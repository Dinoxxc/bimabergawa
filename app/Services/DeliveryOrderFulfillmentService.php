<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\DeliveryOrderFulfillmentDTO;
use App\Events\DeliveryOrderDispatched;
use App\Exceptions\InsufficientFulfillmentQuantityException;
use App\Models\DeliveryOrder;
use App\Models\DeliveryOrderItem;
use App\Models\SalesOrder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class DeliveryOrderFulfillmentService
{
    protected DocumentNumberService $documentNumberService;

    public function __construct(?DocumentNumberService $documentNumberService = null)
    {
        $this->documentNumberService = $documentNumberService ?? new DocumentNumberService;
    }

    /**
     * Hitung akumulasi kuantitas terkirim dan sisa kuantitas (remaining_qty) untuk setiap item Sales Order.
     * Mengambil daftar pesanan dari QuotationItem terkait dan menghitung riwayat pengiriman
     * dari seluruh DeliveryOrderItem yang terhubung ke Sales Order ini.
     *
     * @return array<int, array{
     *     quotation_item_id: int,
     *     product_id: ?int,
     *     item_name: string,
     *     model_number: ?string,
     *     uom: string,
     *     ordered_qty: int,
     *     shipped_qty: int,
     *     remaining_qty: int,
     *     is_fulfilled: bool
     * }>
     */
    public function getRemainingQuantities(SalesOrder $salesOrder): array
    {
        $quotation = $salesOrder->quotation;
        if (! $quotation) {
            return [];
        }

        $quotationItems = $quotation->items()->get();

        // Kumpulkan riwayat pengiriman aktif (mengecualikan surat jalan yang telah dibatalkan / di-soft delete)
        $deliveredItems = DeliveryOrderItem::whereHas('deliveryOrder', function ($q) use ($salesOrder) {
            $q->whereNull('delivery_orders.deleted_at')
                ->where('sales_order_id', $salesOrder->id);
        })->with('deliveryOrder')->get();

        // Kumpulkan pool kuantitas terkirim per SKU / item name untuk konsumsi berurutan (FIFO)
        $shippedPool = [];
        foreach ($deliveredItems as $dItem) {
            $key = $dItem->product_id !== null ? 'p_'.$dItem->product_id : 'n_'.trim(strtolower($dItem->item_name));

            // Evaluasi kuantitas efektif:
            // 1. Jika DO telah berstatus 'delivered' atau 'partially_delivered', dan terdapat rekaman penerimaan fisik (received/defective):
            //    gunakan quantity_received (hanya barang yang diterima baik yang dihitung mengurangi sisa pesanan).
            // 2. Jika DO masih berstatus 'prepared' atau 'shipped' (dalam perjalanan):
            //    gunakan quantity_shipped (kuantitas dicadangkan agar tidak terjadi double-shipping).
            $doStatus = $dItem->deliveryOrder?->status ?? 'shipped';
            if (in_array($doStatus, ['delivered', 'partially_delivered'], true) && ($dItem->quantity_received > 0 || $dItem->quantity_defective > 0)) {
                $effectiveQty = (int) $dItem->quantity_received;
            } else {
                $effectiveQty = (int) $dItem->quantity_shipped;
            }

            $shippedPool[$key] = ($shippedPool[$key] ?? 0) + $effectiveQty;
        }

        $result = [];

        foreach ($quotationItems as $qItem) {
            $orderedQty = (int) $qItem->quantity;
            $key = $qItem->product_id !== null ? 'p_'.$qItem->product_id : 'n_'.trim(strtolower($qItem->item_name));

            $availableShipped = $shippedPool[$key] ?? 0;
            $allocatedShipped = min($orderedQty, $availableShipped);

            // Kurangi kuantitas terpakai dari pool pengiriman (mencegah double-counting antar baris pesanan yang sama)
            $shippedPool[$key] = max(0, $availableShipped - $allocatedShipped);

            $remainingQty = max(0, $orderedQty - $allocatedShipped);

            $result[] = [
                'quotation_item_id' => $qItem->id,
                'product_id' => $qItem->product_id,
                'item_name' => $qItem->item_name,
                'model_number' => $qItem->model_number,
                'uom' => $qItem->uom ?? 'pcs',
                'ordered_qty' => $orderedQty,
                'shipped_qty' => $allocatedShipped,
                'remaining_qty' => $remainingQty,
                'is_fulfilled' => ($remainingQty === 0),
            ];
        }

        return $result;
    }

    /**
     * Validasi kelayakan kuantitas pengiriman sebelum surat jalan dibuat:
     * - Memastikan status Sales Order bukan 'cancelled' atau 'completed'.
     * - Memastikan Sales Order telah disetujui resmi dan disetujui Direktur jika >= Rp 100 Juta (TC-ERP-DO-009).
     * - Memastikan kuantitas kirim tidak melebihi sisa pesanan (remaining_qty).
     *
     * @param  array<int, array{sales_order_item_id?: int, quotation_item_id?: int, product_id?: int, quantity_delivered?: int, quantity_shipped?: int, quantity?: int}>  $itemsToShip
     *
     * @throws InvalidArgumentException
     * @throws InsufficientFulfillmentQuantityException
     */
    public function validateShippedQuantities(SalesOrder $salesOrder, array $itemsToShip): void
    {
        if ($salesOrder->status === 'cancelled') {
            throw new InvalidArgumentException('Surat Jalan tidak dapat diterbitkan untuk Sales Order yang telah dibatalkan.');
        }

        if ($salesOrder->status === 'completed') {
            throw new InvalidArgumentException('Seluruh item pesanan pada Sales Order ini telah terkirim lengkap.');
        }

        // Validasi status persetujuan SO dan Quotation (TC-ERP-DO-009)
        if ($salesOrder->quotation && in_array($salesOrder->quotation->status, ['draft', 'pending_director_approval', 'rejected'], true)) {
            throw new InvalidArgumentException('Surat Jalan hanya dapat diterbitkan untuk Sales Order yang telah disetujui resmi.');
        }

        // Validasi kewajiban approval Direktur untuk SO >= Rp 100 Juta (TC-ERP-DO-009)
        if ((float) $salesOrder->total_amount >= 100000000.00 && ! $salesOrder->is_approved_by_director) {
            throw new InvalidArgumentException('Surat Jalan untuk Sales Order senilai Rp 100 Juta ke atas wajib disetujui oleh Direktur sebelum pengiriman.');
        }

        if (empty($itemsToShip)) {
            throw new InvalidArgumentException('Daftar barang yang akan dikirim tidak boleh kosong.');
        }

        $remainingList = $this->getRemainingQuantities($salesOrder);
        $remainingMap = [];
        foreach ($remainingList as $r) {
            $remainingMap[$r['quotation_item_id']] = $r;
            if ($r['product_id'] !== null) {
                $remainingMap['p_'.$r['product_id']] = $r;
            }
        }

        $cumulativeRequested = [];

        foreach ($itemsToShip as $shipItem) {
            $rawQty = $shipItem['quantity_delivered']
                ?? $shipItem['quantity_shipped']
                ?? $shipItem['quantity']
                ?? 0;

            $qtyToShip = (int) $rawQty;
            if ($qtyToShip <= 0) {
                throw new InvalidArgumentException('Kuantitas pengiriman harus lebih besar dari 0.');
            }

            $itemId = $shipItem['sales_order_item_id']
                ?? $shipItem['quotation_item_id']
                ?? $shipItem['item_id']
                ?? null;

            $productId = $shipItem['product_id'] ?? null;

            // Cocokkan item berdasarkan quotation_item_id atau product_id
            $matchedRemaining = null;
            if ($itemId !== null && isset($remainingMap[$itemId])) {
                $matchedRemaining = $remainingMap[$itemId];
            } elseif ($productId !== null && isset($remainingMap['p_'.$productId])) {
                $matchedRemaining = $remainingMap['p_'.$productId];
            } elseif ($itemId !== null && isset($remainingMap['p_'.$itemId])) {
                $matchedRemaining = $remainingMap['p_'.$itemId];
            }

            if ($matchedRemaining === null) {
                throw new InvalidArgumentException(sprintf(
                    'Item pesanan dengan ID [%s] tidak ditemukan pada Sales Order ini.',
                    (string) ($itemId ?? $productId)
                ));
            }

            $targetKey = $matchedRemaining['quotation_item_id'];
            $cumulativeRequested[$targetKey] = ($cumulativeRequested[$targetKey] ?? 0) + $qtyToShip;

            if ($cumulativeRequested[$targetKey] > $matchedRemaining['remaining_qty']) {
                throw new InsufficientFulfillmentQuantityException(
                    salesOrderItemId: (int) ($itemId ?? $matchedRemaining['quotation_item_id']),
                    requestedQty: $cumulativeRequested[$targetKey],
                    remainingQty: $matchedRemaining['remaining_qty'],
                    message: sprintf(
                        'Akumulasi kuantitas pengiriman (%d) untuk item [%s] tidak boleh melebihi sisa pesanan yang belum terkirim (%d).',
                        $cumulativeRequested[$targetKey],
                        $matchedRemaining['item_name'],
                        $matchedRemaining['remaining_qty']
                    )
                );
            }
        }
    }

    /**
     * Terbitkan Surat Jalan (Delivery Order) penuh atau parsial secara atomik.
     * Mengelola penomoran resmi parsial, pembuatan record DO & DO Items,
     * serta transisi status Sales Order (partially_fulfilled / completed).
     *
     * @throws InvalidArgumentException
     * @throws InsufficientFulfillmentQuantityException
     */
    public function createFulfillment(
        DeliveryOrderFulfillmentDTO $dto,
        ?int $userId = null,
        ?string $idempotencyKey = null
    ): DeliveryOrder {
        return DB::transaction(function () use ($dto, $userId) {
            $salesOrder = SalesOrder::where('id', $dto->salesOrderId)
                ->lockForUpdate()
                ->firstOrFail();

            // Validasi kelayakan kuantitas dan status SO
            $this->validateShippedQuantities($salesOrder, $dto->items);

            $quotation = $salesOrder->quotation;
            $remainingList = $this->getRemainingQuantities($salesOrder);
            $remainingMap = [];
            foreach ($remainingList as $r) {
                $remainingMap[$r['quotation_item_id']] = $r;
                if ($r['product_id'] !== null) {
                    $remainingMap['p_'.$r['product_id']] = $r;
                }
            }

            // Hitung indeks parsial DO untuk Sales Order ini (1, 2, 3...)
            $existingDoCount = (int) DeliveryOrder::withTrashed()
                ->where('sales_order_id', $salesOrder->id)
                ->count();
            $partialIndex = $existingDoCount + 1;

            // Format nomor surat jalan resmi (PBB/DOR/YYYY/MM/XXXX/01 atau PBB/DOR/YYYY/MM/XXXX)
            $deliveryNumber = $this->documentNumberService->generateDeliveryOrderNumber(
                soNumber: $salesOrder->order_number,
                partialIndex: $partialIndex,
                userId: $userId
            );

            // Tentukan alamat pengiriman dan informasi PIC
            $resolvedAddress = $dto->shippingAddress
                ?? $salesOrder->shipping_address
                ?? $quotation?->inquiry?->delivery_address
                ?? 'Alamat Proyek Pemesan';

            $resolvedPicName = $dto->recipientPicName
                ?? $quotation?->customer_name
                ?? $quotation?->inquiry?->pic_name
                ?? 'Penerima Lapangan';

            $resolvedPhone = $dto->recipientPhone
                ?? $quotation?->customer_phone
                ?? $quotation?->inquiry?->company_phone
                ?? '-';

            $courierName = $dto->courierName
                ?? $dto->shippingMethod
                ?? 'Armada Internal PBB';

            $deliveryOrder = DeliveryOrder::create([
                'delivery_number' => $deliveryNumber,
                'sales_order_id' => $salesOrder->id,
                'courier_name' => $courierName,
                'driver_name' => $dto->driverName,
                'vehicle_plate_number' => $dto->vehiclePlateNumber,
                'tracking_number' => $dto->trackingNumber,
                'tracking_url' => null,
                'shipping_address' => $resolvedAddress,
                'recipient_pic_name' => $resolvedPicName,
                'recipient_phone' => $resolvedPhone,
                'total_packages' => $dto->totalPackages ?? 1,
                'total_weight_kg' => $dto->totalWeightKg,
                'status' => 'shipped',
                'shipped_at' => now(),
            ]);

            // Buat baris item surat jalan fisik
            foreach ($dto->items as $shipItem) {
                $rawQty = $shipItem['quantity_delivered']
                    ?? $shipItem['quantity_shipped']
                    ?? $shipItem['quantity']
                    ?? 1;

                $qtyToShip = (int) $rawQty;

                $itemId = $shipItem['sales_order_item_id']
                    ?? $shipItem['quotation_item_id']
                    ?? $shipItem['item_id']
                    ?? null;

                $productId = $shipItem['product_id'] ?? null;

                $matched = null;
                if ($itemId !== null && isset($remainingMap[$itemId])) {
                    $matched = $remainingMap[$itemId];
                } elseif ($productId !== null && isset($remainingMap['p_'.$productId])) {
                    $matched = $remainingMap['p_'.$productId];
                } elseif ($itemId !== null && isset($remainingMap['p_'.$itemId])) {
                    $matched = $remainingMap['p_'.$itemId];
                }

                $finalProductId = $productId ?? $matched['product_id'] ?? null;
                $finalItemName = $shipItem['item_name'] ?? $matched['item_name'] ?? 'Item Pengadaan';
                $finalModelNumber = $shipItem['model_number'] ?? $matched['model_number'] ?? null;
                $finalUom = $shipItem['uom'] ?? $matched['uom'] ?? 'pcs';

                DeliveryOrderItem::create([
                    'delivery_order_id' => $deliveryOrder->id,
                    'product_id' => $finalProductId,
                    'item_name' => $finalItemName,
                    'model_number' => $finalModelNumber,
                    'quantity_shipped' => $qtyToShip,
                    'uom' => $finalUom,
                ]);
            }

            // Evaluasi ulang sisa pesanan pasca pengiriman
            $updatedRemaining = $this->getRemainingQuantities($salesOrder);
            $isFullyFulfilled = collect($updatedRemaining)->every(fn ($item) => $item['remaining_qty'] === 0);

            if ($isFullyFulfilled) {
                $salesOrder->update(['status' => 'completed']);
            } else {
                $salesOrder->update(['status' => 'partially_fulfilled']);
            }

            // Emisi domain event pengiriman
            DeliveryOrderDispatched::dispatch(
                $deliveryOrder->id,
                $deliveryOrder->delivery_number,
                $deliveryOrder->courier_name
            );

            return $deliveryOrder;
        }, 3);
    }

    /**
     * Konfirmasi serah terima pengiriman fisik di lokasi proyek (status -> delivered).
     * Menerima upload path bukti surat jalan bertanda tangan dan stempel basah (Proof of Delivery).
     */
    public function confirmDelivery(DeliveryOrder $deliveryOrder, ?string $podPath = null): DeliveryOrder
    {
        if ($deliveryOrder->trashed()) {
            throw new InvalidArgumentException('Surat Jalan yang telah dibatalkan tidak dapat dikonfirmasi pengirimannya.');
        }

        $updateData = [
            'status' => 'delivered',
        ];

        if ($podPath !== null && trim($podPath) !== '') {
            $updateData['proof_of_delivery_path'] = $podPath;
        }

        $deliveryOrder->update($updateData);

        // Pada serah terima normal 100%, set quantity_received = quantity_shipped jika belum diset
        foreach ($deliveryOrder->items as $item) {
            if ($item->quantity_received === 0 && $item->quantity_defective === 0) {
                $item->update([
                    'quantity_received' => $item->quantity_shipped,
                    'quantity_defective' => 0,
                ]);
            }
        }

        return $deliveryOrder;
    }

    /**
     * Konfirmasi serah terima fisik di lokasi proyek dengan pencatatan barang cacat / retur (TC-ERP-DO-017).
     * Mengakomodasi penerimaan sebagian baik dan pengembalian unit cacat/rusak ke gudang,
     * serta meregenerasi sisa pesanan pada Sales Order agar dapat diterbitkan Surat Jalan Pengganti.
     *
     * @param  array<int, array{delivery_order_item_id?: int, quotation_item_id?: int, product_id?: int, quantity_received: int, quantity_defective?: int, defect_notes?: string}>  $itemDiscrepancies
     *
     * @throws InvalidArgumentException
     */
    public function confirmDeliveryWithDiscrepancy(
        DeliveryOrder $deliveryOrder,
        array $itemDiscrepancies,
        ?string $podPath = null,
        ?string $returnProofPath = null,
        ?string $discrepancyNotes = null
    ): DeliveryOrder {
        if ($deliveryOrder->trashed()) {
            throw new InvalidArgumentException('Surat Jalan yang telah dibatalkan tidak dapat dikonfirmasi pengirimannya.');
        }

        if (empty($itemDiscrepancies)) {
            throw new InvalidArgumentException('Daftar verifikasi fisik barang tidak boleh kosong.');
        }

        return DB::transaction(function () use ($deliveryOrder, $itemDiscrepancies, $podPath, $returnProofPath, $discrepancyNotes) {
            $items = $deliveryOrder->items()->get()->keyBy('id');
            $hasDefect = false;

            foreach ($itemDiscrepancies as $disc) {
                $doItemId = $disc['delivery_order_item_id'] ?? null;
                $targetItem = null;

                if ($doItemId !== null && $items->has($doItemId)) {
                    $targetItem = $items->get($doItemId);
                } else {
                    $prodId = $disc['product_id'] ?? null;
                    if ($prodId !== null) {
                        $targetItem = $items->firstWhere('product_id', $prodId);
                    }
                }

                if (! $targetItem) {
                    continue;
                }

                $qtyReceived = max(0, (int) ($disc['quantity_received'] ?? 0));
                $qtyDefective = max(0, (int) ($disc['quantity_defective'] ?? 0));
                $defectNotes = $disc['defect_notes'] ?? null;

                if (($qtyReceived + $qtyDefective) > $targetItem->quantity_shipped) {
                    throw new InvalidArgumentException(sprintf(
                        'Total kuantitas diterima (%d) dan cacat (%d) untuk item [%s] melebihi kuantitas awal yang dikirim (%d).',
                        $qtyReceived,
                        $qtyDefective,
                        $targetItem->item_name,
                        $targetItem->quantity_shipped
                    ));
                }

                if ($qtyDefective > 0) {
                    $hasDefect = true;
                }

                $targetItem->update([
                    'quantity_received' => $qtyReceived,
                    'quantity_defective' => $qtyDefective,
                    'defect_notes' => $defectNotes,
                ]);
            }

            // Jika ada item yang belum tercakup di $itemDiscrepancies, defaultkan received = shipped
            foreach ($items as $item) {
                $refreshed = $item->fresh();
                if ($refreshed->quantity_received === 0 && $refreshed->quantity_defective === 0) {
                    $refreshed->update([
                        'quantity_received' => $refreshed->quantity_shipped,
                        'quantity_defective' => 0,
                    ]);
                }
            }

            $updateData = [
                'status' => $hasDefect ? 'partially_delivered' : 'delivered',
            ];

            if ($podPath !== null && trim($podPath) !== '') {
                $updateData['proof_of_delivery_path'] = $podPath;
            }

            if ($returnProofPath !== null && trim($returnProofPath) !== '') {
                $updateData['return_proof_path'] = $returnProofPath;
            }

            if ($discrepancyNotes !== null && trim($discrepancyNotes) !== '') {
                $updateData['discrepancy_notes'] = $discrepancyNotes;
            }

            $deliveryOrder->update($updateData);

            // Evaluasi ulang status Sales Order
            $salesOrder = SalesOrder::where('id', $deliveryOrder->sales_order_id)->lockForUpdate()->first();
            if ($salesOrder) {
                $remainingList = $this->getRemainingQuantities($salesOrder);
                $isFullyFulfilled = collect($remainingList)->every(fn ($item) => $item['remaining_qty'] === 0);

                if ($isFullyFulfilled) {
                    $salesOrder->update(['status' => 'completed']);
                } else {
                    $salesOrder->update(['status' => 'partially_fulfilled']);
                }
            }

            return $deliveryOrder;
        }, 3);
    }

    /**
     * Batalkan surat jalan (misal: pengiriman gagal, proyek tutup, atau anomali logistik / TC-ERP-DO-016).
     * Mengembalikan status alokasi sisa kuantitas pada Sales Order.
     */
    public function cancelDeliveryOrder(DeliveryOrder $deliveryOrder, string $reason = ''): void
    {
        if ($deliveryOrder->status === 'delivered') {
            throw new InvalidArgumentException('Surat Jalan yang telah berstatus delivered tidak dapat dibatalkan.');
        }

        DB::transaction(function () use ($deliveryOrder, $reason) {
            $salesOrder = SalesOrder::where('id', $deliveryOrder->sales_order_id)
                ->lockForUpdate()
                ->first();

            // Catat alasan pembatalan jika ada
            if ($reason !== '') {
                $deliveryOrder->update([
                    'notes' => ($deliveryOrder->notes ? $deliveryOrder->notes.' | ' : '').'Dibatalkan: '.$reason,
                ]);
            }

            // Hapus surat jalan secara soft delete
            $deliveryOrder->delete();

            if ($salesOrder) {
                // Evaluasi ulang sisa kuantitas setelah DO dihapus
                $remainingList = $this->getRemainingQuantities($salesOrder);
                $totalDelivered = collect($remainingList)->sum('shipped_qty');

                if ($totalDelivered === 0) {
                    $salesOrder->update(['status' => 'processing']);
                } else {
                    $salesOrder->update(['status' => 'partially_fulfilled']);
                }
            }
        }, 3);
    }
}
