<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('knowledge_chunks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->uuid('uuid')->unique();
            $table->morphs('chunkable');
            $table->string('source_type', 50);
            $table->unsignedBigInteger('source_id')->nullable();
            $table->integer('chunk_index');
            $table->string('strategy', 30);
            $table->longText('content');
            $table->text('heading')->nullable();
            $table->json('metadata')->nullable();
            $table->integer('word_count')->default(0);
            $table->integer('char_count')->default(0);
            $table->integer('token_estimate')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['business_id', 'source_type']);
            $table->index(['business_id', 'chunkable_type', 'chunkable_id']);
            $table->index(['business_id', 'source_type', 'source_id']);
        });

        if (DB::getDriverName() === 'mysql') {
            Schema::table('knowledge_chunks', function (Blueprint $table) {
                $table->fullText('content');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_chunks');
    }
};
