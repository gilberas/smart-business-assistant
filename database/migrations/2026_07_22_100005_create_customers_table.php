<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->uuid()->unique();
            $table->string('name');
            $table->string('phone', 30);
            $table->string('email')->nullable();
            $table->string('avatar_url')->nullable();
            $table->json('tags')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('last_interacted_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['business_id', 'phone']);
            $table->index(['business_id', 'last_interacted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
