<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->foreignId('owner_id')->nullable()->after('uuid')->constrained('users')->nullOnDelete();
            $table->string('website')->nullable()->after('address');
            $table->json('branding')->nullable()->after('locale');
            $table->json('preferences')->nullable()->after('branding');
            $table->text('notes')->nullable()->after('preferences');
            $table->string('owner_name')->nullable()->after('notes');
            $table->string('owner_email')->nullable()->after('owner_name');
            $table->string('owner_phone', 30)->nullable()->after('owner_email');
            $table->string('tax_id')->nullable()->after('owner_phone');
            $table->string('registration_number')->nullable()->after('tax_id');
            $table->string('industry')->nullable()->after('registration_number');
            $table->integer('employee_count')->nullable()->after('industry');
            $table->softDeletes('trial_ends_at')->nullable()->change();
        });

        Schema::table('business_user', function (Blueprint $table) {
            $table->timestamp('last_switched_at')->nullable()->after('joined_at');
        });
    }

    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropForeign(['owner_id']);
            $table->dropColumn([
                'owner_id', 'website', 'branding', 'preferences', 'notes',
                'owner_name', 'owner_email', 'owner_phone', 'tax_id',
                'registration_number', 'industry', 'employee_count',
            ]);
        });

        Schema::table('business_user', function (Blueprint $table) {
            $table->dropColumn('last_switched_at');
        });
    }
};
