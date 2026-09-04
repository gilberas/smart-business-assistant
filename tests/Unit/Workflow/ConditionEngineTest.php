<?php

use App\Enums\Workflow\WorkflowConditionOperator;
use App\Models\WorkflowCondition;
use App\Services\Workflow\ConditionEngineService;

beforeEach(function () {
    $this->engine = app(ConditionEngineService::class);
});

it('evaluates equals operator', function () {
    $condition = createCondition('equals', 'hello');
    expect($this->engine->matches($condition, ['field' => 'hello']))->toBeTrue();
    expect($this->engine->matches($condition, ['field' => 'world']))->toBeFalse();
});

it('evaluates not_equals operator', function () {
    $condition = createCondition('not_equals', 'hello');
    expect($this->engine->matches($condition, ['field' => 'world']))->toBeTrue();
    expect($this->engine->matches($condition, ['field' => 'hello']))->toBeFalse();
});

it('evaluates contains operator', function () {
    $condition = createCondition('contains', 'test');
    expect($this->engine->matches($condition, ['field' => 'this is a test']))->toBeTrue();
    expect($this->engine->matches($condition, ['field' => 'nothing here']))->toBeFalse();
});

it('evaluates numeric comparisons', function () {
    expect($this->engine->matches(createCondition('greater_than', 5), ['field' => 10]))->toBeTrue();
    expect($this->engine->matches(createCondition('less_than', 10), ['field' => 5]))->toBeTrue();
    expect($this->engine->matches(createCondition('greater_than_or_equal', 5), ['field' => 5]))->toBeTrue();
});

it('evaluates boolean conditions', function () {
    expect($this->engine->matches(createCondition('is_true', null), ['field' => true]))->toBeTrue();
    expect($this->engine->matches(createCondition('is_false', null), ['field' => false]))->toBeTrue();
});

it('evaluates empty/not_empty conditions', function () {
    expect($this->engine->matches(createCondition('is_empty', null), ['field' => '']))->toBeTrue();
    expect($this->engine->matches(createCondition('is_not_empty', null), ['field' => 'value']))->toBeTrue();
});

it('evaluates date comparisons', function () {
    expect($this->engine->matches(createCondition('date_before', '2025-01-01'), ['field' => '2024-06-15']))->toBeTrue();
    expect($this->engine->matches(createCondition('date_after', '2025-01-01'), ['field' => '2025-06-15']))->toBeTrue();
});

it('evaluates regex operator', function () {
    $condition = createCondition('regex', '^[A-Z].+');
    expect($this->engine->matches($condition, ['field' => 'Hello World']))->toBeTrue();
    expect($this->engine->matches($condition, ['field' => 'hello world']))->toBeFalse();
});

it('evaluates in_array operator', function () {
    $condition = createCondition('in_array', ['red', 'blue', 'green']);
    expect($this->engine->matches($condition, ['field' => 'blue']))->toBeTrue();
    expect($this->engine->matches($condition, ['field' => 'yellow']))->toBeFalse();
});

it('evaluates condition groups with AND connector', function () {
    $conditions = [
        createCondition('equals', 'active', 'group1', 0, 'and'),
        createCondition('greater_than', 10, 'group1', 1, 'and'),
    ];
    expect($this->engine->evaluateGroup($conditions, ['field' => 'active', 'field2' => 15]))->toBeTrue();
    expect($this->engine->evaluateGroup($conditions, ['field' => 'active', 'field2' => 5]))->toBeFalse();
});

it('evaluates condition groups with OR connector', function () {
    $conditions = [
        createCondition('equals', 'admin', 'group1', 0, 'or'),
        createCondition('equals', 'superadmin', 'group1', 1, 'or'),
    ];
    expect($this->engine->evaluateGroup($conditions, ['field' => 'admin'], 'or'))->toBeTrue();
    expect($this->engine->evaluateGroup($conditions, ['field' => 'user'], 'or'))->toBeFalse();
});

it('returns true for empty condition group', function () {
    expect($this->engine->evaluateGroup([], ['field' => 'anything']))->toBeTrue();
});

function createCondition(string $operator, mixed $value, ?string $groupKey = null, int $priority = 0, string $connector = 'and'): WorkflowCondition
{
    $condition = new WorkflowCondition();
    $condition->field = 'field';
    $condition->operator = $operator;
    $condition->value = $value;
    $condition->group_key = $groupKey;
    $condition->priority = $priority;
    $condition->boolean_connector = $connector;

    return $condition;
}
