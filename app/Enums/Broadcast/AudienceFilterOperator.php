<?php

namespace App\Enums\Broadcast;

enum AudienceFilterOperator: string
{
    case Equals = 'equals';
    case NotEquals = 'not_equals';
    case In = 'in';
    case NotIn = 'not_in';
    case Contains = 'contains';
    case GreaterThan = 'greater_than';
    case LessThan = 'less_than';
    case DateBefore = 'date_before';
    case DateAfter = 'date_after';
    case IsSet = 'is_set';
    case IsNotSet = 'is_not_set';

    public function label(): string
    {
        return match ($this) {
            self::Equals => 'Equals',
            self::NotEquals => 'Not equals',
            self::In => 'In',
            self::NotIn => 'Not in',
            self::Contains => 'Contains',
            self::GreaterThan => 'Greater than',
            self::LessThan => 'Less than',
            self::DateBefore => 'Date before',
            self::DateAfter => 'Date after',
            self::IsSet => 'Is set',
            self::IsNotSet => 'Is not set',
        };
    }
}
