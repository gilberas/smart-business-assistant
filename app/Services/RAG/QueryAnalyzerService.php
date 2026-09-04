<?php

namespace App\Services\RAG;

class QueryAnalyzerService
{
    public function analyze(string $query): array
    {
        return [
            'original' => $query,
            'normalized' => $this->normalize($query),
            'keywords' => $this->extractKeywords($query),
            'entities' => $this->extractEntities($query),
            'word_count' => str_word_count($query),
            'char_count' => strlen($query),
            'has_question' => $this->hasQuestionMark($query),
            'question_words' => $this->getQuestionWords($query),
            'language' => $this->detectLanguage($query),
        ];
    }

    private function normalize(string $query): string
    {
        $text = mb_strtolower(trim($query));
        $text = preg_replace('/[^\w\s\p{P}]/u', '', $text);
        return preg_replace('/\s+/', ' ', $text);
    }

    private function extractKeywords(string $query): array
    {
        $query = mb_strtolower($query);
        $stopWords = ['the', 'a', 'an', 'is', 'are', 'was', 'were', 'in', 'on', 'at', 'to', 'for',
            'of', 'and', 'or', 'but', 'it', 'its', 'this', 'that', 'with', 'from', 'by', 'be',
            'how', 'what', 'when', 'where', 'why', 'which', 'who', 'whom', 'whose', 'do', 'does',
            'did', 'will', 'would', 'could', 'should', 'can', 'may', 'might', 'shall', 'has',
            'have', 'had', 'not', 'no', 'nor', 'so', 'if', 'about', 'into', 'over', 'after',
            'before', 'between', 'under', 'above', 'below', 'up', 'down', 'out', 'off', 'then',
            'than', 'also', 'very', 'just', 'because', 'as', 'until', 'while', 'please', 'help'];

        $words = str_word_count($query, 1);
        $keywords = [];

        foreach ($words as $word) {
            $word = trim($word);
            if (strlen($word) > 2 && !in_array($word, $stopWords)) {
                $keywords[] = $word;
            }
        }

        return array_values(array_unique($keywords));
    }

    private function extractEntities(string $query): array
    {
        $entities = [];

        if (preg_match('/\b(\d{3}[-.]?\d{3}[-.]?\d{4})\b/', $query, $m)) {
            $entities['phone'][] = $m[1];
        }

        if (preg_match('/\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}\b/', $query, $m)) {
            $entities['email'][] = $m[0];
        }

        if (preg_match('/\b(\d+\.?\d*)\s*(usd|eur|gbp|\$|€|£)\b/i', $query, $m)) {
            $entities['price'][] = $m[0];
        }

        if (preg_match('/\b(order|booking|invoice|ticket|#)\s*#?(\w{5,20})\b/i', $query, $m)) {
            $entities['reference'][] = $m[2];
        }

        return $entities;
    }

    private function hasQuestionMark(string $query): bool
    {
        return str_contains($query, '?');
    }

    private function getQuestionWords(string $query): array
    {
        $words = ['what', 'when', 'where', 'why', 'how', 'who', 'whom', 'whose', 'which', 'is', 'are',
            'can', 'could', 'would', 'should', 'will', 'did', 'do', 'does', 'has', 'have', 'had'];
        $found = [];
        $query = mb_strtolower($query);
        foreach ($words as $word) {
            if (preg_match('/\b' . preg_quote($word, '/') . '\b/', $query)) {
                $found[] = $word;
            }
        }
        return $found;
    }

    private function detectLanguage(string $query): string
    {
        if (preg_match('/[а-яА-ЯЁё]/u', $query)) {
            return 'ru';
        }
        if (preg_match('/[áéíóúñçãõàèìòùäëïöü]/i', $query)) {
            return 'es';
        }
        return 'en';
    }
}
