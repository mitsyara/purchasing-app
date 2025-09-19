<?php

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;

use Filament\Facades\Filament;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Icons\Heroicon;

use Filament\Forms\Components as F;
use Filament\Tables\Columns as T;
use App\Filament\Tables\Columns\LinkColumn as CustomLinkColumn;
use Illuminate\Support\HtmlString;

if (!function_exists('__index')) {
    function __index(?string $column_name = '#'): T\TextColumn
    {
        return T\TextColumn::make('index')
            ->label($column_name)
            ->rowIndex()
            ->toggleable();
    }
}

if (!function_exists('__file_upload')) {
    function __file_upload(?string $model = null, ?bool $is_multiple = false): F\FileUpload
    {
        $path = 'attachments/' . model_to_path($model);

        $form_field = $is_multiple
            // Multiple files
            ? F\FileUpload::make(DB_FILES_COLUMN)
            ->storeFileNamesIn(DB_FILES_NAME)
            ->multiple()
            // Single file
            : F\FileUpload::make(DB_FILE_COLUMN)
            ->storeFileNamesIn(DB_FILE_NAME);

        return $form_field
            ->disk(FILES_DISK)
            ->directory($path)
            ->openable()
            ->downloadable()
            ->previewable(false)
            ->acceptedFileTypes(FILE_TYPES)
            ->maxSize(FILE_MAX_SIZE);
    }
}

if (!function_exists('__order_date_fields')) {
    function __order_date_fields(bool $readonly = false, string|null $date = null): F\Field
    {
        \Filament\Infolists\Components\TextEntry::make('order_date_display')
            ->label(__('Order Date'))
            ->inlineLabel()
            ->state(function (): \Filament\Schemas\JsContent {
                $locale = app()->getLocale();
                return \Filament\Schemas\JsContent::make(<<<JS
                        \$get('order_date') ? new Date(\$get('order_date')).toLocaleDateString('$locale', {
                            day: '2-digit',
                            month: 'long',
                            year: 'numeric',
                        }) : 'N/A';
                    JS);
            });

        $field = $readonly
            ? F\DatePicker::make('order_date')
            ->dehydrated(false)
            ->extraAttributes(['class' => 'opacity-80 cursor-not-allowed'])
            ->extraFieldWrapperAttributes(['class' => 'hidden'])

            : F\DatePicker::make('order_date')
            ->maxDate(today())
            ->requiredWith('status');

        return $field
            ->afterStateHydrated(function (F\Field $component) use ($date) {
                $component->state($date);
                $orderDate = json_encode($date);
                $component->getLivewire()->js(<<<JS
                    \$dispatch('root-date-changed', {
                        orderDate: {$orderDate},
                    });
                JS);
            })
            ->afterStateUpdatedJs(__dispatch_order_date_changed_js())
            ->extraInputAttributes(['id' => 'data-custom-order_date']);
    }
}
if (!function_exists('__eta_etd_fields')) {
    function __eta_etd_fields(bool $isRequired = false): array
    {
        return [
            \Filament\Schemas\Components\Fieldset::make(__('ETD'))
                ->schema([
                    F\DatePicker::make('etd_min')->label(__('From'))
                        ->requiredWithoutAll(fn() => $isRequired ? ['etd_max', 'eta_min', 'eta_max', 'atd', 'ata'] : null)
                        ->validationMessages([
                            'required_without_all' => __('At least one of the ETA/ETD must be presented.'),
                        ])
                        ->extraInputAttributes(['id' => 'data-custom-etd_min']),

                    F\DatePicker::make('etd_max')->label(__('To'))
                        ->requiredWithoutAll(fn() => $isRequired ? ['etd_min', 'eta_min', 'eta_max', 'atd', 'ata'] : null)
                        ->validationMessages([
                            'required_without_all' => __('At least one of the ETA/ETD must be presented.'),
                        ])
                        ->extraInputAttributes(['id' => 'data-custom-etd_max']),
                ])
                ->columns([
                    'default' => 2,
                    'lg' => 1,
                    'xl' => 2,
                ]),

            \Filament\Schemas\Components\Fieldset::make(__('ETA'))
                ->schema([
                    F\DatePicker::make('eta_min')->label(__('From'))
                        ->requiredWithoutAll(fn() => $isRequired ? ['etd_min', 'etd_max', 'eta_max', 'atd', 'ata'] : null)
                        ->validationMessages([
                            'required_without_all' => __('At least one of the ETA/ETD must be presented.'),
                        ])
                        ->extraInputAttributes(['id' => 'data-custom-eta_min']),

                    F\DatePicker::make('eta_max')->label(__('To'))
                        ->requiredWithoutAll(fn() => $isRequired ? ['etd_min', 'etd_max', 'eta_min', 'atd', 'ata'] : null)
                        ->validationMessages([
                            'required_without_all' => __('At least one of the ETA/ETD must be presented.'),
                        ])
                        ->extraInputAttributes(['id' => 'data-custom-eta_max']),
                ])
                ->columns([
                    'default' => 2,
                    'lg' => 1,
                    'xl' => 2,
                ]),
        ];
    }
}

if (!function_exists('__atd_ata_fields')) {
    function __atd_ata_fields(): \Filament\Schemas\Components\Group
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
            ->columns();
    }
}

if (!function_exists('__file_column')) {
    function __file_column(?bool $is_multiple = false): CustomLinkColumn
    {
        $column = $is_multiple ? DB_FILES_COLUMN : DB_FILE_COLUMN;
        $title = $is_multiple ? DB_FILES_NAME : DB_FILE_NAME;
        return CustomLinkColumn::make($column)
            ->titleColumn($title)
            ->multiple($is_multiple);
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
                } else if (($record->$field_min
                    && ($record->$field_max === $record->$field_min || !$record->$field_max))) {
                    return $record->$field_min?->format('d/m/Y');
                } else {
                    return $record->$field_min?->format('d/m/Y') . ' - '
                        . $record->$field_max?->format('d/m/Y');
                }
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
    function __number_field(string $fieldName, ?string $suffixField = null, ?bool $autoLocale = false): F\TextInput
    {
        return $autoLocale ? __number_field_auto_locale($fieldName, $suffixField) : __number_field_vi($fieldName, $suffixField);
    }
}
if (!function_exists('__number_field_vi')) {
    function __number_field_vi(string $fieldName, ?string $suffixField = null): F\TextInput
    {
        $textInput = F\TextInput::make($fieldName)
            ->minValue(0.001)
            ->afterStateHydrated(function (
                F\TextInput $component,
                ?string $state
            ): void {
                if (!$state) {
                    return;
                }
                // Xoá dấu phẩy từ gốc, format lại thành chuỗi dạng 1,000.12
                $formatted = number_format((float) str_replace(',', '', $state), 3, '.', ',');
                // Bỏ phần thập phân thừa số 0
                $formatted = preg_replace('/\.?0+$/', '', $formatted);
                $component->state($formatted);
            })
            ->mask(\Filament\Support\RawJs::make(<<<'JS'
                () => {
                    $el.addEventListener('input', () => {
                        let raw = $el.value.replace(/[^\d.]/g, '');
                        const firstDotIndex = raw.indexOf('.');
                        raw = raw.replace(/\./g, (match, index) => index === firstDotIndex ? '.' : '');

                        const parts = raw.split('.');
                        const integer = parts[0];
                        const decimal = parts[1] ?? '';
                        const formattedInteger = integer.replace(/\B(?=(\d{3})+(?!\d))/g, ',');

                        if (raw.endsWith('.') && decimal === '') {
                            $el.value = formattedInteger + '.';
                        } else if (decimal !== '') {
                            $el.value = `${formattedInteger}.${decimal.slice(0, 3)}`;
                        } else {
                            $el.value = formattedInteger;
                        }
                    });
                }
            JS))
            ->rules([
                fn(F\TextInput $component): Closure
                => function (string $attribute, $value, Closure $fail) use ($component) {
                    $min = $component->getMinValue();
                    if ((float) $value < $min) {
                        $fail(__("Min") . ': ' . $min);
                    }
                },
            ])
            ->stripCharacters(',');

        return $suffixField != null
            ? $textInput
            ->suffix(\Filament\Schemas\JsContent::make("\$get('{$suffixField}')"))
            : $textInput;
    }
}

if (!function_exists('__number_field_auto_locale')) {
    function __number_field_auto_locale(string $fieldName, ?string $suffixField = null): F\TextInput
    {
        $locale = match (app()->getLocale()) {
            'vi' => 'vi_VN',
            'en' => 'en_US',
            default => 'en_US',
        };

        $formatter = new \NumberFormatter($locale, \NumberFormatter::DECIMAL);

        $decimalSeparator = $formatter->getSymbol(\NumberFormatter::DECIMAL_SEPARATOR_SYMBOL);
        $thousandSeparator = $formatter->getSymbol(\NumberFormatter::GROUPING_SEPARATOR_SYMBOL);

        $textInput = F\TextInput::make($fieldName)
            ->minValue(0.001)
            ->afterStateHydrated(function (
                F\TextInput $component,
                ?string $state
            ) use ($decimalSeparator, $thousandSeparator): void {
                if (!$state) {
                    return;
                }
                // Xoá dấu phẩy từ gốc, format lại thành chuỗi dạng 1,000.12
                $formatted = number_format(
                    (float) str_replace($thousandSeparator, '', $state),
                    3,
                    $decimalSeparator,
                    $thousandSeparator
                );
                // Bỏ phần thập phân thừa số 0
                $formatted = preg_replace('/\.?0+$/', '', $formatted);
                $component->state($formatted);
            })
            ->mask(\Filament\Support\RawJs::make(strtr(<<<'JS'
                () => {
                    const decimalSeparator = '{{decimal}}';
                    const thousandSeparator = '{{thousand}}';

                    $el.addEventListener('input', () => {
                        let raw = $el.value
                            .replace(new RegExp('[^\\d' + decimalSeparator + ']', 'g'), '')
                            .replace(new RegExp('\\' + decimalSeparator, 'g'), '.');

                        const firstDotIndex = raw.indexOf('.');
                        raw = raw.replace(/\./g, (match, index) => index === firstDotIndex ? '.' : '');

                        const parts = raw.split('.');
                        const integer = parts[0];
                        const decimal = parts[1] ?? '';

                        const formattedInteger = integer.replace(/\B(?=(\d{3})+(?!\d))/g, thousandSeparator);

                        if (raw.endsWith('.') && decimal === '') {
                            $el.value = formattedInteger + decimalSeparator;
                        } else if (decimal !== '') {
                            $el.value = `${formattedInteger}${decimalSeparator}${decimal.slice(0, 3)}`;
                        } else {
                            $el.value = formattedInteger;
                        }
                    });
                }
            JS, [
                '{{decimal}}' => $decimalSeparator,
                '{{thousand}}' => $thousandSeparator,
            ])))
            ->stripCharacters($thousandSeparator)
            ->rules([
                fn(F\TextInput $component): Closure
                => function (string $attribute, $value, Closure $fail) use ($component) {
                    $min = $component->getMinValue();
                    if ((float) $value < $min) {
                        $fail(__("Min") . ': ' . $min);
                    }
                },
            ]);

        return $suffixField != null
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
    function __notes(?string $column = 'notes'): F\Textarea
    {
        return F\Textarea::make($column);
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
if (!function_exists('__x_load_js')) {
    function __x_load_js(array|string $fileIds): HtmlString
    {
        $urls = collect((array) $fileIds)
            ->filter()
            ->map(fn($id) => FilamentAsset::getScriptSrc($id))
            ->filter()
            ->map(fn($url) => "'" . addslashes($url) . "'")
            ->implode(",\n    ");

        return new HtmlString("[{$urls}]");
    }
}

if (!function_exists('__dispatch_order_date_changed_js')) {
    function __dispatch_order_date_changed_js(): string
    {
        return <<<'JS'
            $dispatch('root-date-changed', {
                orderDate: $state ?? undefined,
            });
        JS;
    }
}

if (!function_exists('__toggle_disable_js')) {
    function __toggle_disable_js(array|string $fields): string
    {
        return <<<'JS'
            let isDisabled = $state == '';
            const key = $el.getAttribute('wire:key');
            const baseKey = key.substring(0, key.lastIndexOf('.') + 1);

            const elementKey = baseKey + 'transaction_id';
            const el = document.querySelector(`[wire\\:key="${elementKey}"]`)
                ?.querySelector('.fi-input-wrp-content-ctn');
            console.log(el, isDisabled);
        JS;
    }
}
