<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use LogicException;

class BusinessCodeGenerator
{
    private const MAX_COLLISION_RETRIES = 1000;

    /**
     * @template TModel of Model
     *
     * @param  class-string<TModel>  $modelClass
     * @param  array<string, mixed>  $attributes
     * @return TModel
     */
    public function create(
        string $modelClass,
        string $column,
        string $prefix,
        array $attributes,
    ): Model {
        return DB::transaction(function () use ($modelClass, $column, $prefix, $attributes): Model {
            $model = $modelClass::query()->create([
                ...$attributes,
                $column => 'TMP-'.Str::uuid(),
            ]);

            $sequence = (int) $model->getKey();

            for ($attempt = 0; $attempt < self::MAX_COLLISION_RETRIES; $attempt++) {
                $candidate = sprintf('%s-%03d', $prefix, $sequence + $attempt);

                try {
                    $model->forceFill([$column => $candidate])->save();

                    return $model;
                } catch (QueryException $exception) {
                    if (! $this->isUniqueConstraintViolation($exception)) {
                        throw $exception;
                    }
                }
            }

            throw new LogicException("Kode unik untuk {$modelClass} tidak dapat dibuat.");
        }, 3);
    }

    private function isUniqueConstraintViolation(QueryException $exception): bool
    {
        return in_array((string) ($exception->errorInfo[0] ?? $exception->getCode()), ['23000', '23505'], true)
            || str_contains(mb_strtolower($exception->getMessage()), 'unique constraint');
    }
}
