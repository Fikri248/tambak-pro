<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feeding_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_number', 50)->unique();
            $table->dateTime('transaction_date')->index();
            $table->foreignId('location_id')
                ->constrained('locations')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('batch_id')
                ->nullable()
                ->constrained('commodity_batches')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('feed_item_id')
                ->constrained('feed_items')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('vendor_id')
                ->nullable()
                ->constrained('vendors')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->decimal('stock_quantity_snapshot', 18, 3)->nullable();
            $table->decimal('feed_quantity', 18, 3);
            $table->decimal('unit_cost', 18, 4);
            $table->decimal('total_cost', 18, 2);
            $table->foreignId('created_by')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE feeding_transactions ADD CONSTRAINT chk_feeding_qty CHECK (feed_quantity > 0)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('feeding_transactions');
    }
};
