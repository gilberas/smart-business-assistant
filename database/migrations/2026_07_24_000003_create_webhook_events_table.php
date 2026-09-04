<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('webhook_events', function (Blueprint $table) {
            $table->uuid('uuid')->unique()->after('id');
            $table->foreignId('whatsapp_account_id')->nullable()->constrained()->nullOnDelete()->after('business_id');
            $table->string('phone_number_id')->nullable()->index()->after('whatsapp_account_id');
            $table->string('event_type')->index()->after('phone_number_id');
            $table->string('meta_event_id')->nullable()->index()->after('event_type');
            $table->string('meta_changed_field')->nullable()->after('meta_event_id');
            $table->text('meta_resource')->nullable()->after('meta_changed_field');
            $table->json('headers')->nullable()->after('payload');
            $table->string('signature')->nullable()->after('headers');
            $table->unsignedTinyInteger('retry_count')->default(0)->after('signature');
            $table->text('last_error')->nullable()->after('retry_count');

            $table->index(['business_id', 'event_type', 'created_at'], 'webhook_events_type_created_idx');
        });
    }

    public function down(): void
    {
        Schema::table('webhook_events', function (Blueprint $table) {
            $table->dropColumn([
                'uuid', 'whatsapp_account_id', 'phone_number_id', 'event_type',
                'meta_event_id', 'meta_changed_field', 'meta_resource',
                'headers', 'signature', 'retry_count', 'last_error',
            ]);
            $table->dropIndex('webhook_events_type_created_idx');
        });
    }
};
