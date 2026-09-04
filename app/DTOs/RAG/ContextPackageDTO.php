<?php

namespace App\DTOs\RAG;

readonly class ContextPackageDTO
{
    public function __construct(
        public string $query,
        public string $intent = 'general',
        public array $businessProfile = [],
        public array $relevantFaqs = [],
        public array $relevantArticles = [],
        public array $relevantProducts = [],
        public array $relevantServices = [],
        public array $relevantPolicies = [],
        public array $relevantDocuments = [],
        public array $customerProfile = [],
        public array $conversationSummary = [],
        public array $messageHistory = [],
        public array $suggestedCitations = [],
        public float $confidenceScore = 0.0,
        public int $tokenEstimate = 0,
        public float $executionTimeMs = 0.0,
        public ?int $retrievalLogId = null,
    ) {}

    public function toArray(): array
    {
        return [
            'query' => $this->query,
            'intent' => $this->intent,
            'business_profile' => $this->businessProfile,
            'relevant_faqs' => $this->relevantFaqs,
            'relevant_articles' => $this->relevantArticles,
            'relevant_products' => $this->relevantProducts,
            'relevant_services' => $this->relevantServices,
            'relevant_policies' => $this->relevantPolicies,
            'relevant_documents' => $this->relevantDocuments,
            'customer_profile' => $this->customerProfile,
            'conversation_summary' => $this->conversationSummary,
            'message_history' => $this->messageHistory,
            'suggested_citations' => $this->suggestedCitations,
            'confidence_score' => round($this->confidenceScore, 4),
            'token_estimate' => $this->tokenEstimate,
            'execution_time_ms' => round($this->executionTimeMs, 2),
            'retrieval_log_id' => $this->retrievalLogId,
        ];
    }

    public function allRelevantContent(): array
    {
        return array_merge(
            $this->relevantFaqs,
            $this->relevantArticles,
            $this->relevantProducts,
            $this->relevantServices,
            $this->relevantPolicies,
            $this->relevantDocuments,
        );
    }
}
