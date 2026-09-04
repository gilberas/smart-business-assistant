<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('message_id')->nullable()->constrained()->nullOnDelete();
            $table->string('media_id')->nullable()->index();
            $table->string('mime_type')->nullable();
            $table->string('sha256')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('file_name')->nullable();
            $table->string('caption')->nullable();
            $table->string('local_path')->nullable();
            $table->string('disk')->nullable();
            $table->text('url')->nullable();
            $table->text('temporary_url')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['business_id', 'media_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
