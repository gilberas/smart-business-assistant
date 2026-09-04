<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->uuid('uuid')->unique()->after('id');
            $table->foreignId('whatsapp_account_id')->nullable()->constrained()->nullOnDelete()->after('business_id');
            $table->string('phone_number_id')->nullable()->index()->after('whatsapp_account_id');
            $table->string('from_number')->nullable()->index()->after('phone_number_id');
            $table->string('to_number')->nullable()->index()->after('from_number');
            $table->string('direction')->nullable()->after('to_number');
            $table->string('message_type')->nullable()->after('direction');
            $table->string('meta_message_id')->nullable()->index()->after('message_type');
            $table->string('conversation_uuid')->nullable()->index()->after('meta_message_id');
            $table->string('media_id')->nullable()->after('content');
            $table->text('media_url')->nullable()->after('media_id');
            $table->string('caption')->nullable()->after('media_url');
            $table->unsignedBigInteger('reply_to_message_id')->nullable()->after('caption');
            $table->string('quoted_message_id')->nullable()->after('reply_to_message_id');
            $table->string('template_name')->nullable()->after('quoted_message_id');
            $table->string('template_language')->nullable()->after('template_name');
            $table->json('template_params')->nullable()->after('template_language');
            $table->json('context')->nullable()->after('template_params');
            $table->json('errors')->nullable()->after('metadata');
            $table->timestamp('sent_at')->nullable()->after('errors');
            $table->timestamp('delivered_at')->nullable()->after('sent_at');
            $table->timestamp('read_at')->nullable()->after('delivered_at');
            $table->timestamp('failed_at')->nullable()->after('read_at');
            $table->softDeletes();

            $table->index(['business_id', 'from_number', 'created_at'], 'msg_from_created_idx');
            $table->index(['business_id', 'direction', 'created_at'], 'msg_dir_created_idx');
            $table->index(['business_id', 'conversation_id'], 'msg_conv_id_idx');
            $table->index(['business_id', 'status'], 'msg_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropIndex('msg_from_created_idx');
            $table->dropIndex('msg_dir_created_idx');
            $table->dropIndex('msg_conv_id_idx');
            $table->dropIndex('msg_status_idx');
            $table->dropColumn([
                'uuid', 'whatsapp_account_id', 'phone_number_id', 'from_number', 'to_number',
                'direction', 'message_type', 'meta_message_id', 'conversation_uuid',
                'media_id', 'media_url', 'caption', 'reply_to_message_id', 'quoted_message_id',
                'template_name', 'template_language', 'template_params', 'context',
                'errors', 'sent_at', 'delivered_at', 'read_at', 'failed_at', 'deleted_at',
            ]);
        });
    }
};
