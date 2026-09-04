<?php

namespace App\Console\Commands;

use App\Models\AiRequestLog;
use App\Models\AiUsage;
use App\Models\Business;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AggregateDailyMetrics extends Command
{
    protected $signature = 'analytics:aggregate-daily {--date= : The date to aggregate (YYYY-MM-DD)}';

    protected $description = 'Aggregate daily analytics metrics for all businesses';

    public function handle(): int
    {
        $date = $this->option('date') ?: now()->subDay()->toDateString();
        $start = "{$date} 00:00:00";
        $end = "{$date} 23:59:59";

        $businesses = Business::where('status', 'active')->cursor();
        $bar = $this->output->createProgressBar($businesses->count());
        $bar->start();

        foreach ($businesses as $business) {
            try {
                $this->aggregateConversations($business, $date, $start, $end);
                $this->aggregateMessages($business, $date, $start, $end);
                $this->aggregateCustomers($business, $date, $start, $end);
                $this->aggregateAiUsage($business, $date, $start, $end);
            } catch (\Throwable $e) {
                $this->warn("Error aggregating business #{$business->id}: {$e->getMessage()}");
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Daily aggregation complete for {$date}.");

        return self::SUCCESS;
    }

    protected function aggregateConversations(Business $business, string $date, string $start, string $end): void
    {
        $total = Conversation::where('business_id', $business->id)
            ->whereBetween('created_at', [$start, $end])->count();

        $open = Conversation::where('business_id', $business->id)
            ->whereBetween('created_at', [$start, $end])
            ->where('status', 'open')->count();

        $closed = Conversation::where('business_id', $business->id)
            ->whereBetween('created_at', [$start, $end])
            ->where('status', 'closed')->count();

        $aiHandled = Conversation::where('business_id', $business->id)
            ->whereBetween('created_at', [$start, $end])
            ->where('handover_mode', 'ai')->count();

        $humanHandled = Conversation::where('business_id', $business->id)
            ->whereBetween('created_at', [$start, $end])
            ->where('handover_mode', 'human')->count();

        DB::table('analytics_daily')->updateOrInsert(
            ['business_id' => $business->id, 'date' => $date, 'metric' => 'conversations'],
            ['value' => $total, 'dimensions' => json_encode(['open' => $open, 'closed' => $closed, 'ai_handled' => $aiHandled, 'human_handled' => $humanHandled]), 'updated_at' => now()],
        );
    }

    protected function aggregateMessages(Business $business, string $date, string $start, string $end): void
    {
        $total = Message::where('business_id', $business->id)
            ->whereBetween('created_at', [$start, $end])->count();

        $inbound = Message::where('business_id', $business->id)
            ->whereBetween('created_at', [$start, $end])
            ->where('direction', 'inbound')->count();

        $outbound = Message::where('business_id', $business->id)
            ->whereBetween('created_at', [$start, $end])
            ->where('direction', 'outgoing')->count();

        DB::table('analytics_daily')->updateOrInsert(
            ['business_id' => $business->id, 'date' => $date, 'metric' => 'messages'],
            ['value' => $total, 'dimensions' => json_encode(['inbound' => $inbound, 'outbound' => $outbound]), 'updated_at' => now()],
        );
    }

    protected function aggregateCustomers(Business $business, string $date, string $start, string $end): void
    {
        $new = \App\Models\Customer::where('business_id', $business->id)
            ->whereBetween('created_at', [$start, $end])->count();

        $interacted = \App\Models\Customer::where('business_id', $business->id)
            ->whereBetween('last_interacted_at', [$start, $end])->count();

        DB::table('analytics_daily')->updateOrInsert(
            ['business_id' => $business->id, 'date' => $date, 'metric' => 'customers'],
            ['value' => $new, 'dimensions' => json_encode(['interacted' => $interacted]), 'updated_at' => now()],
        );
    }

    protected function aggregateAiUsage(Business $business, string $date, string $start, string $end): void
    {
        $usage = AiRequestLog::where('business_id', $business->id)
            ->whereBetween('created_at', [$start, $end])
            ->select(
                DB::raw('COUNT(*) as total'),
                DB::raw("SUM(CASE WHEN response_status = 'success' THEN 1 ELSE 0 END) as successful"),
                DB::raw("SUM(CASE WHEN response_status = 'failed' THEN 1 ELSE 0 END) as failed"),
                DB::raw('COALESCE(SUM(total_tokens), 0) as total_tokens'),
                DB::raw('COALESCE(SUM(cost), 0) as cost'),
            )->first();

        if ($usage && $usage->total > 0) {
            DB::table('analytics_daily')->updateOrInsert(
                ['business_id' => $business->id, 'date' => $date, 'metric' => 'ai_requests'],
                ['value' => $usage->total, 'dimensions' => json_encode([
                    'successful' => $usage->successful,
                    'failed' => $usage->failed,
                    'total_tokens' => $usage->total_tokens,
                    'cost' => $usage->cost,
                ]), 'updated_at' => now()],
            );
        }
    }
}
