<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commodity_batches', function (Blueprint $table) {
            $table->id();
            $table->string('batch_code', 50)->unique();
            $table->foreignId('commodity_id')
                ->constrained('commodities')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('vendor_id')
                ->nullable()
                ->constrained('vendors')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->date('purchase_date');
            $table->decimal('initial_quantity', 18, 3);
            $table->decimal('total_cost', 18, 2);
            $table->decimal('unit_cost', 18, 4);
            $table->enum('status', ['ACTIVE', 'CLOSED', 'CANCELLED'])->default('ACTIVE');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE commodity_batches ADD CONSTRAINT chk_batches_initial_qty CHECK (initial_quantity > 0)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('commodity_batches');
    }
};
