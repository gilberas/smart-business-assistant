<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_phone_numbers', function (Blueprint $table) {
            $table->uuid('uuid')->unique()->after('id');
            $table->foreignId('whatsapp_account_id')->nullable()->constrained()->nullOnDelete()->after('business_id');
            $table->string('phone_number_id')->nullable()->index()->after('phone_number');
            $table->text('certificate')->nullable()->after('verified_name');
            $table->string('code_verification_status')->nullable()->after('certificate');
            $table->boolean('pin_enabled')->default(false)->after('code_verification_status');
            $table->string('messaging_limit_tier')->nullable()->after('pin_enabled');
            $table->timestamp('last_checked_at')->nullable()->after('messaging_limit_tier');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_phone_numbers', function (Blueprint $table) {
            $table->dropColumn([
                'uuid', 'whatsapp_account_id', 'phone_number_id', 'certificate',
                'code_verification_status', 'pin_enabled', 'messaging_limit_tier',
                'last_checked_at', 'deleted_at',
            ]);
        });
    }
};
