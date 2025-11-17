<?php

namespace App\Filament\Clusters\Settings\Resources\Users\Pages;

use App\Filament\Clusters\Settings\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageUsers extends ManageRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->before(function (array $data): array {
                    if ($data['email_verified_at'] ?? false) {
                        $data['email_verified_at'] = now();
                    }
                    if ($data['phone_verified_at'] ?? false) {
                        $data['phone_verified_at'] = now();
                    }
                    return $data;
                }),
        ];
    }
}
