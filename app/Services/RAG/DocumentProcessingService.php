<?php

namespace App\Services\RAG;

use App\Contracts\RAG\DocumentProcessorInterface;
use App\Enums\RAG\IngestionStatus;
use App\Models\RAG\DocumentIngestion;
use App\Models\RAG\KnowledgeChunk;
use App\Repositories\RAG\DocumentIngestionRepository;
use App\Repositories\RAG\KnowledgeChunkRepository;
use App\Services\RAG\Chunking\ChunkingStrategyFactory;

class DocumentProcessingService implements DocumentProcessorInterface
{
    public function __construct(
        private readonly ChunkingService $chunkingService,
        private readonly ChunkingStrategyFactory $strategyFactory,
        private readonly KnowledgeChunkRepository $chunkRepository,
        private readonly DocumentIngestionRepository $ingestionRepository,
    ) {}

    public function name(): string
    {
        return 'document_processor';
    }

    public function process(object $ingestible, array $options = []): DocumentIngestion
    {
        $businessId = $ingestible->business_id;
        $strategy = $options['strategy'] ?? config('rag.chunking.default_strategy', 'paragraph');

        $ingestion = $this->ingestionRepository->create([
            'business_id' => $businessId,
            'ingestible_type' => get_class($ingestible),
            'ingestible_id' => $ingestible->id,
            'status' => IngestionStatus::Processing,
            'strategy' => $strategy,
            'metadata' => $options,
        ]);

        try {
            $content = $this->extractContent($ingestible);
            $chunks = $this->chunkingService->chunk($content, $strategy, $options);

            foreach ($chunks as $index => $chunkDTO) {
                $this->chunkRepository->create([
                    'business_id' => $businessId,
                    'chunkable_type' => get_class($ingestible),
                    'chunkable_id' => $ingestible->id,
                    'source_type' => $this->getSourceType($ingestible),
                    'source_id' => $ingestible->id,
                    'chunk_index' => $index,
                    'strategy' => $strategy,
                    'content' => $chunkDTO->content,
                    'heading' => $chunkDTO->heading,
                    'word_count' => $chunkDTO->wordCount,
                    'char_count' => $chunkDTO->charCount,
                    'token_estimate' => $chunkDTO->tokenEstimate,
                    'is_active' => true,
                ]);
            }

            $ingestion->update([
                'status' => IngestionStatus::Completed,
                'total_chunks' => count($chunks),
                'processed_chunks' => count($chunks),
                'completed_at' => now(),
            ]);
        } catch (\Throwable $e) {
            $ingestion->update([
                'status' => IngestionStatus::Failed,
                'error_message' => $e->getMessage(),
            ]);
            throw $e;
        }

        return $ingestion->fresh();
    }

    public function reprocess(DocumentIngestion $ingestion, array $options = []): DocumentIngestion
    {
        $ingestible = $ingestion->ingestible;

        KnowledgeChunk::where('business_id', $ingestion->business_id)
            ->where('chunkable_type', $ingestion->ingestible_type)
            ->where('chunkable_id', $ingestion->ingestible_id)
            ->delete();

        return $this->process($ingestible, $options);
    }

    private function extractContent(object $ingestible): string
    {
        if (method_exists($ingestible, 'getContentAttribute')) {
            return $ingestible->getContentAttribute();
        }

        $content = '';
        foreach (['content', 'body', 'text', 'answer', 'description', 'value'] as $field) {
            if (isset($ingestible->{$field})) {
                $content .= $ingestible->{$field} . "\n\n";
            }
        }

        foreach (['title', 'name', 'question', 'key'] as $field) {
            if (isset($ingestible->{$field})) {
                $content = $ingestible->{$field} . "\n\n" . $content;
                break;
            }
        }

        return trim($content);
    }

    private function getSourceType(object $ingestible): string
    {
        $class = get_class($ingestible);
        return match ($class) {
            'App\Models\KnowledgeArticle' => 'knowledge_article',
            'App\Models\KnowledgeBaseArticle' => 'knowledge_article',
            'App\Models\KnowledgeFaq' => 'faq',
            'App\Models\Product' => 'product',
            'App\Models\BusinessInformation' => 'business_information',
            default => strtolower(class_basename($class)),
        };
    }
}
