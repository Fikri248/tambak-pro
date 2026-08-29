<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_descriptions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->enum('status', ['ACTIVE', 'INACTIVE'])->default('ACTIVE');
            $table->timestamps();
        });

        Schema::create('account_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->enum('status', ['ACTIVE', 'INACTIVE'])->default('ACTIVE');
            $table->timestamps();
        });

        Schema::create('financial_statements', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->enum('status', ['ACTIVE', 'INACTIVE'])->default('ACTIVE');
            $table->timestamps();
        });

        Schema::create('chart_of_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('number_code', 50)->unique();
            $table->foreignId('description_id')->constrained('account_descriptions')->restrictOnDelete();
            $table->foreignId('account_type_id')->constrained()->restrictOnDelete();
            $table->foreignId('financial_statement_id')->constrained()->restrictOnDelete();
            $table->enum('status', ['ACTIVE', 'INACTIVE'])->default('ACTIVE');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chart_of_accounts');
        Schema::dropIfExists('financial_statements');
        Schema::dropIfExists('account_types');
        Schema::dropIfExists('account_descriptions');
    }
};
