<?php

namespace App\Enums\RAG;

enum SearchSourceType: string
{
    case BusinessInformation = 'business_information';
    case KnowledgeArticle = 'knowledge_article';
    case FAQ = 'faq';
    case Product = 'product';
    case Service = 'service';
    case Policy = 'policy';
    case Document = 'document';
    case Conversation = 'conversation';
    case Customer = 'customer';
    case OrderHistory = 'order_history';
    case BookingHistory = 'booking_history';

    public function label(): string
    {
        return match ($this) {
            self::BusinessInformation => 'Business Information',
            self::KnowledgeArticle => 'Knowledge Article',
            self::FAQ => 'FAQ',
            self::Product => 'Product',
            self::Service => 'Service',
            self::Policy => 'Policy',
            self::Document => 'Document',
            self::Conversation => 'Conversation',
            self::Customer => 'Customer',
            self::OrderHistory => 'Order History',
            self::BookingHistory => 'Booking History',
        };
    }
}
