<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('message_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('status');
            $table->string('meta_status')->nullable();
            $table->timestamp('timestamp')->nullable();
            $table->string('error_code')->nullable();
            $table->string('error_title')->nullable();
            $table->text('error_message')->nullable();
            $table->json('details')->nullable();
            $table->timestamps();

            $table->index(['business_id', 'message_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_statuses');
    }
};
