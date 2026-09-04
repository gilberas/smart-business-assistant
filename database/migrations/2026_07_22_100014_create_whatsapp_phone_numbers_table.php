<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_phone_numbers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('phone_number', 30);
            $table->string('display_phone_number', 30)->nullable();
            $table->string('verified_name')->nullable();
            $table->string('quality_rating', 20)->nullable();
            $table->string('status', 20)->default('pending');
            $table->timestamps();

            $table->unique(['business_id', 'phone_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_phone_numbers');
    }
};
