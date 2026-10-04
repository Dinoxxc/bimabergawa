<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\Brand;
use App\Models\CommissionPayout;
use App\Models\Inquiry;
use App\Models\Invoice;
use App\Models\Quotation;
use App\Models\SalesOrder;
use App\Models\User;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReportsHub extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static string|\UnitEnum|null $navigationGroup = 'Reports & Analytics';

    protected static ?string $navigationLabel = 'Reports Hub';

    protected static ?string $title = 'Executive Business & Financial Reports Hub';

    protected static ?string $slug = 'reports';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.reports-hub';

    public string $activeTab = 'sales';

    /**
     * RBAC Access Matrix: Super Admin, Finance, and Sales PIC can access.
     * Logistics and Mitra (freelance agents) are strictly forbidden (HTTP 403).
     */
    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user && $user->hasAnyRole(['super_admin', 'sales_pic', 'finance']);
    }

    public function mount(): void
    {
        $user = auth()->user();

        // Security Guard: If user is Sales PIC only (no Super Admin / Finance), restrict strictly to Tab 1 (Sales)
        if ($user && $user->hasRole('sales_pic') && ! $user->hasAnyRole(['super_admin', 'finance'])) {
            $this->activeTab = 'sales';
        }
    }

    public function setActiveTab(string $tab): void
    {
        $user = auth()->user();

        // Enforce Zero HPP Leak constraint for Sales PIC role
        if ($user && $user->hasRole('sales_pic') && ! $user->hasAnyRole(['super_admin', 'finance'])) {
            if ($tab !== 'sales') {
                $this->activeTab = 'sales';

                return;
            }
        }

        $this->activeTab = $tab;
    }

    /**
     * Determine if current user can view Margin HPP tab.
     */
    public function canViewMarginTab(): bool
    {
        $user = auth()->user();

        return $user && $user->hasAnyRole(['super_admin', 'finance']);
    }

    /**
     * Determine if current user can view Accounts Receivable tab.
     */
    public function canViewArTab(): bool
    {
        $user = auth()->user();

        return $user && $user->hasAnyRole(['super_admin', 'finance']);
    }

    /**
     * Determine if current user can view Commission tab.
     */
    public function canViewCommissionTab(): bool
    {
        $user = auth()->user();

        return $user && $user->hasAnyRole(['super_admin', 'finance']);
    }

    /**
     * Tab 1: Sales & Conversion Metrics.
     *
     * @return array<string, mixed>
     */
    public function getSalesPerformanceData(): array
    {
        $totalInquiries = Inquiry::count();
        $wonInquiries = Inquiry::where('status', 'deal')->count();
        $conversionRate = $totalInquiries > 0 ? round(($wonInquiries / $totalInquiries) * 100, 2) : 0.0;

        $validSoQuery = SalesOrder::whereIn('status', ['approved', 'processing', 'ready_to_ship', 'partially_shipped', 'shipped', 'completed']);
        $totalSalesValue = (float) $validSoQuery->sum('total_amount');
        $soCount = $validSoQuery->count();
        $averageOrderValue = $soCount > 0 ? round($totalSalesValue / $soCount, 2) : 0.0;

        // Brand Performance Breakdown
        $brands = Brand::withCount('products')->get()->map(function ($brand) {
            $inquiryItemsCount = DB::table('inquiry_items')
                ->join('products', 'inquiry_items.product_id', '=', 'products.id')
                ->where('products.brand_id', $brand->id)
                ->count();

            return [
                'name' => $brand->name,
                'code' => $brand->getEffectiveCode(),
                'products_count' => $brand->products_count,
                'inquiry_items_count' => $inquiryItemsCount,
            ];
        })->sortByDesc('inquiry_items_count')->take(5)->values();

        // Sales PIC Productivity Matrix
        $salesReps = User::role('sales_pic')->get()->map(function ($rep) {
            $assignedInquiries = Inquiry::where('assigned_user_id', $rep->id)->count();
            $wonDeals = Inquiry::where('assigned_user_id', $rep->id)->where('status', 'deal')->count();
            $repSalesOrders = SalesOrder::whereHas('quotation', fn ($q) => $q->where('user_id', $rep->id))
                ->whereIn('status', ['approved', 'processing', 'ready_to_ship', 'partially_shipped', 'shipped', 'completed'])
                ->sum('total_amount');

            return [
                'name' => $rep->name,
                'email' => $rep->email,
                'assigned_inquiries' => $assignedInquiries,
                'won_deals' => $wonDeals,
                'conversion' => $assignedInquiries > 0 ? round(($wonDeals / $assignedInquiries) * 100, 1) : 0.0,
                'total_omzet' => (float) $repSalesOrders,
            ];
        });

        return [
            'totalInquiries' => $totalInquiries,
            'wonInquiries' => $wonInquiries,
            'conversionRate' => $conversionRate,
            'totalSalesValue' => $totalSalesValue,
            'soCount' => $soCount,
            'averageOrderValue' => $averageOrderValue,
            'brands' => $brands,
            'salesReps' => $salesReps,
        ];
    }

    /**
     * Tab 2: Profitability & Live Margin HPP Metrics (Guarded from Sales PIC).
     *
     * @return array<string, mixed>
     */
    public function getMarginAnalysisData(): array
    {
        if (auth()->check() && ! $this->canViewMarginTab()) {
            return [];
        }

        $quotations = Quotation::whereIn('status', ['approved', 'sent'])->get();

        $totalSubtotal = (float) $quotations->sum('subtotal');
        $totalCogs = 0.0;
        $healthyCount = 0;
        $thinCount = 0;
        $criticalCount = 0;

        foreach ($quotations as $quo) {
            $margin = (float) $quo->gross_profit_margin;
            if ($margin >= 15.00) {
                $healthyCount++;
            } elseif ($margin >= 8.00) {
                $thinCount++;
            } else {
                $criticalCount++;
            }

            // Estimate COGS from quotation subtotal and gross margin
            $cogs = (float) $quo->subtotal * (1 - ($margin / 100));
            $totalCogs += $cogs;
        }

        $grossProfit = $totalSubtotal - $totalCogs;
        $weightedMargin = $totalSubtotal > 0 ? round(($grossProfit / $totalSubtotal) * 100, 2) : 0.0;

        // Critical Margin Transactions
        $criticalTransactions = Quotation::where('gross_profit_margin', '<', 8.00)
            ->whereNotNull('approved_at')
            ->latest()
            ->take(10)
            ->get();

        return [
            'totalRevenue' => $totalSubtotal,
            'totalCogs' => $totalCogs,
            'grossProfit' => $grossProfit,
            'weightedMargin' => $weightedMargin,
            'healthyCount' => $healthyCount,
            'thinCount' => $thinCount,
            'criticalCount' => $criticalCount,
            'totalCount' => $quotations->count(),
            'criticalTransactions' => $criticalTransactions,
        ];
    }

    /**
     * Tab 3: Accounts Receivable (AR Aging Buckets).
     *
     * @return array<string, mixed>
     */
    public function getArAgingData(): array
    {
        if (auth()->check() && ! $this->canViewArTab()) {
            return [];
        }

        $unpaidInvoices = Invoice::whereNotIn('status', ['paid', 'cancelled'])->get();

        $currentAr = 0.0;
        $overdue1_30 = 0.0;
        $overdue31_60 = 0.0;
        $criticalOverdue = 0.0;
        $totalOutstanding = 0.0;

        $now = Carbon::now();
        $invoicesList = [];

        foreach ($unpaidInvoices as $inv) {
            $remaining = (float) ($inv->grand_total - $inv->paid_amount);
            $totalOutstanding += $remaining;
            $dueDate = $inv->due_date ? Carbon::parse($inv->due_date) : $now;

            if ($dueDate->greaterThanOrEqualTo($now)) {
                $currentAr += $remaining;
                $bucket = 'Current (Not Overdue)';
                $badgeColor = 'success';
            } else {
                $daysOverdue = (int) $dueDate->diffInDays($now);
                if ($daysOverdue <= 30) {
                    $overdue1_30 += $remaining;
                    $bucket = "{$daysOverdue} Days (Tier 1)";
                    $badgeColor = 'warning';
                } elseif ($daysOverdue <= 60) {
                    $overdue31_60 += $remaining;
                    $bucket = "{$daysOverdue} Days (Tier 2)";
                    $badgeColor = 'warning';
                } else {
                    $criticalOverdue += $remaining;
                    $bucket = "{$daysOverdue} Days (Critical)";
                    $badgeColor = 'danger';
                }
            }

            // WhatsApp reminder link preparation
            $cleanPhone = preg_replace('/[^0-9]/', '', (string) ($inv->phone_wa ?? '08123456789'));
            if (str_starts_with($cleanPhone, '0')) {
                $cleanPhone = '62'.substr($cleanPhone, 1);
            }
            $formattedBalance = number_format($remaining, 0, ',', '.');
            $msg = rawurlencode("Kepada Yth. Keuangan {$inv->company_name}, kami mengingatkan tagihan Invoice No. {$inv->invoice_number} senilai Rp {$formattedBalance} dengan jatuh tempo {$inv->due_date?->format('d/m/Y')}. Mohon konfirmasi pembayaran ke rekening resmi PT. Panca Bima Bergawa.");
            $waUrl = "https://wa.me/{$cleanPhone}?text={$msg}";

            $invoicesList[] = [
                'id' => $inv->id,
                'company_name' => $inv->company_name,
                'invoice_number' => $inv->invoice_number,
                'due_date' => $inv->due_date?->format('d M Y') ?? '-',
                'grand_total' => (float) $inv->grand_total,
                'paid_amount' => (float) $inv->paid_amount,
                'remaining' => $remaining,
                'bucket' => $bucket,
                'badgeColor' => $badgeColor,
                'waUrl' => $waUrl,
            ];
        }

        return [
            'totalOutstanding' => $totalOutstanding,
            'currentAr' => $currentAr,
            'overdue1_30' => $overdue1_30,
            'overdue31_60' => $overdue31_60,
            'criticalOverdue' => $criticalOverdue,
            'invoicesList' => $invoicesList,
        ];
    }

    /**
     * Tab 4: Agent Freelance Commission Ledger & Rankings.
     *
     * @return array<string, mixed>
     */
    public function getCommissionData(): array
    {
        if (auth()->check() && ! $this->canViewCommissionTab()) {
            return [];
        }

        $totalIssued = (float) CommissionPayout::sum('amount');
        $totalPaid = (float) CommissionPayout::where('status', 'paid')->sum('amount');
        $payableBalance = (float) CommissionPayout::whereIn('status', ['pending', 'verified', 'approved'])->sum('amount');

        // Top Agents Performance
        $agents = User::role('freelance_sales')->get()->map(function ($agent) {
            $totalDeals = SalesOrder::whereHas('quotation', fn ($q) => $q->where('freelance_sales_id', $agent->freelance_sales_id ?? $agent->id))
                ->whereIn('status', ['approved', 'processing', 'ready_to_ship', 'partially_shipped', 'shipped', 'completed'])
                ->sum('total_amount');

            $commissionEarned = (float) CommissionPayout::where('freelance_sales_id', $agent->freelance_sales_id ?? $agent->id)->sum('amount');
            $commissionPaid = (float) CommissionPayout::where('freelance_sales_id', $agent->freelance_sales_id ?? $agent->id)->where('status', 'paid')->sum('amount');

            return [
                'name' => $agent->name,
                'email' => $agent->email,
                'total_deals' => (float) $totalDeals,
                'commission_earned' => $commissionEarned,
                'commission_paid' => $commissionPaid,
                'balance' => $commissionEarned - $commissionPaid,
            ];
        })->sortByDesc('commission_earned')->values();

        return [
            'totalIssued' => $totalIssued,
            'totalPaid' => $totalPaid,
            'payableBalance' => $payableBalance,
            'agents' => $agents,
        ];
    }
}
