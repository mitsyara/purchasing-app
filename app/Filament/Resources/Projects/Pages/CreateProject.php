<?php

namespace App\Filament\Resources\Projects\Pages;

use App\Filament\Resources\Projects\ProjectResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProject extends CreateRecord
{
    protected static string $resource = ProjectResource::class;

    protected function afterCreate(): void
    {
        // Get Project
        /** @var \App\Models\Project */
        $record = $this->getRecord();

        // Log the user who created the record
        $record->updateQuietly([
            'created_by' => auth()->id(),
        ]);

        // Call Services
        new \App\Services\Project\ProjectService($record);
    }
}
