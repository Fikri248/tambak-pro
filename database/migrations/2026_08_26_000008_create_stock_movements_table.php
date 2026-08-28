<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_number', 50)->unique();
            $table->dateTime('transaction_date')->index();
            $table->foreignId('batch_id')
                ->constrained('commodity_batches')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('from_location_id')
                ->constrained('locations')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('to_location_id')
                ->constrained('locations')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->decimal('quantity', 18, 3);
            $table->foreignId('created_by')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE stock_movements ADD CONSTRAINT chk_movements_qty CHECK (quantity > 0)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
