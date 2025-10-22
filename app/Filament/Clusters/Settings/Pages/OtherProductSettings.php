<?php

namespace App\Filament\Clusters\Settings\Pages;

use App\Filament\Clusters\Settings\SettingsCluster;
use App\Filament\Schemas\SettingsClusters\PackingSchema;
use App\Filament\Schemas\SettingsClusters\UnitSchema;
use App\Filament\Schemas\SettingsClusters\VatSchema;
use BackedEnum;
use Filament\Notifications\Notification;
use App\Filament\BasePage as Page;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class OtherProductSettings extends Page
{
    protected string $view = 'filament.clusters.settings.pages.other-product-settings';

    protected static ?int $navigationSort = 9;

    protected static ?string $cluster = SettingsCluster::class;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('Product Settings');
    }

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    // vat, unit, packing
    public ?string $activePanel = 'vat';

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('create_new')
                ->model(fn() => match ($this->activePanel) {
                    'vat' => \App\Models\Vat::class,
                    'unit' => \App\Models\Unit::class,
                    'packing' => \App\Models\Packing::class,
                    default => null,
                })
                ->schema(fn($schema) => match ($this->activePanel) {
                    'vat' => VatSchema::configure($schema),
                    'unit' => UnitSchema::configure($schema),
                    'packing' => PackingSchema::configure($schema),
                    default => [],
                })
                ->action(function (array $data): void {
                    /** @var Model */
                    $model = match ($this->activePanel) {
                        'vat' => \App\Models\Vat::class,
                        'unit' => \App\Models\Unit::class,
                        'packing' => \App\Models\Packing::class,
                        default => null,
                    };
                    if (!$model) return;

                    $bool = $model::create($data);
                    if (!$bool) return;

                    Notification::make()
                        ->title(\Illuminate\Support\Str::of($model)
                            ->afterLast('\\')
                            ->headline()
                            ->toString() . ' Created!')
                        ->success()
                        ->send();

                    $this->dispatch('refresh-custom-table');
                })
                ->modalWidth(Width::Small)
                ->modalSubmitActionLabel('Create'),
        ];
    }
}
