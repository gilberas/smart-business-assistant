<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics_daily', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('metric', 100);
            $table->decimal('value', 18, 4)->default(0);
            $table->json('dimensions')->nullable();
            $table->timestamps();

            $table->unique(['business_id', 'date', 'metric']);
            $table->index(['business_id', 'date']);
            $table->index(['business_id', 'metric']);
        });

        Schema::create('analytics_hourly', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->tinyInteger('hour');
            $table->string('metric', 100);
            $table->decimal('value', 18, 4)->default(0);
            $table->json('dimensions')->nullable();
            $table->timestamps();

            $table->unique(['business_id', 'date', 'hour', 'metric']);
            $table->index(['business_id', 'date', 'hour']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_hourly');
        Schema::dropIfExists('analytics_daily');
    }
};
