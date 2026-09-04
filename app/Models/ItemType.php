<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use LogicException;

class ItemType extends Model
{
    public const SEMANTIC_FEED = 'FEED';

    public const SEMANTIC_NUTRITION = 'NUTRITION';

    public const SEMANTIC_MEDICINE = 'MEDICINE';

    public const SEMANTIC_OTHER = 'OTHER';

    public const SEMANTICS = [
        self::SEMANTIC_FEED,
        self::SEMANTIC_NUTRITION,
        self::SEMANTIC_MEDICINE,
        self::SEMANTIC_OTHER,
    ];

    protected $fillable = ['name'];

    protected function casts(): array
    {
        return ['is_system' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::creating(function (ItemType $type): void {
            $type->code ??= 'CUSTOM-'.Str::upper((string) Str::uuid());
            $type->semantic_type ??= self::SEMANTIC_OTHER;
            $type->is_system ??= false;
            $type->normalized_name = self::normalizeName($type->name);
        });

        static::updating(function (ItemType $type): void {
            if ($type->isDirty(['code', 'semantic_type', 'is_system'])) {
                throw new LogicException('Identitas internal Jenis Barang/Item tidak dapat diubah.');
            }

            $type->normalized_name = self::normalizeName($type->name);
        });
    }

    public function feedItems(): HasMany
    {
        return $this->hasMany(FeedItem::class);
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
