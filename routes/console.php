<?php

use App\Console\Commands\ProcessScheduledWorkflows;
use App\Jobs\AI\MonitorProviderHealth;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('workflows:process-scheduled', function () {
    $this->call(ProcessScheduledWorkflows::class);
})->purpose('Process scheduled and delayed workflow executions');

Schedule::job(new MonitorProviderHealth)->everyFiveMinutes();
Schedule::command('workflows:process-scheduled')->everyMinute();
Schedule::command('broadcast:process-scheduled')->everyMinute();
Schedule::command('analytics:aggregate-daily')->dailyAt('01:00');
