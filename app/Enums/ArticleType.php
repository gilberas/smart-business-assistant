<?php

namespace App\Enums;

enum ArticleType: string
{
    case Article = 'article';
    case Guide = 'guide';
    case FAQ = 'faq';
    case Glossary = 'glossary';
    case Documentation = 'documentation';
}
