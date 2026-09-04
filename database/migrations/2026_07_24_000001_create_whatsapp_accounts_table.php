<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_accounts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('waba_id')->nullable()->index();
            $table->text('access_token')->nullable();
            $table->string('token_status')->default('active');
            $table->timestamp('token_expires_at')->nullable();
            $table->string('webhook_secret')->nullable();
            $table->timestamp('webhook_verified_at')->nullable();
            $table->timestamp('last_webhook_received_at')->nullable();
            $table->json('metadata')->nullable();
            $table->string('status')->default('disconnected');
            $table->timestamp('connected_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_accounts');
    }
};
