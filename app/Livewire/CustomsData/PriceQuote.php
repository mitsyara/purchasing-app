<?php

namespace App\Livewire\CustomsData;

use App\Models\Company;
use App\Models\Contact;
use App\Models\Product;
use DefStudio\SearchableInput\DTO\SearchResult;
use DefStudio\SearchableInput\Forms\Components\SearchableInput;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Livewire\Component;

use Filament\Forms\Components as F;
use Filament\Schemas\Components as S;
use Filament\Actions\Action;
use Filament\Support\Assets\Js;
use Filament\Support\Icons\Heroicon;

class PriceQuote extends Component implements HasSchemas, HasActions
{
    use InteractsWithSchemas, InteractsWithActions;

    public ?array $data;
    public array $defaultTerms = [
        'Báo giá có hiệu lực trong vòng 07 ngày.',
        'Thanh toán trong vòng 30 ngày kể từ ngày xuất hóa đơn.',
    ];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function render()
    {
        return view('livewire.customs-data.price-quote');
    }

    // ================= Form ================= //

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            S\Section::make('Thông tin Cơ bản')
                ->schema($this->basicInfo())
                ->compact()
                ->collapsible(),

            ...$this->productsInfo(),
        ])
            ->columns(['default' => 1])
            ->statePath('data');
    }

    public function basicInfo(): array
    {
        return [
            S\Flex::make([
                F\DatePicker::make('date')
                    ->label('Ngày')
                    ->default(today())
                    ->maxDate(today())
                    ->required(),

                F\TextInput::make('quote_no')
                    ->label('Số báo giá')
                    ->required(),

                F\Select::make('currency')
                    ->label('Tiền tệ')
                    ->options([
                        'USD' => 'USD',
                        'VND' => 'VND',
                        'EUR' => 'EUR',
                    ])
                    ->default('VND')
                    ->selectablePlaceholder(false)
                    ->required(),
            ]),

            S\Group::make([
                F\TextInput::make('quote_by')
                    ->label('Người báo giá'),

                S\Group::make([
                    F\TextInput::make('quote_by_phone')
                        ->label('Điện thoại')
                        ->tel(),
                    F\TextInput::make('quote_by_email')
                        ->label('Email')
                        ->email(),
                ])
                    ->columns(['default' => 2]),

                SearchableInput::make('quote_by_company')
                    ->label('Công ty (mình)')
                    ->searchUsing(function (string $search) {
                        return Company::query()
                            ->where('company_name', 'like', "%$search%")
                            ->orWhere('company_code', 'like', "%$search%")
                            ->limit(10)
                            ->pluck('company_name')
                            ->values()
                            ->toArray();
                    })
                    ->columnSpanFull()
                    ->required(),

            ])
                ->columns(2),

            S\Group::make([
                F\TextInput::make('quote_to')
                    ->label('Người nhận'),
                S\Group::make([
                    F\TextInput::make('quote_to_phone')
                        ->label('Điện thoại')
                        ->tel(),
                    F\TextInput::make('quote_to_email')
                        ->label('Email')
                        ->email(),
                ])
                    ->columns(['default' => 2]),

                SearchableInput::make('quote_to_company')
                    ->label('Công ty (khách)')
                    ->searchUsing(function (string $search) {
                        return Contact::query()->where('is_cus', true)
                            ->where('contact_name', 'like', "%$search%")
                            ->orWhere('contact_code', 'like', "%$search%")
                            ->limit(10)
                            ->pluck('contact_name')
                            ->values()
                            ->toArray();
                    })
                    ->columnSpanFull()
                    ->required(),
            ])
                ->columns(2),

            S\Group::make([
                F\Repeater::make('terms_and_conditions')
                    ->label('Điều khoản và Điều kiện')
                    ->afterStateHydrated(fn(F\Field $component)
                    => $component->state(collect($this->defaultTerms)
                        ->map(fn($term) => ['terms' => $term])
                        ->toArray()))
                    ->simple(
                        F\TextInput::make('terms')
                            ->label('Điều khoản')
                            ->required()
                    )
                    ->defaultItems(2)
                    ->minItems(0)
                    ->addActionLabel('Thêm điều khoản'),

                F\Textarea::make('notes')
                    ->label('Ghi chú')
                    ->rows(3),
            ])
                ->columns(),

        ];
    }

    public function productsInfo(): array
    {
        return [
            F\Repeater::make('products')
                ->label('Thông tin Sản phẩm')
                ->table([
                    F\Repeater\TableColumn::make('Mô tả sản phẩm')
                        ->markAsRequired(),
                    F\Repeater\TableColumn::make('Số lượng')
                        ->width('120px')
                        ->markAsRequired(),
                    F\Repeater\TableColumn::make('Đơn giá')
                        ->width('150px')
                        ->markAsRequired(),
                    F\Repeater\TableColumn::make('Thuế')
                        ->width('80px')
                        ->markAsRequired(),
                ])
                ->schema([
                    SearchableInput::make('product_description')
                        ->label('Mô tả sản phẩm')
                        ->searchUsing(function (string $search) {
                            return Product::query()
                                ->where('product_full_name', 'like', "%$search%")
                                ->orWhere('product_code', 'like', "%$search%")
                                ->limit(15)
                                ->pluck('product_full_name', 'id')
                                ->values()
                                ->toArray();
                        })
                        ->required(),

                    __number_field('quantity')
                        ->label('Số lượng')
                        ->numeric()
                        ->required(),

                    __number_field('unit_price')
                        ->label(fn($get) => 'Đơn giá (' . $get('currency') . ')')
                        ->numeric()
                        ->required(),

                    F\TextInput::make('vat')
                        ->label('Thuế')
                        ->numeric()
                        ->minValue(0)
                        ->required(),
                ])
                ->defaultItems(1)
                ->minItems(1)
                ->compact()
                ->addActionLabel('Thêm sản phẩm'),
        ];
    }

    // ================= Actions ================= //
    public function resetFormAction(): Action
    {
        return Action::make('resetForm')
            ->label('Đặt lại')
            ->action(function (): void {
                $this->form->fill();
            })
            ->icon(Heroicon::OutlinedArrowPath)
            ->color('warning')
            ->outlined();
    }

    public function printAction(): Action
    {
        return Action::make('print')
            ->label('In Báo giá')
            ->outlined()
            ->color('info')
            ->icon(Heroicon::OutlinedPrinter)
            ->action(function () {
                $data = $this->form->getState();
                $route = route('customs-data.price-quote.print', ['data' => $data]);
                $this->dispatch('print-price-pdf', url: $route);
            })
        ;
    }
}
