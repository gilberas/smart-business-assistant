<?php

namespace App\Console\Commands;

use App\Services\Broadcast\SchedulingService;
use Illuminate\Console\Command;

class ProcessScheduledCampaigns extends Command
{
    protected $signature = 'broadcast:process-scheduled';

    protected $description = 'Process broadcast campaigns that are due for sending';

    public function handle(SchedulingService $schedulingService): int
    {
        $this->info('Checking for scheduled campaigns...');

        try {
            $schedulingService->processDueCampaigns();
            $this->info('Scheduled campaigns processed successfully.');
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Failed to process scheduled campaigns: '.$e->getMessage());
            return self::FAILURE;
        }
    }
}
