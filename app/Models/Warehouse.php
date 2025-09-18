<?php

namespace App\Models;

use App\Observers\WarehouseObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;

#[ObservedBy([WarehouseObserver::class])]
class Warehouse extends Model
{
    protected $fillable = [
        'warehouse_code',
        'warehouse_name',
        'warehouse_address',
        'region',
    ];

    protected $casts = [
        'region' => \App\Enums\RegionEnum::class,
    ];

    // Helper methods
    public function setWarehouseCode(): static
    {
        $words = preg_split('/\s+/', trim($this->warehouse_name));

        // Nếu từ đầu tiên là "Kho"
        if (!empty($words) && strcasecmp($words[0], 'Kho') === 0) {
            array_shift($words);
        }

        // Nếu từ cuối cùng là "Warehouse"
        if (!empty($words) && strcasecmp(end($words), 'Warehouse') === 0) {
            array_pop($words);
        }

        // Lấy chữ cái đầu của từng từ
        $letters = array_map(fn($w) => mb_substr($w, 0, 1), $words);

        $base = 'WH-' . strtoupper(implode('', $letters));
        $code = $base;

        $i = 1;
        while (
            static::where('warehouse_code', $code)
            ->where('id', '!=', $this->id) // tránh đụng chính nó khi update
            ->exists()
        ) {
            $code = $base . $i;
            $i++;
        }

        $this->updateQuietly([
            'warehouse_code' => $code,
        ]);

        return $this;
    }
}
