<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /** @var array<string, string> */
    private const CANONICAL_TYPES = [
        'FEED' => 'Pakan',
        'NUTRITION' => 'Nutrisi',
        'MEDICINE' => 'Obat',
        'OTHER' => 'Lainnya',
    ];

    public function up(): void
    {
        Schema::create('item_types', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 100)->unique();
            $table->string('name');
            $table->string('normalized_name')->unique();
            $table->enum('semantic_type', array_keys(self::CANONICAL_TYPES))->default('OTHER')->index();
            $table->boolean('is_system')->default(false)->index();
            $table->timestamps();
        });

        $now = now();

        foreach (self::CANONICAL_TYPES as $code => $name) {
            DB::table('item_types')->insert([
                'code' => $code,
                'name' => $name,
                'normalized_name' => mb_strtolower($name),
                'semantic_type' => $code,
                'is_system' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        Schema::table('feed_items', function (Blueprint $table): void {
            $table->foreignId('item_type_id')
                ->nullable()
                ->after('item_type')
                ->constrained('item_types')
                ->restrictOnDelete();
        });

        $legacyValues = DB::table('feed_items')
            ->select('item_type')
            ->whereNotNull('item_type')
            ->distinct()
            ->pluck('item_type');

        foreach ($legacyValues as $legacyValue) {
            $legacyValue = trim((string) $legacyValue);
            $canonicalCode = mb_strtoupper($legacyValue);
            $typeId = DB::table('item_types')->where('code', $canonicalCode)->value('id');

            if (! $typeId) {
                $name = $legacyValue !== '' ? $legacyValue : 'Jenis Barang/Item Lama';
                $normalizedName = mb_strtolower($name);
                $typeId = DB::table('item_types')->where('normalized_name', $normalizedName)->value('id');

                $typeId ??= DB::table('item_types')->insertGetId([
                    'code' => 'LEGACY-'.Str::upper(Str::substr(hash('sha256', $legacyValue), 0, 24)),
                    'name' => $name,
                    'normalized_name' => $normalizedName,
                    'semantic_type' => 'OTHER',
                    'is_system' => false,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            DB::table('feed_items')
                ->where('item_type', $legacyValue)
                ->update(['item_type_id' => $typeId]);
        }

        if (DB::table('feed_items')->whereNull('item_type_id')->exists()) {
            throw new RuntimeException('Normalisasi Jenis Barang/Item dibatalkan karena terdapat Barang/Item yang tidak dapat dipetakan.');
        }

        Schema::table('feed_items', function (Blueprint $table): void {
            $table->unsignedBigInteger('item_type_id')->nullable(false)->change();
            $table->dropColumn('item_type');
        });
    }

    public function down(): void
    {
        Schema::table('feed_items', function (Blueprint $table): void {
            $table->string('item_type', 100)->nullable()->after('name');
        });

        DB::table('feed_items')
            ->join('item_types', 'item_types.id', '=', 'feed_items.item_type_id')
            ->update(['feed_items.item_type' => DB::raw('item_types.semantic_type')]);

        Schema::table('feed_items', function (Blueprint $table): void {
            $table->string('item_type', 100)->nullable(false)->change();
            $table->dropConstrainedForeignId('item_type_id');
        });

        Schema::dropIfExists('item_types');
    }
};
