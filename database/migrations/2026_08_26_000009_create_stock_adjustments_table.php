<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_adjustments', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_number', 50)->unique();
            $table->dateTime('transaction_date')->index();
            $table->foreignId('location_id')
                ->constrained('locations')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('batch_id')
                ->constrained('commodity_batches')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->enum('adjustment_type', ['MORTALITY', 'LOSS', 'CORRECTION_IN', 'CORRECTION_OUT', 'OTHER']);
            $table->decimal('quantity_change', 18, 3);
            $table->decimal('quantity_before', 18, 3);
            $table->decimal('quantity_after', 18, 3);
            $table->text('reason')->nullable();
            $table->foreignId('created_by')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });

        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE stock_adjustments ADD CONSTRAINT chk_adjustments_after_qty CHECK (quantity_after >= 0)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_adjustments');
    }
};
