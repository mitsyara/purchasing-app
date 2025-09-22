<?php

use Illuminate\Database\Eloquent\Relations\Pivot;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;


// ********** Global Helpers **********
if (!function_exists('get_resource_permissions')) {
    function get_resource_permissions(string $resource): string
    {
        return Str::of($resource)
            ->afterLast('Resources\\')
            ->before('Resource')
            ->replace('\\', '')
            ->snake()
            ->replace('_', '::');
    }
}
if (!function_exists('model_to_path')) {
    function model_to_path(string|\Illuminate\Database\Eloquent\Model|null $model): string
    {
        return $model
            ? Str::of($model)->afterLast('Models\\')->snake()
            : 'default';
    }
}

if (!function_exists('mb_proper')) {
    function mb_proper(string $str): string
    {
        return mb_convert_case(mb_strtolower($str), MB_CASE_TITLE, "UTF-8");
    }
}

if (!function_exists('tags_to_array')) {
    function tags_to_array(string $str, ?string $separator = ','): array
    {
        $str = str_replace([' ', ','], $separator, $str);
        return explode($separator, $str);
    }
}

if (!function_exists('lead_zeros')) {
    function lead_zeros(int $number, ?int $length = 3, ?int $groupSize = 3, ?string $separator = '.'): string
    {
        // Bước 1: Pad số với số 0 bên trái
        $padded = str_pad($number, $length, '0', STR_PAD_LEFT);
        // Bước 2: Tách thành các nhóm từ phải sang trái
        {
            // Đảo ngược để dễ tách nhóm
            $reversed = strrev($padded);
            $grouped = implode($separator, str_split($reversed, $groupSize));
            // Đảo ngược lại để đúng thứ tự
            $formatted = strrev($grouped);
        }
        return $formatted;
    }
}

if (!function_exists('trim_decimal')) {
    function trim_decimal(string|float|int|null $number = null): ?string
    {
        $fmt = new \NumberFormatter(app()->getLocale(), \NumberFormatter::DECIMAL);
        $decimalSeparator = $fmt->getSymbol(\NumberFormatter::DECIMAL_SEPARATOR_SYMBOL);
        // Convert to String, then Remove unnesscesary 0 and dot
        return $number ? rtrim(rtrim((string) $number, '0'), '.') : null;
    }
}

if (!function_exists('__number_string_converter_vi')) {
    function __number_string_converter_vi(string|float|int|null $value, bool $toString = true): string|float|null
    {
        if (is_null($value)) return null;

        if ($toString) {
            // Convert float|int -> string 
            return number_format((float) $value, 0, '.', ',');
        }

        // Convert string -> float
        return (float) str_replace(',', '', $value);
    }
}

// ********** App's global helpers **********

if (!function_exists('auth')) {
    function auth(): \Illuminate\Contracts\Auth\Guard|\Illuminate\Contracts\Auth\StatefulGuard
    {
        return app('auth');
    }
}

if (!function_exists('debug_log')) {
    function debug_log(string $message, mixed $context = null): void
    {
        \Illuminate\Support\Facades\Log::channel('debug_log')
            ->debug($message, $context);
    }
}

if (!function_exists('__get_all_models')) {
    function __get_all_models(?bool $include_pivot = true): Collection
    {
        $models = collect(File::allFiles(app_path()))
            ->map(function ($item) {
                $path = $item->getRelativePathName();
                $class = sprintf(
                    '\%s%s',
                    app()->getInstance()->getNamespace(),
                    strtr(substr($path, 0, strrpos($path, '.')), '/', '\\')
                );
                return $class;
            })
            ->filter(function ($class) {
                $valid = false;
                if (class_exists($class)) {
                    $reflection = new \ReflectionClass($class);
                    $valid = $reflection->isSubclassOf(\Illuminate\Database\Eloquent\Model::class) &&
                        !$reflection->isAbstract();
                }
                return $valid;
            })
            ->when(
                $include_pivot === false,
                fn(Collection $collection): Collection =>
                $collection->filter(function ($class) {
                    $valid = false;
                    if (class_exists($class)) {
                        $reflection = new \ReflectionClass($class);
                        $valid = !$reflection->isSubclassOf(Pivot::class) &&
                            !$reflection->isAbstract();
                    }
                    return $valid;
                })
            );
        return $models->values();
    }
}
if (!function_exists('__get_model_with_files_column')) {
    function __get_model_with_files_column(?bool $groupped = false): array
    {
        $results = [];
        foreach (__get_all_models() as $modelClass) {
            $model = new $modelClass;
            if (property_exists($model, 'fillable')) {
                $field = collect($model->getFillable())
                    ->first(fn($field) => in_array($field, [
                        DB_FILE_COLUMN,
                        DB_FILES_COLUMN,
                    ]));
                if ($field) {
                    $results[$modelClass] = $field;
                }
            }
        }
        foreach ($results as $model => $column) {
            $reversed[$column][] = $model;
        }
        return $groupped ? $reversed : $results;
    }
}

if (!function_exists('__tables_check')) {
    function __tables_check(): array
    {
        $results = [];
        $models = __get_all_models()->toArray();
        $ignore = [
            \App\Models\User::class => [
                'email_verified_at',
                'remember_token',
            ],
            // \App\Models\Permission::class => [
            //     'name',
            //     'guard_name',
            // ],
            // \App\Models\Role::class => [
            //     'name',
            //     'guard_name',
            // ],
            // // pivot table
            // \App\Models\CompanyContact::class => [
            //     'company_id',
            //     'contact_id',
            // ],
        ];
        foreach ($models as $model) {
            /** @var \Illuminate\Database\Eloquent\Model $instance */
            $instance = new $model;
            $table = $instance->getTable();
            if (!Schema::hasTable($table)) {
                Log::channel(config('logging.custom_log'))
                    ->warning("Schema check: {$table} did not exists!");
                continue;
            }
            $columns = Schema::getColumnListing($table);
            $fillable = $instance->getFillable();
            $excluded = [
                // Model default columns
                'id',
                'created_at',
                'updated_at',
                'deleted_at',
                // Virtual columns
                'value',
                'fake_value',
                'remaining_value',
            ];
            // add Ignore column if model is in $ignore
            $ignoreForModel = $ignore[ltrim($model, '\\')] ?? [];
            $excluded = array_merge($excluded, $ignoreForModel);
            $unlisted = array_diff($columns, array_merge($fillable, $excluded));
            if (!empty($unlisted)) {
                $results[$model] = array_values($unlisted);
            }
        }
        return $results;
    }
}


if (!function_exists('__options')) {
    function __options(string $model, ?array $conditions = [], ?string $suffix = null, ?bool $re_cache = false): ?\Illuminate\Support\Collection
    {
        if (!is_subclass_of($model, \Illuminate\Database\Eloquent\Model::class)) {
            throw new \InvalidArgumentException("{$model} is not a valid Eloquent model.");
        }

        $cache_key = __FUNCTION__ . '_' . model_to_path($model);

        if (filled($cache_key)) {
            $cache_key = $cache_key . '.' . $suffix;
        }

        if ($re_cache) {
            Cache::forget($cache_key);
        }

        return Cache::rememberForever($cache_key, function () use ($model, $conditions) {
            /** @var  \Illuminate\Database\Eloquent\Builder $query */
            $query = $model::query();
            foreach ($conditions as $key => $value) {
                $query->where($key, $value);
            }
            return $query->get();
        });
    }
}
