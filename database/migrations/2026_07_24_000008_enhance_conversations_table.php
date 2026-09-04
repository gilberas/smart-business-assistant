<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->uuid('uuid')->unique()->after('id');
            $table->string('priority', 20)->default('normal')->after('source');
            $table->string('channel', 30)->nullable()->after('priority');
            $table->string('channel_id')->nullable()->after('channel');
            $table->string('title')->nullable()->after('channel_id');
            $table->foreignId('last_message_id')->nullable()->constrained('messages')->nullOnDelete()->after('last_message_at');
            $table->timestamp('closed_at')->nullable()->after('handovered_at');
            $table->unsignedInteger('message_count')->default(0)->after('closed_at');
            $table->index(['business_id', 'priority', 'status'], 'conv_priority_status_idx');
            $table->index(['business_id', 'channel', 'status'], 'conv_channel_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropIndex('conv_priority_status_idx');
            $table->dropIndex('conv_channel_status_idx');
            $table->dropColumn(['uuid', 'priority', 'channel', 'channel_id', 'title', 'last_message_id', 'closed_at', 'message_count']);
        });
    }
};
