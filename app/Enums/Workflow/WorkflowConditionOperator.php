<?php

namespace App\Enums\Workflow;

enum WorkflowConditionOperator: string
{
    case Equals = 'equals';
    case NotEquals = 'not_equals';
    case Contains = 'contains';
    case StartsWith = 'starts_with';
    case EndsWith = 'ends_with';
    case Regex = 'regex';
    case DateBefore = 'date_before';
    case DateAfter = 'date_after';
    case GreaterThan = 'greater_than';
    case GreaterThanOrEqual = 'greater_than_or_equal';
    case LessThan = 'less_than';
    case LessThanOrEqual = 'less_than_or_equal';
    case IsTrue = 'is_true';
    case IsFalse = 'is_false';
}