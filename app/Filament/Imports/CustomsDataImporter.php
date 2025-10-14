<?php

namespace App\Filament\Imports;

use App\Models\CustomsData;
use Carbon\Carbon;
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

                \Filament\Forms\Components\Select::make('separator')
                    ->label('Decimal separator (e.g. 1,000.25)')
                    ->options([
                        ',' => 'Dot (e.g. 1,000.25)',
                        '.' => 'Comma (e.g. 1.000,25)',
                    ])
                    ->selectablePlaceholder(false)
                    ->default('.'),

                \Filament\Forms\Components\Select::make('dateFormat')
                    ->label('Date Format')
                    ->options([
                        'Y-m-d' => now()->format('Y-m-d'),
                        'd/m/Y' => now()->format('d/m/Y'),
                        'm/d/Y' => now()->format('m/d/Y'),
                        'd-m-Y' => now()->format('d-m-Y'),
                        'm-d-Y' => now()->format('m-d-Y'),
                        'F j, Y' => now()->format('F j, Y') . ' ' . '(Long text Date)',
                        'M j, Y' => now()->format('M j, Y') . ' ' . '(Short text Date)',
                        'j/n/Y' => now()->format('j/n/Y') . ' ' . '(no leading zeros)',
                        'n/j/Y' => now()->format('n/j/Y') . ' ' . '(no leading zeros)',
                    ])
                    ->default('d/m/Y')
                    ->selectablePlaceholder(false)
                    ->required(),
            ])
                ->columns(1)
                ->columnSpanFull(),
        ];
    }

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('import_date')
                ->example('2025-01-20')
                ->guess(['import_date', 'ngaynhap'])
                ->castStateUsing(fn($state, $options): ?string
                => static::convertDate($state, $options['dateFormat']))
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
                ->castStateUsing(fn($state, $options): ?float => match ($options['separator']) {
                    ',' => static::convertToFloat($state, '.'),
                    '.' => static::convertToFloat($state, ',')
                })
                ->rules(['numeric'])
                ->requiredMapping(),

            ImportColumn::make('price')
                ->example('42.5')
                ->guess(['price', 'gia', 'unit_price', 'unit price'])
                ->castStateUsing(fn($state, $options) => match ($options['separator']) {
                    ',' => static::convertToFloat($state, '.'),
                    '.' => static::convertToFloat($state, ',')
                })
                ->rules(['numeric'])
                ->requiredMapping(),

            ImportColumn::make('export_country')
                ->example('China')
                ->guess([
                    'export_country',
                    'xuat_xu',
                    'origin_country',
                    'origin',
                    'country',
                    'original',
                    'nuocsx',
                    'nuoc_sx',
                    'country_of_origin',
                ])
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

    public function resolveRecord(): CustomsData
    {
        return CustomsData::firstOrNew([
            'import_date' => $this->data['import_date'],
            'importer' => $this->data['importer'],
            'product' => $this->data['product'],
            'exporter' => $this->data['exporter'],
        ], [
            'unit' => $this->data['unit'],
            'qty' => $this->data['qty'],
            'price' => $this->data['price'],
            'export_country' => $this->data['export_country'],
            'incoterm' => $this->data['incoterm'],
            'hscode' => $this->data['hscode'],
        ]);
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
    public static function convertToFloat(string|float|int $value, ?string $thousandSeparator = ','): ?float
    {
        if (blank($value)) return null;

        // Remove thousand separators
        if ($thousandSeparator === ',') {
            $value = str_replace($thousandSeparator, '', $value);
        } else {
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
        }

        return round(floatval($value), 3);
    }

    public static function convertDate(string $value, ?string $originFormat): ?string
    {
        if (blank($value)) return null;

        $date = Carbon::createFromFormat($originFormat, $value);

        return $date ? $date->format('Y-m-d') : null;
    }
}
