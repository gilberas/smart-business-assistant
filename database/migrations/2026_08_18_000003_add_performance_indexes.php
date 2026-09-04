<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // customers list defaults to ORDER BY created_at DESC filtered by business_id
        Schema::table('customers', function (Blueprint $table) {
            $table->index(['business_id', 'created_at'], 'customers_business_created_idx');
        });

        // analytics calculators query handover logs by business + type, and per-agent
        // ranges by business + assigned_to + created_at
        Schema::table('handover_logs', function (Blueprint $table) {
            $table->index(['business_id', 'handover_type'], 'handover_logs_business_type_idx');
            $table->index(['business_id', 'assigned_to', 'created_at'], 'handover_logs_business_assigned_created_idx');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex('customers_business_created_idx');
        });

        Schema::table('handover_logs', function (Blueprint $table) {
            $table->dropIndex('handover_logs_business_type_idx');
            $table->dropIndex('handover_logs_business_assigned_created_idx');
        });
    }
};
