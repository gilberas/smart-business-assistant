<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->foreignId('conversation_id')->nullable()->change();
            $table->longText('body')->nullable();
        });

        Schema::table('whatsapp_phone_numbers', function (Blueprint $table) {
            $table->string('display_phone')->nullable();
            $table->string('display_name')->nullable();
        });

        Schema::table('webhook_events', function (Blueprint $table) {
            $table->string('type')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn('body');
            $table->foreignId('conversation_id')->nullable(false)->change();
        });

        Schema::table('whatsapp_phone_numbers', function (Blueprint $table) {
            $table->dropColumn(['display_phone', 'display_name']);
        });

        Schema::table('webhook_events', function (Blueprint $table) {
            $table->string('type')->nullable(false)->change();
        });
    }
};