<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_models', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_provider_id')->constrained()->cascadeOnDelete();
            $table->string('model_id');
            $table->string('name');
            $table->string('group')->nullable();
            $table->json('capabilities')->nullable();
            $table->decimal('input_price_per_1k', 15, 10)->default(0);
            $table->decimal('output_price_per_1k', 15, 10)->default(0);
            $table->unsignedInteger('context_window')->default(0);
            $table->unsignedInteger('max_output_tokens')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['ai_provider_id', 'model_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_models');
    }
};
