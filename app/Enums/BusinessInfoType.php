<?php

namespace App\Enums;

enum BusinessInfoType: string
{
    case Hours = 'hours';
    case Location = 'location';
    case Contact = 'contact';
    case Social = 'social';
    case Policy = 'policy';
    case Other = 'other';
}
