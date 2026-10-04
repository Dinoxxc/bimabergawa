<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProductResource\Pages;

use App\Exports\ProductTemplateExport;
use App\Filament\Resources\ProductResource;
use App\Filament\Resources\ProductResource\Widgets\ProductOverviewWidget;
use App\Imports\ProductsBulkImport;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('New Product SKU')
                ->icon('heroicon-m-plus'),

            Action::make('download_template')
                ->label('Download Template (.xlsx)')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(fn () => Excel::download(new ProductTemplateExport, 'catalog_template.xlsx')),

            Action::make('bulk_import')
                ->label('Bulk Import Excel')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('primary')
                ->visible(fn (): bool => auth()->user()?->hasAnyRole(['super_admin', 'sales_pic']) ?? false)
                ->form([
                    FileUpload::make('file')
                        ->label('Catalog Spreadsheet (.xlsx, .csv)')
                        ->disk('local_private')
                        ->directory('imports')
                        ->acceptedFileTypes([
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'application/vnd.ms-excel',
                            'text/csv',
                            'application/csv',
                            'text/plain',
                        ])
                        ->required(),
                ])
                ->action(function (array $data): void {
                    abort_unless(
                        auth()->user()?->hasAnyRole(['super_admin', 'sales_pic']),
                        403,
                        'Unauthorized access to bulk catalog import.'
                    );

                    $storedPath = $data['file'];
                    $fullPath = Storage::disk('local_private')->path($storedPath);

                    $extension = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
                    if (! in_array($extension, ['xlsx', 'csv', 'xls'], true)) {
                        Notification::make()
                            ->title('Invalid File Format')
                            ->body('Format berkas wajib berupa XLSX atau CSV (File format must be XLSX or CSV).')
                            ->danger()
                            ->send();

                        return;
                    }

                    try {
                        $importer = new ProductsBulkImport;
                        Excel::import($importer, $fullPath);
                        $importer->recordActivityLog();

                        $created = $importer->createdCount;
                        $updated = $importer->updatedCount;
                        $errors = $importer->errors;
                        $total = $importer->getTotalProcessed();

                        if ($total === 0 && ! empty($errors)) {
                            Notification::make()
                                ->title('Import Notice')
                                ->body($errors[0])
                                ->warning()
                                ->send();
                        } elseif (! empty($errors)) {
                            Notification::make()
                                ->title('Import Completed with Warnings')
                                ->body(sprintf(
                                    'Import Selesai: %d produk berhasil diproses (%d ditambahkan, %d diperbarui, %d baris diabaikan).',
                                    $total,
                                    $created,
                                    $updated,
                                    count($errors)
                                ))
                                ->warning()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Import Selesai')
                                ->body(sprintf(
                                    'Import Selesai: %d produk berhasil diproses (Import Complete: %d products processed successfully).',
                                    $total,
                                    $total
                                ))
                                ->success()
                                ->send();
                        }
                    } catch (ValidationException $ve) {
                        Notification::make()
                            ->title('Template Error')
                            ->body($ve->getMessage())
                            ->danger()
                            ->send();

                        throw $ve;
                    }
                }),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            ProductOverviewWidget::class,
        ];
    }
}
