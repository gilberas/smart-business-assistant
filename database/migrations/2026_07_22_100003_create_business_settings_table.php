<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->unique()->constrained()->cascadeOnDelete();
            $table->json('business_hours')->nullable();
            $table->string('ai_provider', 20)->nullable();
            $table->string('ai_model', 100)->nullable();
            $table->decimal('ai_temperature', 4, 2)->default(0.7);
            $table->integer('ai_max_tokens')->default(2048);
            $table->text('ai_system_prompt')->nullable();
            $table->text('whatsapp_business_account_id')->nullable();
            $table->text('whatsapp_access_token')->nullable();
            $table->string('whatsapp_phone_number_id')->nullable();
            $table->string('webhook_verify_token')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_settings');
    }
};
