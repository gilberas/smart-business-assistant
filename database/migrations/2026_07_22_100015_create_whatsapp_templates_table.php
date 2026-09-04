<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('language', 10);
            $table->string('category', 30)->nullable();
            $table->string('status', 20)->default('pending');
            $table->text('body');
            $table->json('header')->nullable();
            $table->text('footer')->nullable();
            $table->json('buttons')->nullable();
            $table->string('meta_template_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['business_id', 'name', 'language']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_templates');
    }
};
