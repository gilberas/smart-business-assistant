<?php

namespace App\Services\RAG;

use App\Contracts\RAG\ContextBuilderInterface;
use App\DTOs\RAG\SearchQueryDTO;
use App\DTOs\RAG\ContextPackageDTO;
use App\DTOs\RAG\SearchResultDTO;
use App\Models\Business;
use App\Models\Customer;
use App\Models\Conversation;
use App\Models\Message;

class ContextBuilderService implements ContextBuilderInterface
{
    public function name(): string
    {
        return 'standard';
    }

    public function build(SearchQueryDTO $query, array $rankedResults): ContextPackageDTO
    {
        $start = microtime(true);

        $businessProfile = $this->getBusinessProfile($query->businessId);
        $customerProfile = $query->customerId ? $this->getCustomerProfile($query->customerId) : [];
        $conversationData = $query->conversationId ? $this->getConversationData($query->conversationId) : [];

        $faqs = $this->filterBySource($rankedResults, 'faq');
        $articles = $this->filterBySource($rankedResults, 'knowledge_article');
        $products = $this->filterBySource($rankedResults, 'product');
        $services = $this->filterBySource($rankedResults, 'service');
        $policies = $this->filterBySource($rankedResults, 'business_information');
        $documents = $this->filterBySource($rankedResults, 'document');

        $allContent = array_merge($faqs, $articles, $products, $services, $policies, $documents);
        $confidenceScore = $this->calculateConfidence($rankedResults);
        $tokenEstimate = $this->estimateTokens($businessProfile, $allContent, $customerProfile, $conversationData);
        $citations = $this->generateCitations($allContent);

        return new ContextPackageDTO(
            query: $query->query,
            intent: $query->intent ?? 'general',
            businessProfile: $businessProfile,
            relevantFaqs: array_map(fn(SearchResultDTO $r) => $r->toArray(), $faqs),
            relevantArticles: array_map(fn(SearchResultDTO $r) => $r->toArray(), $articles),
            relevantProducts: array_map(fn(SearchResultDTO $r) => $r->toArray(), $products),
            relevantServices: array_map(fn(SearchResultDTO $r) => $r->toArray(), $services),
            relevantPolicies: array_map(fn(SearchResultDTO $r) => $r->toArray(), $policies),
            relevantDocuments: array_map(fn(SearchResultDTO $r) => $r->toArray(), $documents),
            customerProfile: $customerProfile,
            conversationSummary: $conversationData['summary'] ?? [],
            messageHistory: $conversationData['messages'] ?? [],
            suggestedCitations: $citations,
            confidenceScore: $confidenceScore,
            tokenEstimate: $tokenEstimate,
            executionTimeMs: (microtime(true) - $start) * 1000,
        );
    }

    private function getBusinessProfile(int $businessId): array
    {
        try {
            $business = Business::with(['information' => function ($q) {
                $q->where('is_public', true);
            }])->find($businessId);

            if (!$business) return [];

            return [
                'name' => $business->name,
                'slug' => $business->slug,
                'timezone' => $business->timezone,
                'locale' => $business->locale,
                'currency' => $business->currency,
                'information' => $business->information?->map(fn($info) => [
                    'key' => $info->key,
                    'value' => $info->value,
                    'type' => $info->type,
                ])->toArray() ?? [],
            ];
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function getCustomerProfile(int $customerId): array
    {
        try {
            $customer = Customer::with(['tags', 'addresses'])->find($customerId);
            if (!$customer) return [];

            return [
                'id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone,
                'email' => $customer->email,
                'notes_count' => $customer->notes()?->count() ?? 0,
                'tags' => $customer->tags?->pluck('name')->toArray() ?? [],
                'total_orders' => $customer->total_orders ?? 0,
                'lifetime_value' => $customer->lifetime_value ?? 0,
                'status' => $customer->status,
            ];
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function getConversationData(int $conversationId): array
    {
        try {
            $conversation = Conversation::with(['messages' => function ($q) {
                $q->latest()->limit(config('rag.context.max_conversation_messages', 10));
            }])->find($conversationId);

            if (!$conversation) return ['summary' => [], 'messages' => []];

            return [
                'summary' => [
                    'id' => $conversation->id,
                    'uuid' => $conversation->uuid,
                    'status' => $conversation->status,
                    'priority' => $conversation->priority,
                    'subject' => $conversation->subject,
                    'message_count' => $conversation->messages_count ?? $conversation->messages->count(),
                    'is_active' => $conversation->isActive(),
                ],
                'messages' => $conversation->messages->reverse()->map(fn(Message $msg) => [
                    'role' => $msg->direction === 'inbound' ? 'customer' : 'agent',
                    'content' => $msg->body ?? $msg->content ?? '',
                    'created_at' => $msg->created_at?->toISOString(),
                ])->toArray(),
            ];
        } catch (\Throwable $e) {
            return ['summary' => [], 'messages' => []];
        }
    }

    private function filterBySource(array $results, string $sourceType): array
    {
        return array_values(array_filter($results, fn(SearchResultDTO $r) => $r->sourceType === $sourceType));
    }

    private function calculateConfidence(array $results): float
    {
        if (empty($results)) return 0;

        $scores = array_map(fn(SearchResultDTO $r) => $r->relevanceScore, $results);
        $avgScore = array_sum($scores) / count($scores);

        $topScore = max($scores);
        $hasHighMatch = $topScore > 0.7 ? 0.2 : ($topScore > 0.4 ? 0.1 : 0);

        return min(1.0, $avgScore + $hasHighMatch);
    }

    private function estimateTokens(array $businessProfile, array $results, array $customerProfile, array $conversationData): int
    {
        $total = 0;

        $total += strlen(json_encode($businessProfile)) / 4;
        $total += strlen(json_encode($customerProfile)) / 4;

        foreach ($results as $result) {
            $total += strlen($result?->content ?? '') / 4;
        }

        foreach ($conversationData['messages'] ?? [] as $msg) {
            $total += strlen($msg['content'] ?? '') / 4;
        }

        return (int) ceil($total);
    }

    private function generateCitations(array $results): array
    {
        $citations = [];
        foreach ($results as $i => $result) {
            if (!empty($result->title) && $result->relevanceScore > 0.3) {
                $citations[] = [
                    'index' => $i + 1,
                    'source_type' => $result->sourceType,
                    'title' => $result->title,
                    'relevance' => round($result->relevanceScore, 2),
                ];
            }
        }
        return $citations;
    }
}
