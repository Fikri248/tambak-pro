<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pond_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')
                ->constrained('locations')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('batch_id')
                ->constrained('commodity_batches')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->decimal('quantity', 18, 3);
            $table->timestamps();

            $table->unique(['location_id', 'batch_id']);
        });

        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE pond_stocks ADD CONSTRAINT chk_pond_stocks_qty CHECK (quantity >= 0)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pond_stocks');
    }
};
