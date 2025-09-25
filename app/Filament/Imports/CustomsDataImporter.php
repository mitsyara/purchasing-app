<?php

namespace App\Filament\Imports;

use App\Models\CustomsData;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class CustomsDataImporter extends Importer
{
    protected static ?string $model = CustomsData::class;

    public static function getOptionsFormComponents(): array
    {
        return [
            \Filament\Schemas\Components\Group::make([
                \Filament\Forms\Components\Checkbox::make('localeDelimiter')
                    ->label('Dấu thập phân là dấu phẩy (,)'),
                \Filament\Forms\Components\Checkbox::make('forceRecordCreate'),
            ])
                ->columns()
                ->columnSpanFull(),
        ];
    }

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('category')
                ->example('Amoxicillin')
                ->ignoreBlankState()
                ->relationship(),

            ImportColumn::make('import_date')
                ->example('2025-01-20')
                ->guess(['import_date', 'ngaynhap'])
                ->rules(['required', 'date'])
                ->requiredMapping(),

            ImportColumn::make('importer')
                ->example('Andy Group Gmbh')
                ->guess(['importer', 'ctynhap'])
                ->rules(['required'])
                ->requiredMapping(),

            ImportColumn::make('product')
                ->example('A mốc xi ci lin Tri hi đờ rát')
                ->guess(['product', 'products', 'hanghoa', 'hang_hoa', 'tenhang'])
                ->rules(['required'])
                ->requiredMapping(),

            ImportColumn::make('unit')
                ->example('KGM')
                ->guess(['unit', 'dvt', 'uom'])
                ->ignoreBlankState()
                ->rules(['max:255'])
                ->requiredMapping(),

            ImportColumn::make('qty')
                ->example('1000')
                ->guess(['qty', 'kl', 'quantity', 'soluong', 'so_luong'])
                ->castStateUsing(fn($state) => static::convertToFloat($state))
                ->rules(['numeric'])
                ->requiredMapping(),

            ImportColumn::make('price')
                ->example('42.5')
                ->guess(['price', 'gia', 'unit_price', 'unit price'])
                ->castStateUsing(fn($state) => static::convertToFloat($state))
                ->rules(['numeric'])
                ->requiredMapping(),

            ImportColumn::make('export_country')
                ->example('China')
                ->guess(['export_country', 'xuat_xu', 'origin_country', 'origin'])
                ->ignoreBlankState()
                ->rules(['max:255'])
                ->requiredMapping(),

            ImportColumn::make('exporter')
                ->example('Sun Pharma')
                ->guess(['exporter', 'doitacnhap'])
                ->requiredMapping(),

            ImportColumn::make('incoterm')
                ->example('CIF')
                ->guess(['incoterm', 'ship', 'incoterms', 'trade_terms', 'trade terms', 'trade_term', 'trade term'])
                ->ignoreBlankState()
                ->rules(['max:255'])
                ->requiredMapping(),

            ImportColumn::make('hscode')
                ->example('123456789')
                ->guess(['hscode', 'hs', 'hs_code', 'hs code'])
                ->ignoreBlankState()
                ->rules(['max:255'])
                ->requiredMapping(),
        ];
    }

    public function resolveRecord(): ?CustomsData
    {
        // default: , <> null|false
        $isSeparator = $this->options['localeDelimiter'];

        if ($this->options['forceRecordCreate'] ?? false) {
            // Update existing records, matching them by `$this->data['column_name']`
            return CustomsData::firstOrNew([
                'import_date' => $this->data['import_date'],
                'importer' => $this->data['importer'],
                'product' => $this->data['product'],
                'qty' => $isSeparator ? $this->data['qty'] : static::convertToFloat($this->data['qty']),
                'price' => $isSeparator ? $this->data['price'] : static::convertToFloat($this->data['price']),
                'exporter' => $this->data['exporter'],
            ]);
        } else {
            return new CustomsData();
        }
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your customs data import has completed and '
            . number_format($import->successful_rows) . ' '
            . str('row')->plural($import->successful_rows) . ' imported.';
        if (app()->getLocale() === 'vi') {
            $body = 'Dữ liệu Hải quan đã được thêm Thành công. Có '
                . number_format($import->successful_rows)
                . ' bản ghi được thêm vào CSDL.';
        }

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' '
                . str('row')->plural($failedRowsCount)
                . ' failed to import.';
            if (app()->getLocale() === 'vi') {
                $body .= ' ' . number_format($failedRowsCount)
                    . ' bị lỗi, không thể thêm vào CSDL.';
            }
        }

        return $body;
    }

    // Helper methods
    public static function convertToFloat($value): ?float
    {
        if (blank($value)) return null;
        
        $value = str_replace(',', '.', $value);
        return round(floatval($value), 3);
    }
}
