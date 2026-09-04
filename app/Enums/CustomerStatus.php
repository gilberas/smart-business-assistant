<?php

namespace App\Enums;

enum CustomerStatus: string
{
    case Active = 'active';
    case Archived = 'archived';
    case Inactive = 'inactive';
    case Blacklisted = 'blacklisted';
}
