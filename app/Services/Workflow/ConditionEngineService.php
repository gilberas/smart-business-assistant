<?php

namespace App\Services\Workflow;

use App\Contracts\Workflow\ConditionEvaluatorInterface;
use App\Models\WorkflowCondition;

class ConditionEngineService implements ConditionEvaluatorInterface
{
    public function matches(WorkflowCondition $condition, array $context): bool
    {
        return $this->evaluate($condition, $context);
    }

    public function evaluate(WorkflowCondition $condition, array $context): bool
    {
        $actual = data_get($context, $condition->field);
        $expected = $condition->value;

        $result = match ($condition->operator) {
            'equals' => $this->evaluateEquals($actual, $expected),
            'not_equals' => !$this->evaluateEquals($actual, $expected),
            'contains' => is_string($actual) && str_contains(mb_strtolower($actual), mb_strtolower((string) (is_array($expected) ? ($expected[0] ?? '') : $expected))),
            'not_contains' => is_string($actual) && !str_contains(mb_strtolower($actual), mb_strtolower((string) (is_array($expected) ? ($expected[0] ?? '') : $expected))),
            'starts_with' => is_string($actual) && str_starts_with(mb_strtolower($actual), mb_strtolower((string) $expected)),
            'ends_with' => is_string($actual) && str_ends_with(mb_strtolower($actual), mb_strtolower((string) $expected)),
            'regex' => is_string($actual) && @preg_match('/' . str_replace('/', '\/', (string) $expected) . '/', $actual) === 1,
            'date_before' => strtotime((string) $actual) < strtotime((string) $expected),
            'date_after' => strtotime((string) $actual) > strtotime((string) $expected),
            'date_on_or_before' => strtotime((string) $actual) <= strtotime((string) $expected),
            'date_on_or_after' => strtotime((string) $actual) >= strtotime((string) $expected),
            'greater_than' => (float) $actual > (float) $expected,
            'greater_than_or_equal' => (float) $actual >= (float) $expected,
            'less_than' => (float) $actual < (float) $expected,
            'less_than_or_equal' => (float) $actual <= (float) $expected,
            'is_true' => (bool) $actual === true,
            'is_false' => (bool) $actual === false,
            'is_empty' => empty($actual),
            'is_not_empty' => !empty($actual),
            'in_array' => is_array($expected) && in_array($actual, $expected, true),
            'not_in_array' => is_array($expected) && !in_array($actual, $expected, true),
            'has_tag' => $this->evaluateHasTag($actual, $expected),
            'not_has_tag' => !$this->evaluateHasTag($actual, $expected),
            'business_hours' => $this->evaluateBusinessHours($context),
            'ai_confidence_gte' => (float) data_get($context, 'ai_confidence', 0) >= (float) $expected,
            'language_equals' => mb_strtolower((string) $actual) === mb_strtolower((string) $expected),
            'message_type' => (string) $actual === (string) $expected,
            default => false,
        };

        return $result;
    }

    public function evaluateGroup(array $conditions, array $context, string $booleanConnector = 'and'): bool
    {
        if (empty($conditions)) {
            return true;
        }

        $results = array_map(fn (WorkflowCondition $c) => $this->evaluate($c, $context), $conditions);

        return $booleanConnector === 'or'
            ? in_array(true, $results, true)
            : !in_array(false, $results, true);
    }

    private function evaluateEquals(mixed $actual, mixed $expected): bool
    {
        if (is_array($expected)) {
            $expected = $expected[0] ?? $expected;
        }

        if (is_string($actual) && is_string($expected)) {
            return mb_strtolower($actual) === mb_strtolower($expected);
        }

        return $actual == $expected;
    }

    private function evaluateHasTag(mixed $actual, mixed $expected): bool
    {
        $tagName = is_array($expected) ? ($expected[0] ?? '') : (string) $expected;
        $tags = data_get((array) $actual, 'tags', is_array($actual) ? $actual : []);

        if (is_string($actual)) {
            return str_contains(mb_strtolower($actual), mb_strtolower($tagName));
        }

        foreach ($tags as $tag) {
            $tagValue = is_string($tag) ? $tag : ($tag['name'] ?? $tag['value'] ?? '');
            if (mb_strtolower((string) $tagValue) === mb_strtolower($tagName)) {
                return true;
            }
        }

        return false;
    }

    private function evaluateBusinessHours(array $context): bool
    {
        $businessHours = data_get($context, 'business_hours', []);

        if (empty($businessHours)) {
            return true;
        }

        $now = now();
        $dayName = mb_strtolower($now->format('l'));
        $todayHours = $businessHours[$dayName] ?? $businessHours['default'] ?? null;

        if (!$todayHours || !isset($todayHours['open'], $todayHours['close'])) {
            return false;
        }

        $current = (int) $now->format('Hi');

        return $current >= (int) str_replace(':', '', $todayHours['open'])
            && $current <= (int) str_replace(':', '', $todayHours['close']);
    }
}
