<?php

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Roles\RoleResource;
use App\Models\Role;
use Filament\Actions as A;
use Filament\Resources\Pages\ManageRecords;

class ManageRoles extends ManageRecords
{
    protected static string $resource = RoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            A\CreateAction::make()
                ->mutateDataUsing(function (array $data, A\Action $action): array {
                    $action->after(fn(Role $record) => RoleResource::afterSave($record, $data));
                    return RoleResource::mutateData($data);
                })
                ->modal()->slideOver()
                ->modalWidth(\Filament\Support\Enums\Width::ScreenTwoExtraLarge),
        ];
    }
}
