<?php

namespace App\Services\Project;

use App\Models\Project;
use App\Models\ProjectItem;
use App\Models\ProjectShipment;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;

/**
 * Service xử lý business logic cho Project
 */
class ProjectService
{
    /**
     * Tạo project mới
     */
    public function create(array $data): Project
    {
        // Validate dữ liệu
        $this->validateProjectData($data);
        
        // Set user tạo
        if (auth()->check()) {
            $data['created_by'] = auth()->id();
        }

        return DB::transaction(function () use ($data) {
            // Tách project items khỏi data chính
            $projectItems = $data['items'] ?? [];
            unset($data['items']);
            
            // Tạo project
            $project = Project::create($data);
            
            // Tạo project items nếu có
            if (!empty($projectItems)) {
                $this->createProjectItems($project, $projectItems);
            }
            
            return $project->load(['items.product', 'company']);
        });
    }

    /**
     * Cập nhật project
     */
    public function update(int $id, array $data): bool
    {
        $project = Project::findOrFail($id);
        
        // Validate dữ liệu
        $this->validateProjectData($data, $id);
        
        // Set user cập nhật
        if (auth()->check()) {
            $data['updated_by'] = auth()->id();
        }

        return DB::transaction(function () use ($project, $data) {
            // Tách project items khỏi data chính
            $projectItems = $data['items'] ?? [];
            unset($data['items']);
            
            // Cập nhật project
            $result = $project->update($data);
            
            // Cập nhật project items nếu có
            if (!empty($projectItems)) {
                $this->updateProjectItems($project, $projectItems);
            }
            
            return $result;
        });
    }

    /**
     * Xóa project
     */
    public function delete(int $id): bool
    {
        $project = Project::findOrFail($id);
        
        return DB::transaction(function () use ($project) {
            // Xóa project items trước
            $project->items()->delete();
            
            // Xóa project shipments
            $project->shipments()->delete();
            
            // Xóa project
            return $project->delete();
        });
    }

    /**
     * Lấy project theo ID
     */
    public function findById(int $id): ?Project
    {
        return Project::with(['items.product', 'shipments', 'company'])
            ->find($id);
    }

    /**
     * Lấy projects theo company
     */
    public function getProjectsByCompany(int $companyId): Collection
    {
        return Project::where('company_id', $companyId)
            ->with(['items.product'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Tìm kiếm projects
     */
    public function search(array $criteria): Collection
    {
        $query = Project::with(['items.product', 'company']);

        if (!empty($criteria['project_name'])) {
            $query->where('project_name', 'LIKE', '%' . $criteria['project_name'] . '%');
        }

        if (!empty($criteria['project_code'])) {
            $query->where('project_code', 'LIKE', '%' . $criteria['project_code'] . '%');
        }

        if (!empty($criteria['company_id'])) {
            $query->where('company_id', $criteria['company_id']);
        }

        if (!empty($criteria['status'])) {
            $query->where('status', $criteria['status']);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Tính tổng giá trị project
     */
    public function calculateProjectTotal(Project $project): float
    {
        return $project->items->sum(fn($item) => $item->qty * $item->unit_price);
    }

    /**
     * Validate dữ liệu project
     */
    private function validateProjectData(array $data, ?int $excludeId = null): void
    {
        $errors = [];

        // Kiểm tra tên project
        if (empty($data['project_name'])) {
            $errors['project_name'] = 'Tên dự án là bắt buộc.';
        }

        // Kiểm tra company
        if (empty($data['company_id'])) {
            $errors['company_id'] = 'Công ty là bắt buộc.';
        }

        // Kiểm tra project code nếu có
        if (!empty($data['project_code'])) {
            $query = Project::where('project_code', $data['project_code']);
            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }
            if ($query->exists()) {
                $errors['project_code'] = 'Mã dự án đã tồn tại.';
            }
        }

        if (!empty($errors)) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * Tạo project items
     */
    private function createProjectItems(Project $project, array $items): void
    {
        foreach ($items as $itemData) {
            $itemData['project_id'] = $project->id;
            ProjectItem::create($itemData);
        }
    }

    /**
     * Cập nhật project items
     */
    private function updateProjectItems(Project $project, array $items): void
    {
        // Xóa tất cả items cũ
        $project->items()->delete();
        
        // Tạo lại items mới
        $this->createProjectItems($project, $items);
    }

    /**
     * Tạo project shipment
     */
    public function createShipment(int $projectId, array $data): ProjectShipment
    {
        $project = Project::findOrFail($projectId);
        
        $data['project_id'] = $projectId;
        $data['company_id'] = $project->company_id;
        $data['created_by'] = auth()->id();
        
        return ProjectShipment::create($data);
    }

    /**
     * Lấy project shipments
     */
    public function getProjectShipments(int $projectId): Collection
    {
        return ProjectShipment::where('project_id', $projectId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Update project info (backward compatibility)
     */
    public function updateProjectInfo(int $projectId): void
    {
        $project = Project::findOrFail($projectId);
        
        // Logic cập nhật thông tin project
        $totalAmount = $this->calculateProjectTotal($project);
        
        $project->update([
            'total_amount' => $totalAmount,
            'updated_at' => now(),
        ]);
    }

    /**
     * Sync project shipment info (backward compatibility)
     */
    public function syncProjectShipmentInfo(int $shipmentId): void
    {
        $shipment = ProjectShipment::findOrFail($shipmentId);
        
        // Logic đồng bộ thông tin project shipment
        $shipment->update([
            'updated_at' => now(),
        ]);
    }
}