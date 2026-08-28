<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feed_items', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->enum('item_type', ['FEED', 'NUTRITION', 'MEDICINE', 'OTHER']);
            $table->foreignId('default_vendor_id')
                ->nullable()
                ->constrained('vendors')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->string('unit', 50);
            $table->decimal('default_price', 18, 2)->default(0);
            $table->text('description')->nullable();
            $table->enum('status', ['ACTIVE', 'INACTIVE'])->default('ACTIVE');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feed_items');
    }
};
