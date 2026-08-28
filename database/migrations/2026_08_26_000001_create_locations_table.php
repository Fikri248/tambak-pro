<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('locations')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->enum('location_type', ['AREA', 'TAMBAK', 'PETAK', 'OTHER']);
            $table->text('address')->nullable();
            $table->text('description')->nullable();
            $table->enum('status', ['ACTIVE', 'INACTIVE'])->default('ACTIVE');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};
