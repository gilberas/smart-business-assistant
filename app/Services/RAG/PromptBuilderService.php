<?php

namespace App\Services\RAG;

use App\Contracts\RAG\PromptBuilderInterface;
use App\DTOs\RAG\ContextPackageDTO;
use App\DTOs\RAG\PromptPackageDTO;

class PromptBuilderService implements PromptBuilderInterface
{
    private const LANGUAGE_NAMES = [
        'en' => 'English',
        'es' => 'Spanish',
        'fr' => 'French',
        'de' => 'German',
        'pt' => 'Portuguese',
        'sw' => 'Swahili',
    ];

    public function name(): string
    {
        return 'standard';
    }

    public function build(ContextPackageDTO $context, string $userMessage, array $options = []): PromptPackageDTO
    {
        $systemInstruction = $options['system_instruction']
            ?? config('rag.prompt.system_instruction');

        $locale = $context->businessProfile['locale'] ?? null;
        if ($locale && $locale !== 'en') {
            $languageName = self::LANGUAGE_NAMES[$locale] ?? $locale;
            $systemInstruction .= " Respond in {$languageName}.";
        }

        $outputFormatInstruction = $options['output_format_instruction']
            ?? config('rag.prompt.output_format_instruction');

        $maxTokens = $options['max_tokens']
            ?? config('rag.prompt.max_tokens', 8000);

        $businessContext = $this->buildBusinessContext($context);
        $retrievedContext = $this->buildRetrievedContext($context);
        $conversationHistory = $this->buildConversationHistory($context);

        $rawPrompt = $this->assembleRaw(
            $systemInstruction,
            $businessContext,
            $retrievedContext,
            $conversationHistory,
            $outputFormatInstruction,
            $userMessage,
        );

        $totalTokens = (int) ceil(strlen($rawPrompt) / 4);
        $wasTruncated = $totalTokens > $maxTokens;

        if ($wasTruncated) {
            list($retrievedContext, $conversationHistory) = $this->truncate(
                $retrievedContext,
                $conversationHistory,
                $totalTokens - $maxTokens,
            );

            $rawPrompt = $this->assembleRaw(
                $systemInstruction,
                $businessContext,
                $retrievedContext,
                $conversationHistory,
                $outputFormatInstruction,
                $userMessage,
            );

            $totalTokens = (int) ceil(strlen($rawPrompt) / 4);
        }

        return new PromptPackageDTO(
            systemInstruction: $systemInstruction,
            businessContext: $businessContext,
            retrievedContext: $retrievedContext,
            conversationHistory: $conversationHistory,
            userMessage: $userMessage,
            outputFormatInstruction: $outputFormatInstruction,
            totalTokens: $totalTokens,
            availableTokens: $maxTokens - $totalTokens,
            wasTruncated: $wasTruncated,
            contextPackage: $context,
        );
    }

    private function buildBusinessContext(ContextPackageDTO $context): string
    {
        if (empty($context->businessProfile)) {
            return '';
        }

        $parts = [];
        $profile = $context->businessProfile;

        if (!empty($profile['name'])) {
            $parts[] = "Business: {$profile['name']}";
        }

        if (!empty($profile['information'])) {
            foreach ($profile['information'] as $info) {
                $parts[] = "{$info['key']}: {$info['value']}";
            }
        }

        return implode("\n", $parts);
    }

    private function buildRetrievedContext(ContextPackageDTO $context): string
    {
        $sections = [];

        if (!empty($context->relevantFaqs)) {
            $faqText = '';
            foreach ($context->relevantFaqs as $faq) {
                $faqText .= "Q: {$faq['title']}\nA: {$faq['content']}\n\n";
            }
            $sections[] = "[FAQs]\n{$faqText}";
        }

        if (!empty($context->relevantArticles)) {
            $articleText = '';
            foreach ($context->relevantArticles as $article) {
                $articleText .= "Title: {$article['title']}\n{$article['content']}\n\n";
            }
            $sections[] = "[Knowledge Articles]\n{$articleText}";
        }

        if (!empty($context->relevantProducts)) {
            $productText = '';
            foreach ($context->relevantProducts as $product) {
                $price = $product['metadata']['price'] ?? '';
                $currency = $product['metadata']['currency'] ?? '';
                $productText .= "Product: {$product['title']}";
                if ($price) {
                    $productText .= " ({$currency} {$price})";
                }
                $productText .= "\n{$product['content']}\n\n";
            }
            $sections[] = "[Products]\n{$productText}";
        }

        if (!empty($context->relevantServices)) {
            $serviceText = '';
            foreach ($context->relevantServices as $service) {
                $serviceText .= "Service: {$service['title']}\n{$service['content']}\n\n";
            }
            $sections[] = "[Services]\n{$serviceText}";
        }

        if (!empty($context->relevantPolicies)) {
            $policyText = '';
            foreach ($context->relevantPolicies as $policy) {
                $policyText .= "{$policy['title']}: {$policy['content']}\n\n";
            }
            $sections[] = "[Policies]\n{$policyText}";
        }

        if (!empty($context->relevantDocuments)) {
            $docText = '';
            foreach ($context->relevantDocuments as $doc) {
                $docText .= "{$doc['title']}\n{$doc['content']}\n\n";
            }
            $sections[] = "[Documents]\n{$docText}";
        }

        if (!empty($context->suggestedCitations)) {
            $citationText = "Sources:\n";
            foreach ($context->suggestedCitations as $citation) {
                $citationText .= "[{$citation['index']}] {$citation['title']} ({$citation['source_type']})\n";
            }
            $sections[] = $citationText;
        }

        return implode("\n", $sections);
    }

    private function buildConversationHistory(ContextPackageDTO $context): string
    {
        if (empty($context->messageHistory)) {
            return '';
        }

        $parts = [];

        if (!empty($context->conversationSummary)) {
            $summary = $context->conversationSummary;
            $parts[] = "Conversation Status: {$summary['status']}";
            $parts[] = "Message Count: {$summary['message_count']}";
            if (!empty($summary['subject'])) {
                $parts[] = "Subject: {$summary['subject']}";
            }
            $parts[] = '';
        }

        foreach ($context->messageHistory as $msg) {
            $role = $msg['role'] ?? 'unknown';
            $content = $msg['content'] ?? '';
            $parts[] = "{$role}: {$content}";
        }

        return implode("\n", $parts);
    }

    private function assembleRaw(
        string $systemInstruction,
        string $businessContext,
        string $retrievedContext,
        string $conversationHistory,
        string $outputFormat,
        string $userMessage,
    ): string {
        return implode("\n\n", array_filter([
            $systemInstruction,
            $businessContext ? "[Business Information]\n{$businessContext}" : null,
            $retrievedContext ? "[Retrieved Information]\n{$retrievedContext}" : null,
            $conversationHistory ? "[Conversation History]\n{$conversationHistory}" : null,
            $outputFormat,
            "[User Message]\n{$userMessage}",
        ]));
    }

    private function truncate(string $retrievedContext, string $conversationHistory, int $excessTokens): array
    {
        $tokenBudget = $excessTokens * 4;

        if (!empty($conversationHistory)) {
            $historyLen = strlen($conversationHistory);
            if ($historyLen > 0) {
                $truncateFromHistory = min($historyLen, $tokenBudget);
                $conversationHistory = substr($conversationHistory, 0, $historyLen - $truncateFromHistory);
                $tokenBudget -= $truncateFromHistory;
            }
        }

        if ($tokenBudget > 0 && !empty($retrievedContext)) {
            $contextLen = strlen($retrievedContext);
            $truncateFromContext = min($contextLen, $tokenBudget);
            $retrievedContext = substr($retrievedContext, 0, $contextLen - $truncateFromContext);
        }

        return [$retrievedContext, $conversationHistory];
    }
}
