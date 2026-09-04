<?php

namespace App\Console\Commands;

use App\Services\Broadcast\TemplateService;
use App\Services\TenantService;
use Illuminate\Console\Command;

class SyncWhatsAppTemplates extends Command
{
    protected $signature = 'broadcast:sync-templates {business? : The business ID to sync templates for}';

    protected $description = 'Sync WhatsApp message templates from Meta for a business';

    public function handle(TemplateService $templateService, TenantService $tenantService): int
    {
        $businessId = $this->argument('business');

        if ($businessId) {
            $business = \App\Models\Business::find($businessId);
            if (!$business) {
                $this->error('Business not found.');
                return self::FAILURE;
            }
            $businesses = [$business];
        } else {
            $businesses = \App\Models\Business::whereHas('whatsappAccount', function ($q) {
                $q->where('status', 'connected');
            })->cursor();
        }

        $total = 0;
        foreach ($businesses as $business) {
            $this->line("Syncing templates for business #{$business->id}...");
            try {
                $count = $templateService->syncFromMeta($business);
                $this->info("  Synced {$count} templates.");
                $total += $count;
            } catch (\Throwable $e) {
                $this->warn("  Error: {$e->getMessage()}");
            }
        }

        $this->info("Sync complete. Total templates synced: {$total}");

        return self::SUCCESS;
    }
}
