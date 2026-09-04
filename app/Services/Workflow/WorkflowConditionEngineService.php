<?php

namespace App\Services\Workflow;

use App\Models\WorkflowCondition;

class WorkflowConditionEngineService
{
    public function matches(WorkflowCondition $condition, array $context): bool
    {
        $actual = data_get($context, $condition->field);
        $expected = $condition->value;

        return match ($condition->operator) {
            'equals' => $actual == $expected,
            'not_equals' => $actual != $expected,
            'contains' => is_string($actual) && str_contains(mb_strtolower($actual), mb_strtolower((string) data_get($expected, 0, $expected))),
            'starts_with' => is_string($actual) && str_starts_with(mb_strtolower($actual), mb_strtolower((string) $expected)),
            'ends_with' => is_string($actual) && str_ends_with(mb_strtolower($actual), mb_strtolower((string) $expected)),
            'regex' => is_string($actual) && @preg_match('/' . str_replace('/', '\/', (string) $expected) . '/', $actual) === 1,
            'date_before' => strtotime((string) $actual) < strtotime((string) $expected),
            'date_after' => strtotime((string) $actual) > strtotime((string) $expected),
            'greater_than' => (float) $actual > (float) $expected,
            'greater_than_or_equal' => (float) $actual >= (float) $expected,
            'less_than' => (float) $actual < (float) $expected,
            'less_than_or_equal' => (float) $actual <= (float) $expected,
            'is_true' => (bool) $actual === true,
            'is_false' => (bool) $actual === false,
            default => false,
        };
    }
}