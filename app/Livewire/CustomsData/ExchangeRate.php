<?php

namespace App\Livewire\CustomsData;

use App\Services\VcbExchangeRatesService;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;

class ExchangeRate extends Component implements HasTable, HasSchemas, HasActions
{
    use InteractsWithTable, InteractsWithSchemas, InteractsWithActions;

    public ?array $data = null;
    public ?string $timestamp = null;

    public function mount(): void
    {
        $this->fetchRate();
    }

    #[\Livewire\Attributes\On('dateChanged')]
    public function fetchRate(?string $date = null): void
    {
        // Bước 1: gọi fetch để lấy timestamp mới nhất (chỉ metadata)
        $probe = VcbExchangeRatesService::fetch($date);
        $timestamp = $probe['timestamp'] ?? null;

        if (!$timestamp) {
            // Nếu API không trả timestamp, vẫn lấy data mới
            $rates = $probe;
        } else {
            // Tạo cache key theo timestamp
            $cacheKey = 'vcb_rates_' . str_replace([' ', ':'], ['_', '-'], $timestamp);

            // Bước 2: thử lấy cache
            $rates = Cache::get($cacheKey);

            // Bước 3: nếu chưa có thì mới gọi API thật sự và cache lại
            if (!$rates) {
                $rates = $probe; // dùng luôn kết quả fetch() vừa rồi
                Cache::put($cacheKey, $rates, now()->addDay()); // lưu cache 1 ngày
            }
        }

        // Chuẩn bị dữ liệu cho bảng
        $result = collect($rates)
            ->except('timestamp')
            ->filter(fn($v) => collect($v)->every(fn($val) => $val != 0))
            ->map(fn($v, $k) => array_merge(['curr' => $k], $v))
            ->values()
            ->sortBy('curr')
            ->all();

        $this->timestamp = $rates['timestamp'] ?? null;
        $this->data = $result;

        $this->resetTable();
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(fn(): array => $this->data ?? [])
            ->columns([
                TextColumn::make('curr')
                    ->size(\Filament\Support\Enums\TextSize::ExtraSmall)
                    ->label('Loại tiền tệ')
                    ->sortable(),

                TextColumn::make('cash')
                    ->size(\Filament\Support\Enums\TextSize::ExtraSmall)
                    ->label('Mua Tiền mặt')
                    ->sortable()
                    ->money('VND', locale: 'vi'),

                TextColumn::make('transfer')
                    ->size(\Filament\Support\Enums\TextSize::ExtraSmall)
                    ->label('Mua Chuyển khoản')
                    ->sortable()
                    ->money('VND', locale: 'vi'),

                TextColumn::make('sell')
                    ->size(\Filament\Support\Enums\TextSize::ExtraSmall)
                    ->label('Bán ra')
                    ->sortable()
                    ->money('VND', locale: 'vi'),
            ])
            ->striped();
    }

    public function render()
    {
        return view('livewire.customs-data.exchange-rate');
    }
}
