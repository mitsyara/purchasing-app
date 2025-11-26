<?php

namespace App\Filament\Resources\InventoryAdjustments\Pages;

use App\Filament\Resources\InventoryAdjustments\Helpers\InventoryAdjustmentResourceHelper;
use App\Filament\Resources\InventoryAdjustments\InventoryAdjustmentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageInventoryAdjustments extends ManageRecords
{
    protected static string $resource = InventoryAdjustmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->slideOver()
                ->modalWidth(\Filament\Support\Enums\Width::FiveExtraLarge)
                ->mutateDataUsing(fn(CreateAction $action, array $data): array
                => app(InventoryAdjustmentResourceHelper::class)->syncData($action, $data)),

        ];
    }
}
