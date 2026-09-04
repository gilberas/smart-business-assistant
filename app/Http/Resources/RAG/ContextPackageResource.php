<?php

namespace App\Http\Resources\RAG;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContextPackageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'query' => $this->query,
            'intent' => $this->intent,
            'business_profile' => $this->businessProfile,
            'relevant_faqs' => $this->relevantFaqs,
            'relevant_articles' => $this->relevantArticles,
            'relevant_products' => $this->relevantProducts,
            'relevant_policies' => $this->relevantPolicies,
            'relevant_documents' => $this->relevantDocuments,
            'customer_profile' => $this->customerProfile,
            'conversation_summary' => $this->conversationSummary,
            'message_history' => $this->messageHistory,
            'suggested_citations' => $this->suggestedCitations,
            'confidence_score' => $this->confidenceScore,
            'token_estimate' => $this->tokenEstimate,
            'execution_time_ms' => $this->executionTimeMs,
            'retrieval_log_id' => $this->retrievalLogId,
        ];
    }
}
