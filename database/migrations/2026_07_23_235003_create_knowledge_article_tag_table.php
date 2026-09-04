<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('knowledge_article_tag', function (Blueprint $table) {
            $table->id();
            $table->foreignId('knowledge_article_id')->constrained('knowledge_articles')->cascadeOnDelete();
            $table->foreignId('knowledge_tag_id')->constrained('knowledge_tags')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['knowledge_article_id', 'knowledge_tag_id'], 'article_tag_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_article_tag');
    }
};
