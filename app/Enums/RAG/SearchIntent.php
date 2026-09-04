<?php

namespace App\Enums\RAG;

enum SearchIntent: string
{
    case General = 'general';
    case Product = 'product';
    case FAQ = 'faq';
    case Policy = 'policy';
    case BusinessInfo = 'business_info';
    case Troubleshooting = 'troubleshooting';
    case Pricing = 'pricing';
    case Support = 'support';

    public function label(): string
    {
        return match ($this) {
            self::General => 'General Inquiry',
            self::Product => 'Product Question',
            self::FAQ => 'Frequently Asked Question',
            self::Policy => 'Policy Question',
            self::BusinessInfo => 'Business Information',
            self::Troubleshooting => 'Troubleshooting',
            self::Pricing => 'Pricing Question',
            self::Support => 'Support Request',
        };
    }

    public function relevantSources(): array
    {
        return match ($this) {
            self::Product => ['product', 'service', 'knowledge_article', 'faq'],
            self::FAQ => ['faq', 'knowledge_article'],
            self::Policy => ['business_information', 'knowledge_article', 'document'],
            self::BusinessInfo => ['business_information', 'customer', 'conversation'],
            self::Troubleshooting => ['knowledge_article', 'faq', 'conversation', 'document'],
            self::Pricing => ['product', 'service', 'business_information'],
            self::Support => ['knowledge_article', 'faq', 'conversation', 'customer'],
            default => ['knowledge_article', 'faq', 'product', 'service', 'business_information', 'document', 'conversation', 'customer'],
        };
    }
}
