<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\URL;
use Spatie\SimpleExcel\SimpleExcelWriter;

class CustomsDataExportJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $key) {}

    public function handle(): void
    {
        // ====== LẤY DỮ LIỆU CACHE ======
        $data = Cache::pull($this->key);
        Log::channel('export')->info('Job Started: ' . $this->key, $data ?? []);

        if (!$data) {
            Log::channel('export')->error('No data found in cache for key: ' . $this->key);
            throw new \Exception('No data found for key ' . $this->key);
        }

        // ====== REBUILD QUERY ======
        // $data = [
        //     'key' => $key,
        //     'sessionKey' => $sessionKey,
        //     'connection' => $connection,
        //     'model' => $this->model,
        //     'sql' => $query->toSql(),
        //     'bindings' => $query->getBindings(),
        // ];

        if (isset($data['sql'], $data['bindings'])) {
            $modelClass = $data['model'];
            $sql = $data['sql'];
            $bindings = $data['bindings'];
            /** @var \App\Models\CustomsData $modelClass */
            $query = $modelClass::query()
                ->fromSub(function ($q) use ($sql, $bindings) {
                    $q->selectRaw('*')->fromRaw("({$sql}) as sub", $bindings);
                }, 'subquery');
        }


        // ====== XỬ LÝ CỘT EXPORT ======
        $columns = (new \App\Models\CustomsData())->getFillable();

        $excluded = ['customs_data_category_id'];

        $mapped = collect($columns)
            ->reject(fn($col) => in_array($col, $excluded))
            ->mapWithKeys(fn($col) => [$col => Str::of($col)->headline()->toString()])
            ->toArray();

        $query->select(array_keys($mapped));

        // ====== TẠO FILE EXPORT ======
        $exportDir = storage_path('app/dlhq_exports');
        if (!is_dir($exportDir)) {
            mkdir($exportDir, 0755, true);
        }

        $filename = 'dlhq_' . now()->format('Ymd_His') . '.xlsx';
        $filePath = $exportDir . '/' . $filename;

        $writer = null;

        try {
            // ====== TẠO WRITER & GHI FILE ======
            $writer = SimpleExcelWriter::create($filePath)
                ->addHeader(array_values($mapped));

            $orderCol = method_exists($query, 'getModel')
                ? ($query->getModel()?->getKeyName() ?? array_key_first($mapped))
                : array_key_first($mapped);

            $query->orderBy($orderCol)->chunk(1000, function ($records) use ($writer, $mapped) {
                $rows = collect($records)->map(function ($record) use ($mapped) {
                    return collect($mapped)->keys()
                        ->mapWithKeys(fn($col) => [$col => $record->{$col} ?? null])
                        ->toArray();
                })->toArray();

                $writer->addRows($rows);
            });

            // ====== ĐÓNG FILE ======
            $writer->close();

            // ====== TẠO LINK DOWNLOAD ======
            $relativePath = 'dlhq_exports/' . basename($filePath);
            $signedUrl = URL::temporarySignedRoute(
                'exports.download',
                now()->addMinutes(5),
                ['path' => $relativePath]
            );

            // Lưu kết quả vào cache để FE lấy
            Cache::put(
                "export-result-{$data['sessionKey']}",
                [
                    'url' => $signedUrl,
                    'file' => basename($filePath),
                ],
                now()->addMinutes(5)
            );

            // Schedule xoá file sau 5 phút
            \App\Jobs\CleanUpCustomsDataExportFileJob::dispatch($filePath)->delay(now()->addMinutes(5));
            Log::channel('export')->info("Export completed: {$filePath}", ['url' => $signedUrl]);
        } catch (\Throwable $e) {
            // ====== XỬ LÝ LỖI ======
            Log::channel('export')->error('Export failed', [
                'key' => $this->key,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Nếu có writer, đóng nó lại để giải phóng file handle
            if ($writer) {
                try {
                    $writer->close();
                } catch (\Throwable $inner) {
                    Log::channel('export')->warning('Failed to close writer after error', [
                        'error' => $inner->getMessage(),
                    ]);
                }
            }

            // Nếu file tồn tại và chưa hoàn thiện, xóa luôn
            if (file_exists($filePath)) {
                @unlink($filePath);
            }

            throw $e;
        }
    }
}
