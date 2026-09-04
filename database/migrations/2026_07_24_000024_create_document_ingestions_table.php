<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_ingestions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->morphs('ingestible');
            $table->string('status', 30)->default('pending');
            $table->string('strategy', 30)->nullable();
            $table->integer('total_chunks')->default(0);
            $table->integer('processed_chunks')->default(0);
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['business_id', 'status']);
            $table->index(['business_id', 'ingestible_type', 'ingestible_id'], 'doc_ingest_biz_type_id_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_ingestions');
    }
};
