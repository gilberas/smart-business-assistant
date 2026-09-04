<?php

namespace App\Services\Inbox;

use App\DTOs\RAG\ContextPackageDTO;
use App\Enums\Conversation\ConversationStatus;

class InboxDecisionEngineService
{
    public function decide(ContextPackageDTO $context, string $message, array $options = []): array
    {
        $rules = array_replace(config('inbox.decision', []), $options['rules'] ?? []);
        $confidence = $context->confidenceScore;
        $messageLower = mb_strtolower($message);

        if ($this->containsAny($messageLower, $rules['never_reply_topics'] ?? [])) {
            return $this->result('never_reply', 'matched_never_reply_rule', $confidence);
        }

        if (($rules['business_hours_only'] ?? false) && ! $this->isBusinessHours($options['timezone'] ?? null)) {
            return $this->result('human_review', 'outside_business_hours', $confidence);
        }

        if ($this->containsAny($messageLower, $rules['sensitive_topics'] ?? []) && ($rules['escalate_sensitive_topics'] ?? true)) {
            return $this->result('human_takeover', 'sensitive_topic_detected', $confidence);
        }

        if ($confidence < (float) ($rules['unknown_question_confidence'] ?? 0.45)) {
            return $this->result('human_takeover', 'low_confidence', $confidence);
        }

        if ($confidence >= (float) ($rules['auto_reply_confidence'] ?? 0.85) && ($rules['auto_reply_enabled'] ?? false)) {
            return $this->result('auto_send', 'high_confidence', $confidence);
        }

        if ($rules['review_before_send'] ?? true) {
            return $this->result('human_review', 'review_required', $confidence);
        }

        return $this->result('human_takeover', 'default_safe_path', $confidence);
    }

    private function containsAny(string $text, array $needles): bool
    {
        foreach ($needles as $needle) {
            if ($needle !== '' && str_contains($text, mb_strtolower($needle))) {
                return true;
            }
        }

        return false;
    }

    private function isBusinessHours(?string $timezone = null): bool
    {
        $now = $timezone ? now($timezone) : now();
        $day = (int) $now->format('N');
        $hour = (int) $now->format('G');

        return $day >= 1 && $day <= 5 && $hour >= 8 && $hour < 18;
    }

    private function result(string $decision, string $reason, float $confidence): array
    {
        return [
            'decision' => $decision,
            'reason' => $reason,
            'confidence' => round($confidence, 4),
        ];
    }
}