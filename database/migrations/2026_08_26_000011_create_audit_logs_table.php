<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->string('action', 50);
            $table->string('module', 100);
            $table->unsignedBigInteger('record_id')->nullable();
            $table->string('transaction_number', 50)->nullable();
            $table->text('description')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();

            $table->index(['module', 'record_id']);
            $table->index('transaction_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
