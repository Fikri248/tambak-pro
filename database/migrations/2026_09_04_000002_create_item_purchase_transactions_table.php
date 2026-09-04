<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_purchase_transactions', function (Blueprint $table): void {
            $table->id();
            $table->string('transaction_number', 50)->unique();
            $table->dateTime('transaction_date')->index();
            $table->foreignId('feed_item_id')->constrained('feed_items')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnUpdate()->restrictOnDelete();
            $table->decimal('quantity', 18, 3);
            $table->decimal('unit_cost', 18, 4);
            $table->decimal('total_cost', 18, 2);
            $table->foreignId('created_by')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE item_purchase_transactions ADD CONSTRAINT chk_item_purchase_qty CHECK (quantity > 0)');
            DB::statement('ALTER TABLE item_purchase_transactions ADD CONSTRAINT chk_item_purchase_unit_cost CHECK (unit_cost >= 0)');
            DB::statement('ALTER TABLE item_purchase_transactions ADD CONSTRAINT chk_item_purchase_total_cost CHECK (total_cost >= 0)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('item_purchase_transactions');
    }
};
