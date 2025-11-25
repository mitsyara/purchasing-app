<?php

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;

use Filament\Facades\Filament;
use Filament\Support\Icons\Heroicon;

use Filament\Tables\Columns as T;
use Filament\Forms\Components as F;
use App\Filament\Tables\Columns\LinkColumn as CustomLinkColumn;

if (!function_exists('__index')) {
    function __index(?string $column_name = '#'): T\TextColumn
    {
        return T\TextColumn::make('index')
            ->label($column_name)
            ->rowIndex()
            ->toggleable();
    }
}

if (!function_exists('__eta_etd_fields')) {
    function __eta_etd_fields(bool $isRequired = false, bool $isVisible = true): array
    {
        $errorMessage = 'At least one of the Estimate Dates must be presented.';
        return [
            \Filament\Schemas\Components\Fieldset::make(__('ETD'))
                ->schema([
                    F\DatePicker::make('etd_min')->label(__('From'))
                        ->requiredWithoutAll(fn() => $isRequired ? ['etd_max', 'eta_min', 'eta_max', 'atd', 'ata'] : null)
                        ->validationMessages([
                            'required_without_all' => __($errorMessage),
                        ])
                        ->extraInputAttributes(['id' => 'data-custom-etd_min']),

                    F\DatePicker::make('etd_max')->label(__('To'))
                        ->requiredWithoutAll(fn() => $isRequired ? ['etd_min', 'eta_min', 'eta_max', 'atd', 'ata'] : null)
                        ->validationMessages([
                            'required_without_all' => __($errorMessage),
                        ])
                        ->extraInputAttributes(['id' => 'data-custom-etd_max']),
                ])
                ->columns([
                    'default' => 2,
                    'lg' => 1,
                    'xl' => 2,
                ])
                ->visible($isVisible),

            \Filament\Schemas\Components\Fieldset::make(__('ETA'))
                ->schema([
                    F\DatePicker::make('eta_min')->label(__('From'))
                        ->requiredWithoutAll(fn() => $isRequired ? ['etd_min', 'etd_max', 'eta_max', 'atd', 'ata'] : null)
                        ->validationMessages([
                            'required_without_all' => __($errorMessage),
                        ])
                        ->extraInputAttributes(['id' => 'data-custom-eta_min']),

                    F\DatePicker::make('eta_max')->label(__('To'))
                        ->requiredWithoutAll(fn() => $isRequired ? ['etd_min', 'etd_max', 'eta_min', 'atd', 'ata'] : null)
                        ->validationMessages([
                            'required_without_all' => __($errorMessage),
                        ])
                        ->extraInputAttributes(['id' => 'data-custom-eta_max']),
                ])
                ->columns([
                    'default' => 2,
                    'lg' => 1,
                    'xl' => 2,
                ])
                ->visible($isVisible),
        ];
    }
}

if (!function_exists('__atd_ata_fields')) {
    function __atd_ata_fields(int|array|null $columns = 2): \Filament\Schemas\Components\Group
    {
        return \Filament\Schemas\Components\Group::make([
            F\DatePicker::make('atd')->label(__('ATD'))
                ->maxDate(today())
                ->extraInputAttributes(['data-custom-atd' => true]),

            F\DatePicker::make('ata')->label(__('ATA'))
                ->maxDate(today())
                ->extraInputAttributes(['data-custom-ata' => true]),
        ])
            ->grow(false)
            ->columns($columns);
    }
}

if (!function_exists('__date_range_column')) {
    function __date_range_column(string $field, ?string $field_min = null, ?string $field_max = null): T\TextColumn
    {
        $field_min = $field_min ?? $field . '_min';
        $field_max = $field_max ?? $field . '_max';

        return T\TextColumn::make($field)->label(__(mb_strtoupper($field)))
            ->getStateUsing(function ($record) use ($field_min, $field_max): string {
                if (!$record->$field_min && !$record->$field_max) {
                    return __('N/A');
                }
                if (($record->$field_min
                    && ($record->$field_max === $record->$field_min || !$record->$field_max))) {
                    return $record->$field_min?->format('d/m/Y');
                }
                if (($record->$field_max
                    && ($record->$field_max === $record->$field_min || !$record->$field_min))) {
                    return $record->$field_max?->format('d/m/Y');
                }
                return $record->$field_min?->format('d/m/Y')
                    . ' - ' . $record->$field_max?->format('d/m/Y');
            })
            ->sortable(
                condition: true,
                query: fn(\Illuminate\Database\Eloquent\Builder $query, string $direction)
                => $query
                    ->orderBy($field_min, $direction)
                    ->orderBy($field_max, $direction)
            )
            ->toggleable();
    }
}

if (!function_exists('__code_field')) {
    function __code_field(
        ?string $column = 'code',
        string|Heroicon|null $icon = Heroicon::InformationCircle,
        ?string $tooltip = 'Leave empty to Auto-Generate code'
    ): F\TextInput {
        return F\TextInput::make($column)
            ->unique()
            ->hintIcon($icon)
            ->hintIconTooltip(__($tooltip));
    }
}

if (!function_exists('__number_field')) {
    function __number_field(
        string $fieldName,
        ?string $suffixField = null,
        bool $autoLocale = true,
        bool $negative = false
    ): F\TextInput {
        // Lấy locale hiện tại & ký hiệu phân tách
        if ($autoLocale) {
            $locale = app()->getLocale();
            $fmt = new \NumberFormatter($locale, \NumberFormatter::DECIMAL);
            $decimalSeparator = $fmt->getSymbol(\NumberFormatter::DECIMAL_SEPARATOR_SYMBOL);
            $thousandSeparator = $fmt->getSymbol(\NumberFormatter::GROUPING_SEPARATOR_SYMBOL);
        } else {
            // Mặc định kiểu Việt Nam
            $decimalSeparator = ',';
            $thousandSeparator = '.';
        }

        $textInput = F\TextInput::make($fieldName)
            ->minValue($negative ? null : 0.001)
            ->afterStateHydrated(function (
                F\TextInput $component,
                ?string $state
            ) use ($autoLocale, $negative, $decimalSeparator, $thousandSeparator): void {
                if ($state === null || $state === '') return;

                // Helper function để format số với precision 3 và trim zeros
                $formatNumber = function (float $numValue) use ($autoLocale, $decimalSeparator, $thousandSeparator): string {
                    if ($autoLocale) {
                        $locale = app()->getLocale();
                        $fmt = new \NumberFormatter($locale, \NumberFormatter::DECIMAL);
                        $fmt->setAttribute(\NumberFormatter::FRACTION_DIGITS, 3);
                        $formatted = $fmt->format($numValue);
                    } else {
                        $formatted = number_format($numValue, 3, $decimalSeparator, $thousandSeparator);
                    }

                    // Trim trailing zeros
                    return preg_replace('/' . preg_quote($decimalSeparator, '/') . '?0+$/', '', $formatted);
                };

                if (is_numeric($state)) {
                    // Số thuần → format trực tiếp
                    $numValue = (float) $state;
                    $formatted = $formatNumber($numValue);
                } else {
                    // Chuỗi → parse về số rồi format lại
                    $numValue = __number_string_converter($state, false, $autoLocale);

                    if ($numValue !== null) {
                        // Xử lý số âm nếu không cho phép
                        if (!$negative && $numValue < 0) {
                            $numValue = abs($numValue);
                        }

                        $formatted = $formatNumber($numValue);
                    } else {
                        $formatted = '';
                    }
                }

                $component->state($formatted);
            })

            ->stripCharacters($thousandSeparator)

            ->mask(\Filament\Support\RawJs::make(strtr(<<<'JS'
                () => {
                    const decimalSeparator = '{{decimal}}';
                    const thousandSeparator = '{{thousand}}';
                    const allowNegative = '{{allowNegative}}';

                    let skipNextInput = false;

                    // ✅ Bắt riêng numpad decimal → dùng làm dấu thập phân
                    $el.addEventListener('keydown', (e) => {
                        if (e.code === 'NumpadDecimal') {
                            e.preventDefault();

                            const start = $el.selectionStart;
                            const end = $el.selectionEnd;
                            const value = $el.value;

                            // Nếu đã có 1 dấu decimalSeparator → bỏ qua
                            if (value.includes(decimalSeparator)) return;

                            // Chèn ký tự decimal vào vị trí con trỏ
                            $el.value = value.slice(0, start) + decimalSeparator + value.slice(end);
                            $el.setSelectionRange(start + 1, start + 1);

                            // Bỏ qua input event kế tiếp
                            skipNextInput = true;
                        }
                    });

                    // ✅ Logic format chính
                    $el.addEventListener('input', () => {
                        if (skipNextInput) {
                            skipNextInput = false;
                            return;
                        }

                        // Giữ lại số, dấu decimalSeparator, thousandSeparator và dấu trừ (nếu cho phép)
                        let pattern = allowNegative === 'true'
                            ? '[^\\d\\' + decimalSeparator + '\\' + thousandSeparator + '\\-]'
                            : '[^\\d\\' + decimalSeparator + '\\' + thousandSeparator + ']';
                        let raw = $el.value.replace(new RegExp(pattern, 'g'), '');

                        // Xử lý dấu trừ: nếu có dấu trừ bất kỳ đâu thì chuyển lên đầu
                        let isNegative = false;
                        if (allowNegative === 'true' && raw.includes('-')) {
                            isNegative = true; // Bất kỳ có dấu trừ nào thì coi như số âm
                            raw = raw.replace(/-/g, ''); // Xóa tất cả dấu trừ
                        }

                        // Chuẩn hoá nếu user nhập trộn dấu (ví dụ nhập 1.000,5 ở vi-VN)
                        // Giữ lại tất cả thousandSeparators, chỉ xác định 1 decimalSeparator cuối cùng
                        let lastDecimalPos = raw.lastIndexOf(decimalSeparator);
                        let integerPart = lastDecimalPos >= 0 ? raw.slice(0, lastDecimalPos) : raw;
                        let decimalPart = lastDecimalPos >= 0 ? raw.slice(lastDecimalPos + 1) : '';

                        // Xoá mọi thousandSeparator trong phần integer
                        const cleanInteger = integerPart.replace(new RegExp('\\' + thousandSeparator, 'g'), '');

                        // Format lại phần integer với thousandSeparator chuẩn
                        const formattedInteger = cleanInteger.replace(/\B(?=(\d{3})+(?!\d))/g, thousandSeparator);

                        let result = '';
                        if (lastDecimalPos >= 0) {
                            // Có phần thập phân
                            result = `${formattedInteger}${decimalSeparator}${decimalPart.slice(0, 3)}`;
                        } else {
                            result = formattedInteger;
                        }

                        // Thêm dấu trừ vào đầu nếu cần
                        if (allowNegative === 'true' && isNegative) {
                            result = '-' + result;
                        }

                        $el.value = result;
                    });
                }
            JS, [
                '{{decimal}}' => $decimalSeparator,
                '{{thousand}}' => $thousandSeparator,
                '{{allowNegative}}' => $negative ? 'true' : 'false',
            ])))

            ->validationMessages([
                'min' => 'Min: :value.',
            ])

            ->dehydrateStateUsing(function ($state) use ($autoLocale, $negative) {
                // Kiểm tra state null hoặc empty string, NHƯNG không loại trừ "0"
                if ($state === null || $state === '') return null;

                // parse về float
                $result = __number_string_converter($state, false, $autoLocale);

                // Xử lý số âm nếu không cho phép
                if (!$negative && $result !== null && $result < 0) {
                    $result = abs($result);
                }

                return $result;
            });

        return $suffixField
            ? $textInput
            ->suffix(\Filament\Schemas\JsContent::make("\$get('{$suffixField}')"))
            : $textInput;
    }
}


if (!function_exists('__certificates')) {
    function __certificates(?string $column = 'certificates'): F\TagsInput
    {
        return F\TagsInput::make($column)
            ->separator(',')
            ->splitKeys(['Tab', ',', ';'])
            ->suggestions(CERTIFICATES);
    }
}

if (!function_exists('__notes')) {
    function __notes(?string $column = 'notes', ?string $label = null): F\Textarea
    {
        return F\Textarea::make($column)
            ->label($label ?? __('Notes'));
    }
}

if (!function_exists('__join_and_sortable')) {
    /** @param array $joinsArray = [ $tableJoin => [$foreignKey, $orderColumn], ... ] */
    function __join_and_sortable(
        Builder $query,
        string $direction,
        // format: 'table' => [foreignKey, orderColumn]
        array $joinsArray
    ): Builder {
        // Lấy bảng
        $mainTable = $query->getModel()->getTable();
        foreach ($joinsArray as $joinTable => $params) {
            [$foreignKey, $orderColumn] = $params;

            // Tách alias nếu có "as"
            if (Str::contains(strtolower($joinTable), ' as ')) {
                [$baseTable, $alias] = preg_split('/\s+as\s+/i', $joinTable);
            } else {
                $baseTable = $alias = $joinTable;
            }

            $query->leftJoin("{$baseTable} as {$alias}", "{$mainTable}.{$foreignKey}", '=', "{$alias}.id");
            $query->orderBy("{$alias}.{$orderColumn}", $direction);
        }

        return $query->select("{$mainTable}.*");
    }
}



if (!function_exists('filament_repeater_path_diff')) {
    //
}

/**
 * Filament JS Helpers
 */
