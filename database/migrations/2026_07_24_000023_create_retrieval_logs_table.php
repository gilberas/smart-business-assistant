<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('retrieval_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->uuid('session_id')->nullable();
            $table->string('query', 1000);
            $table->json('parsed_query')->nullable();
            $table->string('intent', 50)->nullable();
            $table->string('primary_strategy', 30)->nullable();
            $table->integer('total_results')->default(0);
            $table->integer('returned_results')->default(0);
            $table->float('execution_time_ms')->default(0);
            $table->json('sources_used')->nullable();
            $table->json('top_chunk_ids')->nullable();
            $table->float('context_token_estimate')->default(0);
            $table->timestamps();
            $table->index(['business_id', 'created_at']);
            $table->index(['business_id', 'intent']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('retrieval_logs');
    }
};
