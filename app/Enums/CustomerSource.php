<?php

namespace App\Enums;

enum CustomerSource: string
{
    case Manual = 'manual';
    case Import = 'import';
    case WhatsApp = 'whatsapp';
    case WebForm = 'web_form';
    case Api = 'api';
    case Referral = 'referral';
    case Social = 'social';
    case Other = 'other';
}
