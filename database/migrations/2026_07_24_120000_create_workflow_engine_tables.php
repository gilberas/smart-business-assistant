<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category')->index();
            $table->string('status', 20)->default('draft')->index();
            $table->string('execution_mode', 20)->default('sequential');
            $table->unsignedInteger('version')->default(1);
            $table->json('variables')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('enabled_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['business_id', 'status']);
            $table->index(['business_id', 'category']);
        });

        Schema::create('workflow_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->json('definition');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('change_summary')->nullable();
            $table->timestamps();

            $table->unique(['workflow_id', 'version']);
        });

        Schema::create('workflow_triggers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_version_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('name');
            $table->json('payload')->nullable();
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();

            $table->index(['workflow_version_id', 'type']);
        });

        Schema::create('workflow_conditions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_version_id')->constrained()->cascadeOnDelete();
            $table->foreignId('workflow_trigger_id')->nullable()->constrained('workflow_triggers')->nullOnDelete();
            $table->string('group_key')->nullable();
            $table->string('field');
            $table->string('operator');
            $table->json('value')->nullable();
            $table->string('boolean_connector')->default('and');
            $table->unsignedInteger('priority')->default(0);
            $table->timestamps();

            $table->index(['workflow_version_id', 'priority']);
        });

        Schema::create('workflow_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_version_id')->constrained()->cascadeOnDelete();
            $table->foreignId('workflow_condition_id')->nullable()->constrained('workflow_conditions')->nullOnDelete();
            $table->string('branch_key')->nullable();
            $table->string('type');
            $table->string('name');
            $table->json('payload')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();

            $table->index(['workflow_version_id', 'position']);
        });

        Schema::create('workflow_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_id')->constrained()->cascadeOnDelete();
            $table->foreignId('workflow_version_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('triggered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('trigger_type');
            $table->string('status', 20)->default('pending')->index();
            $table->string('execution_mode', 20)->default('sequential');
            $table->unsignedInteger('attempts')->default(0);
            $table->unsignedInteger('max_attempts')->default(3);
            $table->json('input_payload')->nullable();
            $table->json('context')->nullable();
            $table->json('result')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamp('next_retry_at')->nullable();
            $table->timestamps();

            $table->index(['business_id', 'status']);
            $table->index(['workflow_id', 'created_at']);
        });

        Schema::create('workflow_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_run_id')->constrained()->cascadeOnDelete();
            $table->foreignId('workflow_action_id')->nullable()->constrained('workflow_actions')->nullOnDelete();
            $table->string('level', 20)->default('info');
            $table->string('stage');
            $table->string('message');
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index(['workflow_run_id', 'level']);
        });

        Schema::create('scheduled_workflows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_id')->constrained()->cascadeOnDelete();
            $table->foreignId('workflow_version_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('cron_expression')->nullable();
            $table->timestamp('next_run_at')->nullable();
            $table->timestamp('last_run_at')->nullable();
            $table->json('schedule_payload')->nullable();
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();

            $table->index(['business_id', 'is_enabled']);
        });

        Schema::create('workflow_variables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_id')->constrained()->cascadeOnDelete();
            $table->foreignId('workflow_version_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('data_type')->default('string');
            $table->json('default_value')->nullable();
            $table->json('description')->nullable();
            $table->boolean('is_required')->default(false);
            $table->timestamps();

            $table->index(['workflow_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_variables');
        Schema::dropIfExists('scheduled_workflows');
        Schema::dropIfExists('workflow_logs');
        Schema::dropIfExists('workflow_runs');
        Schema::dropIfExists('workflow_actions');
        Schema::dropIfExists('workflow_conditions');
        Schema::dropIfExists('workflow_triggers');
        Schema::dropIfExists('workflow_versions');
        Schema::dropIfExists('workflows');
    }
};