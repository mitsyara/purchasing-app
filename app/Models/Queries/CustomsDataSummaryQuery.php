<?php

namespace App\Models\Queries;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class CustomsDataSummaryQuery extends Builder
{
    // Optimized scopes for common queries
    public function forSummaryTable(): Builder
    {
        return $this
            ->select([
                'importer', 
                'customs_data_category_id',
            ])
            ->selectRaw('SUM(total_import) as total_import')
            ->selectRaw('SUM(total_qty) as total_qty') 
            ->selectRaw('SUM(total_value) as total_value')
            ->selectRaw('MAX(is_vett) as is_vett')
            ->groupBy('importer', 'customs_data_category_id');
    }

    public function searchImporter(string $search): Builder
    {
        $search = trim($search);
        
        if (strlen($search) >= 3) {
            // Use FULLTEXT for longer searches with better syntax
            $searchTerm = preg_replace('/[^\w\s]/', '', $search); // Remove special chars
            return $this->whereRaw('MATCH(importer) AGAINST(? IN BOOLEAN MODE)', ["+{$searchTerm}*"]);
        } else {
            // Use LIKE for short searches
            return $this->where('importer', 'LIKE', "%{$search}%");
        }
    }

    public function filterByDateRange(?string $fromDate, ?string $toDate): Builder
    {
        if ($fromDate) {
            $this->where('import_date', '>=', $fromDate);
        }
        if ($toDate) {
            $this->where('import_date', '<=', $toDate);
        }
        
        return $this;
    }

    public function optimizedForFilters(array $filters = []): Builder
    {
        // Apply filters in optimal order for index usage
        return $this
            ->when($filters['null_category'] ?? null, 
                fn($q) => $q->whereNull('customs_data_category_id')
            )
            ->when($filters['customs_data_category_id'] ?? null, 
                fn($q, $ids) => $q->whereIn('customs_data_category_id', $ids)
            )
            ->when($filters['from_date'] ?? null || $filters['to_date'] ?? null,
                fn($q) => $q->filterByDateRange($filters['from_date'] ?? null, $filters['to_date'] ?? null)
            )
            ->when($filters['is_vett'] ?? null,
                fn($q) => $q->where('is_vett', true)
            )
            ->when($filters['importer'] ?? null,
                fn($q, $search) => $q->searchImporter($search)
            );
    }
}
