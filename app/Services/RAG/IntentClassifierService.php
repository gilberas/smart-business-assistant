<?php

namespace App\Services\RAG;

use App\Enums\RAG\SearchIntent;

class IntentClassifierService
{
    public function classify(string $query, ?int $customerId = null, ?int $conversationId = null): SearchIntent
    {
        $query = mb_strtolower(trim($query));

        if ($this->matchPricing($query)) {
            return SearchIntent::Pricing;
        }

        if ($this->matchProduct($query)) {
            return SearchIntent::Product;
        }

        if ($this->matchTroubleshooting($query)) {
            return SearchIntent::Troubleshooting;
        }

        if ($this->matchPolicy($query)) {
            return SearchIntent::Policy;
        }

        if ($this->matchBusinessInfo($query)) {
            return SearchIntent::BusinessInfo;
        }

        if ($this->matchSupport($query)) {
            return SearchIntent::Support;
        }

        return SearchIntent::General;
    }

    private function matchPricing(string $query): bool
    {
        $patterns = [
            'how much', 'price', 'cost', 'pricing', 'fee', 'subscription', 'plan',
            'billing', 'charge', 'rate', 'payment', 'paid', '\$', 'eur', 'usd',
            'how expensive', 'cheap', 'afford', 'budget', 'discount', 'offer',
        ];
        return $this->matchPatterns($query, $patterns);
    }

    private function matchProduct(string $query): bool
    {
        $patterns = [
            'product', 'item', 'buy', 'purchase', 'order', 'in stock', 'available',
            'have you got', 'do you sell', 'do you have', 'looking for', 'need a',
            'recommend', 'suggestion', 'which one', 'compare', 'difference between',
            'specification', 'specs', 'feature', 'variant', 'size', 'color', 'model',
        ];
        return $this->matchPatterns($query, $patterns);
    }

    private function matchTroubleshooting(string $query): bool
    {
        $patterns = [
            'not working', 'broken', 'error', 'issue', 'problem', 'bug', 'fail',
            'doesn\'?t work', 'won\'?t', 'can\'?t', 'fix', 'repair', 'troubleshoot',
            'help with', 'stuck', 'crash', 'freeze', 'malfunction', 'damage',
        ];
        return $this->matchPatterns($query, $patterns);
    }

    private function matchPolicy(string $query): bool
    {
        $patterns = [
            'policy', 'return', 'refund', 'exchange', 'cancel', 'cancellation',
            'warranty', 'guarantee', 'terms', 'condition', 'privacy', 'shipping',
            'delivery', 'shipping time', 'shipment', 'tracking', 'complaint',
        ];
        return $this->matchPatterns($query, $patterns);
    }

    private function matchBusinessInfo(string $query): bool
    {
        $patterns = [
            'hours', 'open', 'closed', 'location', 'address', 'directions',
            'contact', 'phone', 'email', 'call', 'reach', 'find you',
            'holiday', 'schedule', 'appointment', 'booking', 'reservation',
        ];
        return $this->matchPatterns($query, $patterns);
    }

    private function matchSupport(string $query): bool
    {
        $patterns = [
            'speak to', 'talk to', 'human', 'agent', 'representative', 'person',
            'customer service', 'support', 'help desk', 'assistance', 'urgent',
            'immediately', 'asap', 'emergency', 'escalate', 'manager', 'supervisor',
        ];
        return $this->matchPatterns($query, $patterns);
    }

    private function matchPatterns(string $query, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            if (preg_match('/' . $pattern . '/i', $query)) {
                return true;
            }
        }
        return false;
    }
}
