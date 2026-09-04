<?php

namespace App\Services\Analytics;

use App\DTOs\Analytics\AnalyticsFilterDTO;
use App\Models\Business;
use App\Services\Analytics\Calculators\AICalculator;
use App\Services\Analytics\Calculators\AgentCalculator;
use App\Services\Analytics\Calculators\CampaignCalculator;
use App\Services\Analytics\Calculators\ConversationCalculator;
use App\Services\Analytics\Calculators\CustomerCalculator;
use App\Services\Analytics\Calculators\KnowledgeCalculator;
use App\Services\Analytics\Calculators\OverviewCalculator;
use App\Services\Analytics\Calculators\WorkflowCalculator;

class AnalyticsService
{
    public function __construct(
        private readonly OverviewCalculator $overviewCalc,
        private readonly ConversationCalculator $conversationCalc,
        private readonly CustomerCalculator $customerCalc,
        private readonly AICalculator $aiCalc,
        private readonly AgentCalculator $agentCalc,
        private readonly CampaignCalculator $campaignCalc,
        private readonly WorkflowCalculator $workflowCalc,
        private readonly KnowledgeCalculator $knowledgeCalc,
    ) {}

    public function overview(Business $business, AnalyticsFilterDTO $filter): array
    {
        return $this->overviewCalc->calculate($business, $filter);
    }

    public function conversations(Business $business, AnalyticsFilterDTO $filter): array
    {
        return $this->conversationCalc->summary($business, $filter);
    }

    public function customers(Business $business, AnalyticsFilterDTO $filter): array
    {
        return $this->customerCalc->summary($business, $filter);
    }

    public function ai(Business $business, AnalyticsFilterDTO $filter): array
    {
        return $this->aiCalc->summary($business, $filter);
    }

    public function agents(Business $business, AnalyticsFilterDTO $filter): array
    {
        return $this->agentCalc->summary($business, $filter);
    }

    public function campaigns(Business $business, AnalyticsFilterDTO $filter): array
    {
        return $this->campaignCalc->summary($business, $filter);
    }

    public function workflows(Business $business, AnalyticsFilterDTO $filter): array
    {
        return $this->workflowCalc->summary($business, $filter);
    }

    public function knowledge(Business $business, AnalyticsFilterDTO $filter): array
    {
        return $this->knowledgeCalc->summary($business, $filter);
    }

    public function clearCache(Business $business): void
    {
        $prefix = "analytics:{$business->id}:";
        $metrics = ['overview', 'conversation_summary', 'customer_summary', 'ai_summary', 'agent_summary', 'campaign_summary', 'workflow_summary', 'knowledge_summary'];

        foreach ($metrics as $metric) {
            foreach ($this->scanKeys("{$prefix}{$metric}:*") as $redisKey) {
                \Illuminate\Support\Facades\Cache::forget(str_replace(config('cache.prefix').':', '', $redisKey));
            }
        }
    }

    private function scanKeys(string $pattern): array
    {
        $keys = [];
        $cursor = null;

        do {
            $result = \Illuminate\Support\Facades\Redis::scan($cursor, ['match' => $pattern, 'count' => 100]);

            if (is_array($result) && isset($result[1]) && is_array($result[1])) {
                $keys = array_merge($keys, $result[1]);
                $cursor = is_array($result[0]) ? $result[0] : (int) $result[0];
            } elseif (is_array($result) && $result !== []) {
                $keys = array_merge($keys, $result);
                $cursor = null;
            } else {
                break;
            }
        } while ((int) $cursor !== 0);

        return array_values(array_unique($keys));
    }
}
