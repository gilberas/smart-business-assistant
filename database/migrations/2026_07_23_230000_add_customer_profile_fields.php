<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('status', 20)->default('active')->after('email');
            $table->string('source', 50)->nullable()->after('status');
            $table->string('preferred_language', 10)->nullable()->after('source');
            $table->string('timezone', 50)->nullable()->after('preferred_language');
            $table->string('customer_type', 20)->default('individual')->after('timezone');
            $table->string('lifecycle_stage', 30)->default('lead')->after('customer_type');
            $table->string('phone_alt', 30)->nullable()->after('phone');
            $table->string('company_name', 255)->nullable()->after('phone_alt');
            $table->string('job_title', 255)->nullable()->after('company_name');
            $table->string('website_url', 500)->nullable()->after('job_title');
            $table->text('bio')->nullable()->after('website_url');
            $table->string('social_instagram', 255)->nullable()->after('bio');
            $table->string('social_facebook', 255)->nullable()->after('social_instagram');
            $table->string('social_twitter', 255)->nullable()->after('social_facebook');
            $table->string('social_linkedin', 255)->nullable()->after('social_twitter');
            $table->string('gender', 10)->nullable()->after('social_linkedin');
            $table->date('birthday')->nullable()->after('gender');
            $table->string('nationality', 100)->nullable()->after('birthday');
            $table->integer('total_orders')->default(0)->after('nationality');
            $table->decimal('total_spent', 15, 2)->default(0)->after('total_orders');
            $table->decimal('average_order_value', 15, 2)->default(0)->after('total_spent');
            $table->integer('loyalty_points')->default(0)->after('average_order_value');
            $table->string('loyalty_tier', 50)->nullable()->after('loyalty_points');
            $table->string('assigned_to', 255)->nullable()->after('loyalty_tier');
            $table->timestamp('last_activity_at')->nullable()->after('last_interacted_at');
            $table->index('status');
            $table->index('source');
            $table->index('customer_type');
            $table->index('lifecycle_stage');
            $table->index(['business_id', 'status', 'lifecycle_stage']);
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn([
                'status', 'source', 'preferred_language', 'timezone',
                'customer_type', 'lifecycle_stage', 'phone_alt', 'company_name',
                'job_title', 'website_url', 'bio', 'social_instagram',
                'social_facebook', 'social_twitter', 'social_linkedin',
                'gender', 'birthday', 'nationality', 'total_orders',
                'total_spent', 'average_order_value', 'loyalty_points',
                'loyalty_tier', 'assigned_to', 'last_activity_at',
            ]);
            $table->dropIndex(['status']);
            $table->dropIndex(['source']);
            $table->dropIndex(['customer_type']);
            $table->dropIndex(['lifecycle_stage']);
            $table->dropIndex(['business_id', 'status', 'lifecycle_stage']);
        });
    }
};
