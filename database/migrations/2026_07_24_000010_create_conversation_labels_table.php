<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversation_labels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('color', 7)->default('#6366f1');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['business_id', 'name']);
        });

        Schema::create('conversation_label_conversation', function (Blueprint $table) {
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('conversation_label_id')->constrained()->cascadeOnDelete();
            $table->primary(['conversation_id', 'conversation_label_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversation_label_conversation');
        Schema::dropIfExists('conversation_labels');
    }
};
