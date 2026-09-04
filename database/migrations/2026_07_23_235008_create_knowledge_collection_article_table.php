<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('knowledge_collection_article', function (Blueprint $table) {
            $table->id();
            $table->foreignId('knowledge_collection_id')->constrained('knowledge_collections')->cascadeOnDelete();
            $table->foreignId('knowledge_article_id')->constrained('knowledge_articles')->cascadeOnDelete();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['knowledge_collection_id', 'knowledge_article_id'], 'collection_article_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_collection_article');
    }
};
