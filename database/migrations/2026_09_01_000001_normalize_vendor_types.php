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
        'SEED' => 'Vendor Bibit',
        'FEED' => 'Vendor Pakan',
        'SERVICE' => 'Vendor Jasa',
        'MULTIPLE' => 'Vendor Beragam',
        'OTHER' => 'Lainnya',
    ];

    public function up(): void
    {
        Schema::create('vendor_types', function (Blueprint $table): void {
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
            DB::table('vendor_types')->insert([
                'code' => $code,
                'name' => $name,
                'normalized_name' => mb_strtolower($name),
                'semantic_type' => $code,
                'is_system' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        Schema::table('vendors', function (Blueprint $table): void {
            $table->foreignId('vendor_type_id')
                ->nullable()
                ->after('vendor_type')
                ->constrained('vendor_types')
                ->restrictOnDelete();
        });

        $legacyValues = DB::table('vendors')
            ->select('vendor_type')
            ->whereNotNull('vendor_type')
            ->distinct()
            ->pluck('vendor_type');

        foreach ($legacyValues as $legacyValue) {
            $legacyValue = trim((string) $legacyValue);
            $canonicalCode = mb_strtoupper($legacyValue);
            $typeId = DB::table('vendor_types')->where('code', $canonicalCode)->value('id');

            if (! $typeId) {
                $name = $legacyValue !== '' ? $legacyValue : 'Jenis Vendor Lama';
                $normalizedName = mb_strtolower($name);
                $existingId = DB::table('vendor_types')->where('normalized_name', $normalizedName)->value('id');

                $typeId = $existingId ?: DB::table('vendor_types')->insertGetId([
                    'code' => 'LEGACY-'.Str::upper(Str::substr(hash('sha256', $legacyValue), 0, 24)),
                    'name' => $name,
                    'normalized_name' => $normalizedName,
                    'semantic_type' => 'OTHER',
                    'is_system' => false,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            DB::table('vendors')
                ->where('vendor_type', $legacyValue)
                ->update(['vendor_type_id' => $typeId]);
        }

        if (DB::table('vendors')->whereNull('vendor_type_id')->exists()) {
            throw new RuntimeException('Normalisasi Jenis Vendor dibatalkan karena terdapat Vendor yang tidak dapat dipetakan.');
        }

        Schema::table('vendors', function (Blueprint $table): void {
            $table->unsignedBigInteger('vendor_type_id')->nullable(false)->change();
            $table->dropColumn('vendor_type');
        });
    }

    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table): void {
            $table->enum('vendor_type', array_keys(self::CANONICAL_TYPES))->nullable()->after('name');
        });

        DB::table('vendors')
            ->join('vendor_types', 'vendor_types.id', '=', 'vendors.vendor_type_id')
            ->update(['vendors.vendor_type' => DB::raw('vendor_types.semantic_type')]);

        Schema::table('vendors', function (Blueprint $table): void {
            $table->enum('vendor_type', array_keys(self::CANONICAL_TYPES))->nullable(false)->change();
            $table->dropConstrainedForeignId('vendor_type_id');
        });

        Schema::dropIfExists('vendor_types');
    }
};
