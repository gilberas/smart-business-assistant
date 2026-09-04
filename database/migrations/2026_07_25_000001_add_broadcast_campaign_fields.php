<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('broadcast_campaigns', function (Blueprint $table) {
            if (!Schema::hasColumn('broadcast_campaigns', 'uuid')) {
                $table->uuid('uuid')->nullable()->after('id');
            }
            if (!Schema::hasColumn('broadcast_campaigns', 'created_by')) {
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete()->after('business_id');
            }
            if (!Schema::hasColumn('broadcast_campaigns', 'started_at')) {
                $table->timestamp('started_at')->nullable()->after('scheduled_at');
            }
            if (!Schema::hasColumn('broadcast_campaigns', 'completed_at')) {
                $table->timestamp('completed_at')->nullable()->after('started_at');
            }
            if (!Schema::hasColumn('broadcast_campaigns', 'metadata')) {
                $table->json('metadata')->nullable()->after('recipient_filter');
            }
        });

        Schema::table('broadcast_recipients', function (Blueprint $table) {
            if (!Schema::hasColumn('broadcast_recipients', 'uuid')) {
                $table->uuid('uuid')->nullable()->after('id');
            }
            if (!Schema::hasColumn('broadcast_recipients', 'queued_at')) {
                $table->timestamp('queued_at')->nullable()->after('sent_at');
            }
            if (!Schema::hasColumn('broadcast_recipients', 'delivered_at')) {
                $table->timestamp('delivered_at')->nullable()->after('queued_at');
            }
            if (!Schema::hasColumn('broadcast_recipients', 'read_at')) {
                $table->timestamp('read_at')->nullable()->after('delivered_at');
            }
            if (!Schema::hasColumn('broadcast_recipients', 'failed_at')) {
                $table->timestamp('failed_at')->nullable()->after('read_at');
            }
            if (!Schema::hasColumn('broadcast_recipients', 'message_id')) {
                $table->string('message_id', 255)->nullable()->after('uuid');
            }
        });
    }

    public function down(): void
    {
        Schema::table('broadcast_campaigns', function (Blueprint $table) {
            $table->dropColumn(['uuid', 'created_by', 'started_at', 'completed_at', 'metadata']);
        });

        Schema::table('broadcast_recipients', function (Blueprint $table) {
            $table->dropColumn(['uuid', 'queued_at', 'delivered_at', 'read_at', 'failed_at', 'message_id']);
        });
    }
};
