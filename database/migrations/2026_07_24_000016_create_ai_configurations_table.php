<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_configurations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 50)->default('openai');
            $table->string('model', 100)->default('gpt-4o');
            $table->decimal('temperature', 4, 2)->default(0.7);
            $table->decimal('top_p', 4, 2)->default(1.0);
            $table->unsignedInteger('max_tokens')->default(2048);
            $table->unsignedInteger('timeout_seconds')->default(30);
            $table->boolean('streaming')->default(false);
            $table->string('language', 10)->default('en');
            $table->json('safety_settings')->nullable();
            $table->json('system_prompt')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['business_id', 'provider']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_configurations');
    }
};
