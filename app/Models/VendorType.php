<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use LogicException;

class VendorType extends Model
{
    public const SEMANTIC_SEED = 'SEED';

    public const SEMANTIC_FEED = 'FEED';

    public const SEMANTIC_SERVICE = 'SERVICE';

    public const SEMANTIC_MULTIPLE = 'MULTIPLE';

    public const SEMANTIC_OTHER = 'OTHER';

    public const SEMANTICS = [
        self::SEMANTIC_SEED,
        self::SEMANTIC_FEED,
        self::SEMANTIC_SERVICE,
        self::SEMANTIC_MULTIPLE,
        self::SEMANTIC_OTHER,
    ];

    protected $fillable = ['name'];

    protected function casts(): array
    {
        return ['is_system' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::creating(function (VendorType $type): void {
            $type->code ??= 'CUSTOM-'.Str::upper((string) Str::uuid());
            $type->semantic_type ??= self::SEMANTIC_OTHER;
            $type->is_system ??= false;
            $type->normalized_name = self::normalizeName($type->name);
        });

        static::updating(function (VendorType $type): void {
            if ($type->isDirty(['code', 'semantic_type', 'is_system'])) {
                throw new LogicException('Identitas internal Jenis Vendor tidak dapat diubah.');
            }

            $type->normalized_name = self::normalizeName($type->name);
        });
    }

    public function vendors(): HasMany
    {
        return $this->hasMany(Vendor::class);
    }

    public function scopeWithSemantics(Builder $query, array $semantics): Builder
    {
        return $query->whereIn('semantic_type', $semantics);
    }

    public function hasSemantic(string ...$semantics): bool
    {
        return in_array($this->semantic_type, $semantics, true);
    }

    public static function normalizeName(string $name): string
    {
        return mb_strtolower(trim($name));
    }
}
