<?php

namespace App\Services\Project;

use App\Models\Project;

class ProjectService
{
    public function __construct(public Project $project)
    {
        // Calculate Totals
        $importTotalValue = $project->projectItems()->sum('value');
        $importTotalContractValue = $project->projectItems()->sum('contract_value');

        $project->updateQuietly([
            'import_total_value' => $importTotalValue,
            'import_total_contract_value' => $importTotalContractValue,
        ]);

        // Calculate Foreign
        $isForeign = $project->company->country_id !== $project->supplier->country_id;
        $project->updateQuietly([
            'is_foreign' => $isForeign,
        ]);
    }
}
