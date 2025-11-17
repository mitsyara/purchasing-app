<?php

namespace App\Filament\Exports;

use App\Models\CustomsData;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class CustomsDataExporter extends Exporter
{
    protected static ?string $model = CustomsData::class;

    public function getFormats(): array
    {
        return [
            \Filament\Actions\Exports\Enums\ExportFormat::Xlsx,
        ];
    }

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('import_date')
                ->state(fn($record): ?string => $record->import_date?->format('Y-m-d')),
            ExportColumn::make('importer'),
            ExportColumn::make('product'),
            ExportColumn::make('unit'),
            ExportColumn::make('qty')
                ->state(fn($record) => $record->qty + 0),
            ExportColumn::make('price')
                ->state(fn($record) => $record->price + 0),
            ExportColumn::make('export_country'),
            ExportColumn::make('exporter'),
            ExportColumn::make('incoterm'),
            ExportColumn::make('hscode'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your customs data export has completed and '
            . __number_string_converter($export->successful_rows) . ' '
            . str('row')->plural($export->successful_rows) . ' exported.';

        if (app()->getLocale() === 'vi') {
            $body = 'Dữ liệu Hải quan đã được xuất Thành công. '
                . __number_string_converter($export->successful_rows)
                . ' bản ghi được xuất ra.';
        }

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . __number_string_converter($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
            if (app()->getLocale() === 'vi') {
                $body .= ' ' . __number_string_converter($failedRowsCount) . ' bản ghi xuất thất bại.';
            }
        }

        return $body;
    }
}
