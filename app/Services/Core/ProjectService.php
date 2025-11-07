<?php

namespace App\Services\Core;

use App\Models\Project;
use App\Models\ProjectShipment;

/**
 * Service xử lý business logic cho Project
 */
class ProjectService
{
    /**
     * Tính toán và cập nhật totals cho project
     */
    public function updateProjectInfo(int $projectId): void
    {
        $project = Project::find($projectId);

        if (!$project) return;

        // Log the user who updated the record
        if ($project->wasChanged([
            'project_status',
            'project_date',
            'project_number',
            'company_id',
            'supplier_id',
            'supplier_contract_id',
            'import_port_id',
            'staff_buy_id',
            'staff_approved_id',
            'staff_docs_id',
            'staff_declarant_id',
            'staff_sales_id',
            'etd_min',
            'etd_max',
            'eta_min',
            'eta_max',
            'is_skip_invoice',
            'incoterm',
            'currency',
            'pay_term_delay_at',
            'pay_term_days',
            'notes',
        ])) {
            $project->updateQuietly(['updated_by' => auth()->id()]);
        }

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

    /**
     * Sync thông tin project shipment
     */
    public function syncProjectShipmentInfo(int $shipmentId): void
    {
        $shipment = ProjectShipment::find($shipmentId);
        if (!$shipment || !$shipment->project_id) {
            return;
        }

        $project = $shipment->project;
        if (!$project) {
            return;
        }

        // Sync basic shipment information
        $shipment->updateQuietly([
            'currency' => $project->currency,
        ]);

        // Sync shipment lines info
        $this->syncProjectShipmentLinesInfo($shipmentId);

        // Update shipment totals
        $this->updateProjectShipmentTotals($shipmentId);
    }

    /**
     * Sync project shipment lines information
     */
    public function syncProjectShipmentLinesInfo(int $shipmentId): void
    {
        $shipment = ProjectShipment::find($shipmentId);
        if (!$shipment || !$shipment->project_id) {
            return;
        }

        $project = $shipment->project;
        if (!$project) {
            return;
        }

        $projectItems = $project->projectItems()
            ->get();

        foreach ($projectItems as $item) {
            $shipment->projectShipmentItems()->updateOrCreate(
                ['product_id' => $item->product_id],
                [
                    'qty' => $item->qty,
                    'unit_price' => $item->unit_price,
                    'contract_price' => $item->contract_price,
                ]
            );
        }
    }

    /**
     * Update project shipment totals
     */
    public function updateProjectShipmentTotals(int $shipmentId): void
    {
        $shipment = ProjectShipment::find($shipmentId);
        if (!$shipment) {
            return;
        }

        $totalValue = $shipment->projectShipmentItems()->sum('value');
        $totalContractValue = $shipment->projectShipmentItems()->sum('contract_value');
        $totalExtraCost = collect($shipment->extra_costs)->sum() ?? 0;

        $totalQty = $shipment->projectShipmentItems()->sum('qty');
        $averageCost = null;
        if ($totalQty > 0) {
            $averageCost = $totalExtraCost / $totalQty;
        }

        $shipment->updateQuietly([
            'total_value' => $totalValue,
            'total_contract_value' => $totalContractValue,
            'total_extra_cost' => $totalExtraCost,
            'average_cost' => $averageCost,
        ]);
    }

    /**
     * Mark project shipment as delivered
     */
    public function markProjectShipmentDelivered(int $shipmentId): void
    {
        $shipment = ProjectShipment::find($shipmentId);
        if (!$shipment) {
            throw new \InvalidArgumentException("Shipment not found");
        }

        if (in_array($shipment->shipment_status, [
            \App\Enums\ShipmentStatusEnum::Cancelled,
            \App\Enums\ShipmentStatusEnum::Delivered,
        ])) {
            throw new \InvalidArgumentException("Cannot mark shipment as arrived. Current status: " . $shipment->shipment_status?->value);
        }

        $shipment->updateQuietly([
            'shipment_status' => \App\Enums\ShipmentStatusEnum::Delivered,
        ]);
    }
}
