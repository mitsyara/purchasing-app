<?php

namespace App\Livewire\CustomsData;

use App\Services\Common\ExchangeRateService;
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

    /**
     * Fetch exchange rates for a specific date (or today if no date is provided).
     * - Caches results to minimize API calls.
     * - Cache duration: 30 minutes for today's rates, 1 day for older dates
     */
    #[\Livewire\Attributes\On('dateChanged')]
    public function fetchRate(?string $date = null): void
    {
        // Determine date
        $dateStr = $date ?: now()->format('Y-m-d');
        $dateObj = \Carbon\Carbon::createFromFormat('Y-m-d', $dateStr);
        $isToday = $dateObj->isToday();

        // Create cache key by date
        $cacheKey = 'vcb_exrates_' . $dateObj->format('Ymd');

        // Try to get transformed cached data
        $result = Cache::get($cacheKey);

        // If not cached, fetch new data
        if (!$result) {
            $probe = ExchangeRateService::fetch($dateStr);
            $this->timestamp = $probe['timestamp'] ?? null;

            // Transform before caching
            $result = $this->transformData($probe);

            // TTL: today => 30 minutes, older => 1 day
            $ttl = $isToday ? now()->addMinutes(30) : now()->addDay();
            Cache::put($cacheKey, $result, $ttl);
        }

        // Update Livewire data
        $this->data = $result;
        $this->resetTable();
    }

    /**
     * Transform raw rates data into a structured array suitable for table display.
     * - Excludes entries with any zero values.
     * - Sorts by currency code.
     */
    public function transformData(array $rates): array
    {
        return collect($rates)
            ->except('timestamp')
            ->filter(fn($v) => collect($v)->every(fn($val) => $val != 0))
            ->map(fn($v, $k) => array_merge(['curr' => $k], $v))
            ->sortBy('curr')
            ->values()
            ->all();
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
                    ->color('success')
                    ->sortable()
                    ->money('VND', locale: 'vi'),

                TextColumn::make('transfer')
                    ->size(\Filament\Support\Enums\TextSize::ExtraSmall)
                    ->label('Mua Chuyển khoản')
                    ->color('info')
                    ->sortable()
                    ->money('VND', locale: 'vi'),

                TextColumn::make('sell')
                    ->size(\Filament\Support\Enums\TextSize::ExtraSmall)
                    ->label('Bán ra')
                    ->color('danger')
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
