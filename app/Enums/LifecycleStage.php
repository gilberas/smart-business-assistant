<?php

namespace App\Enums;

enum LifecycleStage: string
{
    case Lead = 'lead';
    case Prospect = 'prospect';
    case Active = 'active';
    case AtRisk = 'at_risk';
    case Churned = 'churned';
    case VIP = 'vip';
}
