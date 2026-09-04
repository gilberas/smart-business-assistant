<?php

use App\DTOs\RAG\SearchQueryDTO;
use App\DTOs\RAG\SearchResultDTO;
use App\DTOs\RAG\SearchResponseDTO;
use App\DTOs\RAG\ContextPackageDTO;
use App\DTOs\RAG\PromptPackageDTO;
use App\DTOs\RAG\ChunkDTO;
use App\DTOs\RAG\RankingScoreDTO;
use App\Enums\RAG\SearchSourceType;
use App\Enums\RAG\ChunkStrategyType;
use App\Enums\RAG\IngestionStatus;
use App\Enums\RAG\SearchIntent;
use App\Models\Business;
use App\Models\User;
use App\Models\KnowledgeArticle;
use App\Models\KnowledgeFaq;
use App\Models\Product;
use App\Models\BusinessInformation;
use App\Models\RAG\KnowledgeChunk;
use App\Models\RAG\RetrievalLog;
use App\Models\RAG\DocumentIngestion;
use App\Services\RAG\ChunkingService;
use App\Services\RAG\RankingService;
use App\Services\RAG\QueryAnalyzerService;
use App\Services\RAG\IntentClassifierService;
use App\Services\RAG\SearchService;
use App\Services\RAG\ContextBuilderService;
use App\Services\RAG\PromptBuilderService;
use App\Services\RAG\RetrievalService;
use App\Services\RAG\DocumentProcessingService;
use App\Services\RAG\Chunking\ParagraphChunkStrategy;
use App\Services\RAG\Chunking\SectionChunkStrategy;
use App\Services\RAG\Chunking\SlidingWindowChunkStrategy;
use App\Services\RAG\Chunking\FixedSizeChunkStrategy;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ─── Enum Tests ─────────────────────────────────────────────────────────

describe('RAG Enums', function () {
    it('SearchSourceType has correct cases', function () {
        expect(SearchSourceType::KnowledgeArticle->value)->toBe('knowledge_article');
        expect(SearchSourceType::FAQ->value)->toBe('faq');
        expect(SearchSourceType::Product->value)->toBe('product');
        expect(SearchSourceType::BusinessInformation->value)->toBe('business_information');
    });

    it('SearchSourceType returns labels', function () {
        expect(SearchSourceType::KnowledgeArticle->label())->toBe('Knowledge Article');
        expect(SearchSourceType::FAQ->label())->toBe('FAQ');
    });

    it('ChunkStrategyType has correct cases', function () {
        expect(ChunkStrategyType::Paragraph->value)->toBe('paragraph');
        expect(ChunkStrategyType::Section->value)->toBe('section');
        expect(ChunkStrategyType::SlidingWindow->value)->toBe('sliding_window');
        expect(ChunkStrategyType::FixedSize->value)->toBe('fixed_size');
    });

    it('IngestionStatus has terminal states', function () {
        expect(IngestionStatus::Completed->isTerminal())->toBeTrue();
        expect(IngestionStatus::Failed->isTerminal())->toBeTrue();
        expect(IngestionStatus::Pending->isTerminal())->toBeFalse();
        expect(IngestionStatus::Processing->isTerminal())->toBeFalse();
    });

    it('SearchIntent provides relevant sources', function () {
        $sources = SearchIntent::Pricing->relevantSources();
        expect($sources)->toContain('product');
        expect($sources)->toContain('business_information');

        $sources = SearchIntent::BusinessInfo->relevantSources();
        expect($sources)->toContain('business_information');
    });
});

// ─── DTO Tests ──────────────────────────────────────────────────────────

describe('RAG DTOs', function () {
    it('SearchQueryDTO creates from request', function () {
        $dto = SearchQueryDTO::fromRequest(['query' => 'test query', 'max_results' => 20], 1);
        expect($dto->query)->toBe('test query');
        expect($dto->businessId)->toBe(1);
        expect($dto->maxResults)->toBe(20);
    });

    it('SearchQueryDTO uses defaults', function () {
        $dto = SearchQueryDTO::fromRequest(['query' => 'hello'], 1);
        expect($dto->maxResults)->toBe(config('rag.retrieval.max_results_per_source', 5));
        expect($dto->minScore)->toBe(config('rag.retrieval.min_relevance_score', 0.1));
    });

    it('SearchResultDTO can be created and serialized', function () {
        $dto = new SearchResultDTO(
            sourceType: 'knowledge_article',
            sourceId: 1,
            title: 'Test Article',
            content: 'Test content',
            relevanceScore: 0.85,
            tags: ['tag1', 'tag2'],
        );
        $arr = $dto->toArray();
        expect($arr['source_type'])->toBe('knowledge_article');
        expect($arr['relevance_score'])->toBe(0.85);
        expect($arr['tags'])->toBe(['tag1', 'tag2']);
    });

    it('SearchResponseDTO returns top results', function () {
        $results = [];
        for ($i = 0; $i < 10; $i++) {
            $results[] = new SearchResultDTO(sourceType: 'article', sourceId: $i, relevanceScore: $i / 10);
        }
        $response = new SearchResponseDTO(query: 'test', results: $results, totalResults: 10);
        expect(count($response->topResults(3)))->toBe(3);
    });

    it('ChunkDTO calculates correctly', function () {
        $dto = new ChunkDTO(chunkIndex: 0, content: 'Hello world', wordCount: 2, charCount: 11, tokenEstimate: 3);
        expect($dto->wordCount)->toBe(2);
        expect($dto->tokenEstimate)->toBe(3);
    });

    it('RankingScoreDTO computes total', function () {
        $score = new RankingScoreDTO(
            keywordRelevance: 1.0,
            categoryRelevance: 0.5,
            tagRelevance: 0.0,
            popularity: 0.0,
            recency: 1.0,
            businessPriority: 0.5,
        );
        $total = $score->total();
        expect($total)->toBeGreaterThan(0);
        expect($total)->toBeLessThanOrEqual(1.0);
    });

    it('ContextPackageDTO structures correctly', function () {
        $dto = new ContextPackageDTO(
            query: 'test',
            businessProfile: ['name' => 'TestBiz'],
            relevantFaqs: [['question' => 'Q1']],
            confidenceScore: 0.9,
            tokenEstimate: 500,
        );
        expect($dto->businessProfile['name'])->toBe('TestBiz');
        expect($dto->confidenceScore)->toBe(0.9);
    });

    it('PromptPackageDTO assembles prompt', function () {
        $dto = new PromptPackageDTO(
            systemInstruction: 'You are a helpful assistant',
            businessContext: 'Business: TestCo',
            retrievedContext: 'FAQ: How to return?',
            conversationHistory: 'User: Hi',
            userMessage: 'How do I return?',
            outputFormatInstruction: 'Be concise',
        );
        $assembled = $dto->assemble();
        expect($assembled)->toContain('You are a helpful assistant');
        expect($assembled)->toContain('Business: TestCo');
        expect($assembled)->toContain('How do I return?');
        expect($assembled)->toContain('[USER MESSAGE]');
    });
});

// ─── Chunking Strategy Tests ────────────────────────────────────────────

describe('Chunking Strategies', function () {
    it('ParagraphChunkStrategy splits by paragraphs', function () {
        $strategy = new ParagraphChunkStrategy();
        $content = "First paragraph content.\n\nSecond paragraph content.\n\nThird paragraph content.";
        $chunks = $strategy->chunk($content);
        expect(count($chunks))->toBeGreaterThanOrEqual(1);
        expect($chunks[0]->strategy)->toBe('paragraph');
    });

    it('SectionChunkStrategy detects headings', function () {
        $strategy = new SectionChunkStrategy();
        $content = "# Introduction\n\nThis is the intro.\n\n# Details\n\nMore details here.";
        expect($strategy->supports($content))->toBeTrue();
        $chunks = $strategy->chunk($content);
        expect(count($chunks))->toBeGreaterThanOrEqual(2);
    });

    it('SlidingWindowChunkStrategy creates overlapping chunks', function () {
        $strategy = new SlidingWindowChunkStrategy();
        $words = implode(' ', array_fill(0, 100, 'word'));
        $chunks = $strategy->chunk($words, ['size' => 20, 'overlap' => 5]);
        expect(count($chunks))->toBeGreaterThan(1);
    });

    it('FixedSizeChunkStrategy creates fixed-size chunks', function () {
        $strategy = new FixedSizeChunkStrategy();
        $words = implode(' ', array_fill(0, 100, 'word'));
        $chunks = $strategy->chunk($words, ['size' => 20, 'overlap' => 5]);
        expect(count($chunks))->toBeGreaterThan(1);
    });

    it('ChunkingService can chunk with default strategy', function () {
        $service = app(ChunkingService::class);
        $chunks = $service->chunk("Paragraph one.\n\nParagraph two.");
        expect(count($chunks))->toBeGreaterThanOrEqual(1);
        expect($service->defaultStrategy())->toBe(config('rag.chunking.default_strategy', 'paragraph'));
    });
});

// ─── Query Analyzer Tests ───────────────────────────────────────────────

describe('QueryAnalyzerService', function () {
    it('analyzes basic query', function () {
        $service = new QueryAnalyzerService();
        $result = $service->analyze('How much does the premium plan cost?');
        expect($result['has_question'])->toBeTrue();
        expect($result['question_words'])->toContain('how');
        expect($result['word_count'])->toBeGreaterThan(0);
    });

    it('extracts keywords removing stop words', function () {
        $service = new QueryAnalyzerService();
        $result = $service->analyze('What is the price of your product?');
        expect($result['keywords'])->toContain('price');
        expect($result['keywords'])->toContain('product');
        expect($result['keywords'])->not->toContain('the');
        expect($result['keywords'])->not->toContain('what');
    });

    it('detects entities like phone and email', function () {
        $service = new QueryAnalyzerService();
        $result = $service->analyze('Call me at 555-123-4567 or email support@example.com');
        expect($result['entities'])->toHaveKey('phone');
        expect($result['entities'])->toHaveKey('email');
    });
});

// ─── Intent Classifier Tests ────────────────────────────────────────────

describe('IntentClassifierService', function () {
    it('classifies pricing queries', function () {
        $service = new IntentClassifierService();
        expect($service->classify('How much does it cost?')->value)->toBe('pricing');
        expect($service->classify('What are your prices?')->value)->toBe('pricing');
    });

    it('classifies product queries', function () {
        $service = new IntentClassifierService();
        expect($service->classify('What products do you have?')->value)->toBe('product');
        expect($service->classify('Do you sell widgets?')->value)->toBe('product');
    });

    it('classifies business info queries', function () {
        $service = new IntentClassifierService();
        expect($service->classify('What are your hours?')->value)->toBe('business_info');
        expect($service->classify('Where is your location?')->value)->toBe('business_info');
    });

    it('classifies support queries', function () {
        $service = new IntentClassifierService();
        expect($service->classify('I want to talk to someone')->value)->toBe('support');
    });

    it('defaults to general for unknown queries', function () {
        $service = new IntentClassifierService();
        expect($service->classify('Nice weather today')->value)->toBe('general');
    });
});

// ─── Ranking Service Tests ──────────────────────────────────────────────

describe('RankingService', function () {
    it('scores keyword relevance', function () {
        $service = app(RankingService::class);
        $result = new SearchResultDTO(
            sourceType: 'article',
            sourceId: 1,
            title: 'Premium Plan Pricing',
            content: 'The premium plan costs $99 per month.',
        );
        $score = $service->score($result, 'premium plan pricing');
        expect($score->keywordRelevance)->toBeGreaterThan(0);
    });

    it('scores recency higher for recent content', function () {
        $service = app(RankingService::class);
        $today = new SearchResultDTO(sourceType: 'article', sourceId: 1, updatedAt: \Carbon\Carbon::now());
        $old = new SearchResultDTO(sourceType: 'article', sourceId: 2, updatedAt: \Carbon\Carbon::now()->subMonths(18));

        $todayScore = $service->score($today, 'test');
        $oldScore = $service->score($old, 'test');
        expect($todayScore->recency)->toBe(1.0);
        expect($oldScore->recency)->toBeLessThan(0.5);
    });

    it('ranks results in correct order', function () {
        $service = app(RankingService::class);
        $results = [
            new SearchResultDTO(sourceType: 'article', sourceId: 1, title: 'Low relevance', relevanceScore: 0.1),
            new SearchResultDTO(sourceType: 'article', sourceId: 2, title: 'High relevance', relevanceScore: 0.9),
        ];
        $ranked = $service->rank($results, 'test');
        expect($ranked[0]->relevanceScore)->toBeGreaterThanOrEqual($ranked[1]->relevanceScore);
    });
});

// ─── Search & Retrieval Tests ───────────────────────────────────────────

describe('Search & Retrieval', function () {
    beforeEach(function () {
        $this->seed(\Database\Seeders\RoleAndPermissionSeeder::class);
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
        $this->business = Business::factory()->create(['owner_id' => $this->user->id]);
        $this->user->businesses()->attach($this->business->id, ['role' => 'business-owner', 'status' => 'active', 'joined_at' => now()]);
        $this->session(['current_business_id' => $this->business->id]);
        app(\App\Managers\TenantManager::class)->clear();

        KnowledgeArticle::factory()->create([
            'business_id' => $this->business->id,
            'title' => 'Premium Plan Pricing',
            'content' => 'Our premium plan costs $99 per month with unlimited access.',
            'status' => 'published',
        ]);

        KnowledgeFaq::factory()->create([
            'business_id' => $this->business->id,
            'question' => 'How do I return a product?',
            'answer' => 'You can return any product within 30 days of purchase.',
            'is_published' => true,
        ]);

        Product::factory()->create([
            'business_id' => $this->business->id,
            'name' => 'Premium Widget',
            'description' => 'A high-quality premium widget for professionals.',
            'price' => 99.99,
            'status' => 'published',
        ]);
    });

    it('QueryAnalyzerService integrates', function () {
        $service = app(QueryAnalyzerService::class);
        $result = $service->analyze('How much is premium?');
        expect($result['keywords'])->toContain('premium');
    });

    it('IntentClassifierService integrates', function () {
        $service = app(IntentClassifierService::class);
        expect($service->classify('How much does this cost?')->value)->toBe('pricing');
    });

    it('SearchService performs keyword search', function () {
        $service = app(SearchService::class);
        $dto = new SearchQueryDTO(query: 'premium pricing', businessId: $this->business->id);
        $response = $service->search($dto);
        expect($response->totalResults)->toBeGreaterThan(0);
        expect($response->sourcesUsed)->not->toBeEmpty();
    });

    it('ContextBuilderService builds context', function () {
        $builder = app(ContextBuilderService::class);
        $query = new SearchQueryDTO(query: 'pricing', businessId: $this->business->id);
        $results = [
            new SearchResultDTO(sourceType: 'knowledge_article', sourceId: 1, title: 'Pricing', content: 'Content about pricing', relevanceScore: 0.9),
        ];
        $context = $builder->build($query, $results);
        expect($context->query)->toBe('pricing');
        expect(count($context->allRelevantContent()))->toBeGreaterThan(0);
    });

    it('PromptBuilderService assembles prompt from context', function () {
        $builder = app(PromptBuilderService::class);
        $context = new ContextPackageDTO(
            query: 'pricing',
            businessProfile: ['name' => 'TestBiz', 'information' => [['key' => 'Hours', 'value' => '9-5', 'type' => 'hours']]],
            relevantFaqs: [['title' => 'Q1', 'content' => 'A1']],
        );
        $prompt = $builder->build($context, 'How much does it cost?');
        expect($prompt->assemble())->toContain('How much does it cost?');
        expect($prompt->totalTokens)->toBeGreaterThan(0);
        expect($prompt->wasTruncated)->toBeFalse();
    });

    it('Full retrieval pipeline works end-to-end', function () {
        $service = app(RetrievalService::class);
        $query = new SearchQueryDTO(query: 'premium pricing', businessId: $this->business->id);
        $context = $service->retrieveContext($query);
        expect($context->query)->toBe('premium pricing');
        expect(count($context->relevantArticles) + count($context->relevantProducts))->toBeGreaterThan(0);

        $prompt = $service->buildPrompt($query, 'Tell me about premium pricing');
        expect($prompt->assemble())->toContain('Tell me about premium pricing');
    });

    it('Ensures tenant isolation in search', function () {
        $otherBusiness = Business::factory()->create();
        KnowledgeArticle::factory()->create([
            'business_id' => $otherBusiness->id,
            'title' => 'Other Business Secret',
            'content' => 'This should not be found.',
            'status' => 'published',
        ]);

        $service = app(SearchService::class);
        $dto = new SearchQueryDTO(query: 'secret', businessId: $this->business->id);
        $response = $service->search($dto);

        $foundSecrets = array_filter($response->results, fn($r) => str_contains($r->content ?? '', 'should not be found'));
        expect($foundSecrets)->toBeEmpty();
    });
});

// ─── API Tests ──────────────────────────────────────────────────────────

describe('RAG API', function () {
    beforeEach(function () {
        $this->seed(\Database\Seeders\RoleAndPermissionSeeder::class);
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
        $this->business = Business::factory()->create(['owner_id' => $this->user->id]);
        $this->user->businesses()->attach($this->business->id, ['role' => 'business-owner', 'status' => 'active', 'joined_at' => now()]);
        $this->session(['current_business_id' => $this->business->id]);
        app(\App\Managers\TenantManager::class)->clear();
    });

    it('can search via API', function () {
        KnowledgeArticle::factory()->create([
            'business_id' => $this->business->id,
            'title' => 'Test Article',
            'content' => 'This is test content for searching.',
            'status' => 'published',
        ]);

        $response = $this->postJson('/api/v1/rag/search', ['query' => 'test article']);
        $response->assertOk();
        $response->assertJsonPath('data.query', 'test article');
        expect($response->json('data.results'))->not->toBeEmpty();
    });

    it('can analyze query via API', function () {
        $response = $this->postJson('/api/v1/rag/analyze', ['query' => 'How much does it cost?']);
        $response->assertOk();
        expect($response->json('data.intent'))->toBe('pricing');
        expect($response->json('data.parsed_query'))->toHaveKey('keywords');
    });

    it('can get strategies via API', function () {
        $response = $this->getJson('/api/v1/rag/strategies');
        $response->assertOk();
        expect($response->json('data.available_strategies'))->toHaveKey('hybrid');
        expect($response->json('data.available_strategies'))->toHaveKey('keyword');
    });

    it('can get system info via API', function () {
        $response = $this->getJson('/api/v1/rag/system-info');
        $response->assertOk();
        expect($response->json('data.chunking'))->toHaveKey('default_strategy');
        expect($response->json('data.retrieval'))->toHaveKey('default_strategy');
        expect($response->json('data.sources'))->toBeArray();
    });

    it('can preview context via API', function () {
        KnowledgeArticle::factory()->create([
            'business_id' => $this->business->id,
            'title' => 'Pricing Page',
            'content' => 'Our products cost $99.',
            'status' => 'published',
        ]);

        $response = $this->postJson('/api/v1/rag/context', ['query' => 'pricing']);
        $response->assertOk();
        expect($response->json('data.query'))->toBe('pricing');
    });

    it('can get chunk stats via API', function () {
        $response = $this->getJson('/api/v1/rag/chunks/stats');
        $response->assertOk();
        expect($response->json('data'))->toHaveKey('total_chunks');
    });

    it('can preview chunking via API', function () {
        $response = $this->postJson('/api/v1/rag/chunks/preview', [
            'content' => "Paragraph one.\n\nParagraph two.\n\nParagraph three.",
        ]);
        $response->assertOk();
        expect($response->json('data.total_chunks'))->toBeGreaterThanOrEqual(1);
    });

    it('can get retrieval logs via API', function () {
        RetrievalLog::factory()->create(['business_id' => $this->business->id]);
        $response = $this->getJson('/api/v1/rag/logs');
        $response->assertOk();
    });

    it('enforces tenant isolation for chunks', function () {
        $otherBusiness = Business::factory()->create();
        $chunk = KnowledgeChunk::factory()->create(['business_id' => $otherBusiness->id]);

        $response = $this->getJson("/api/v1/rag/chunks/{$chunk->id}");
        $response->assertNotFound();
    });

    it('can preview prompt via API', function () {
        $response = $this->postJson('/api/v1/rag/prompt-preview', [
            'query' => 'pricing',
            'user_message' => 'How much does it cost?',
        ]);
        $response->assertOk();
        expect($response->json('data.sections'))->toHaveKey('total_tokens');
    });

    it('can preview ranking via API', function () {
        $response = $this->postJson('/api/v1/rag/ranking-preview', [
            'query' => 'premium widget',
            'source_type' => 'product',
            'source_id' => 1,
            'title' => 'Premium Widget',
            'content' => 'A high-end widget.',
        ]);
        $response->assertOk();
        expect($response->json('data.ranking_scores'))->toHaveKey('keyword_relevance');
    });
});

// ─── Model Tests ────────────────────────────────────────────────────────

describe('RAG Models', function () {
    it('creates KnowledgeChunk', function () {
        $business = Business::factory()->create();
        $chunk = KnowledgeChunk::factory()->create(['business_id' => $business->id]);
        expect($chunk->uuid)->not->toBeNull();
        expect($chunk->is_active)->toBeTrue();
        expect($chunk->business_id)->toBe($business->id);
    });

    it('creates RetrievalLog', function () {
        $business = Business::factory()->create();
        $log = RetrievalLog::factory()->create(['business_id' => $business->id]);
        expect($log->id)->not->toBeNull();
        expect($log->query)->not->toBeEmpty();
    });

    it('KnowledgeChunk has business relationship', function () {
        $business = Business::factory()->create();
        $chunk = KnowledgeChunk::factory()->create(['business_id' => $business->id]);
        expect($chunk->business->id)->toBe($business->id);
    });

    it('KnowledgeChunk scopes work', function () {
        $business = Business::factory()->create();
        KnowledgeChunk::factory()->create(['business_id' => $business->id, 'source_type' => 'test', 'is_active' => true]);

        $chunks = KnowledgeChunk::byBusiness($business->id)->get();
        expect($chunks->count())->toBe(1);

        $active = KnowledgeChunk::active()->get();
        expect($active->count())->toBeGreaterThanOrEqual(1);
    });
});
