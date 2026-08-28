<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stocking_transactions', function (Blueprint $table) {
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
            $table->decimal('quantity', 18, 3);
            $table->decimal('total_cost', 18, 2);
            $table->decimal('unit_cost', 18, 4);
            $table->foreignId('created_by')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE stocking_transactions ADD CONSTRAINT chk_stocking_qty CHECK (quantity > 0)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('stocking_transactions');
    }
};
