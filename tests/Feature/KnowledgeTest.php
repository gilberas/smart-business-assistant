<?php

use App\Enums\ArticleStatus;
use App\Enums\ArticleType;
use App\Enums\ArticleVisibility;
use App\Models\Business;
use App\Models\KnowledgeArticle;
use App\Models\KnowledgeCategory;
use App\Models\KnowledgeFaq;
use App\Models\KnowledgeTag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RoleAndPermissionSeeder::class);

    $this->user = User::factory()->create();
    $this->actingAs($this->user);

    $this->business = Business::factory()->create(['owner_id' => $this->user->id]);
    $this->user->businesses()->attach($this->business->id, [
        'role' => 'business-owner',
        'status' => 'active',
        'joined_at' => now(),
    ]);

    session(['current_business_id' => $this->business->id]);
});

// --- Article Tests ---

it('can create an article', function () {
    $category = KnowledgeCategory::factory()->create(['business_id' => $this->business->id]);

    $response = $this->postJson('/api/v1/knowledge/articles', [
        'title' => 'Getting Started Guide',
        'content' => 'This is the content of the guide.',
        'article_type' => ArticleType::Guide->value,
        'status' => ArticleStatus::Draft->value,
        'category_id' => $category->id,
    ]);

    $response->assertStatus(201);
    expect($response->json('data.title'))->toBe('Getting Started Guide');
    expect(KnowledgeArticle::where('business_id', $this->business->id)->count())->toBe(1);
});

it('requires title to create article', function () {
    $response = $this->postJson('/api/v1/knowledge/articles', [
        'content' => 'Some content',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['title']);
});

it('can list articles', function () {
    KnowledgeArticle::factory()->count(3)->create(['business_id' => $this->business->id]);

    $response = $this->getJson('/api/v1/knowledge/articles');

    $response->assertStatus(200);
    expect($response->json('data'))->toHaveLength(3);
});

it('can show an article', function () {
    $article = KnowledgeArticle::factory()->create(['business_id' => $this->business->id]);

    $response = $this->getJson("/api/v1/knowledge/articles/{$article->uuid}");

    $response->assertStatus(200);
    expect($response->json('data.uuid'))->toBe($article->uuid);
});

it('can update an article', function () {
    $article = KnowledgeArticle::factory()->create(['business_id' => $this->business->id]);

    $response = $this->putJson("/api/v1/knowledge/articles/{$article->uuid}", [
        'title' => 'Updated Title',
    ]);

    $response->assertStatus(200);
    expect($article->fresh()->title)->toBe('Updated Title');
});

it('can delete an article', function () {
    $article = KnowledgeArticle::factory()->create(['business_id' => $this->business->id]);

    $response = $this->deleteJson("/api/v1/knowledge/articles/{$article->uuid}");

    $response->assertStatus(200);
    expect(KnowledgeArticle::where('business_id', $this->business->id)->count())->toBe(0);
});

it('can publish an article', function () {
    $article = KnowledgeArticle::factory()->draft()->create(['business_id' => $this->business->id]);

    $response = $this->postJson("/api/v1/knowledge/articles/{$article->uuid}/publish");

    $response->assertStatus(200);
    expect($article->fresh()->status->value)->toBe('published');
    expect($article->fresh()->published_at)->not->toBeNull();
});

it('can archive an article', function () {
    $article = KnowledgeArticle::factory()->published()->create(['business_id' => $this->business->id]);

    $response = $this->postJson("/api/v1/knowledge/articles/{$article->uuid}/archive");

    $response->assertStatus(200);
    expect($article->fresh()->status->value)->toBe('archived');
});

it('can restore an article', function () {
    $article = KnowledgeArticle::factory()->create([
        'business_id' => $this->business->id,
        'status' => ArticleStatus::Archived,
        'deleted_at' => now(),
    ]);

    $response = $this->postJson("/api/v1/knowledge/articles/{$article->uuid}/restore");

    $response->assertStatus(200);
    expect($article->fresh()->status->value)->toBe('draft');
});

it('can search articles', function () {
    KnowledgeArticle::factory()->create(['business_id' => $this->business->id, 'title' => 'How to install']);
    KnowledgeArticle::factory()->create(['business_id' => $this->business->id, 'title' => 'Pricing plans']);

    $response = $this->getJson('/api/v1/knowledge/articles/search?q=install');

    $response->assertStatus(200);
    expect($response->json('data'))->toHaveLength(1);
});

it('can get article stats', function () {
    KnowledgeArticle::factory()->draft()->create(['business_id' => $this->business->id]);
    KnowledgeArticle::factory()->published()->count(3)->create(['business_id' => $this->business->id]);
    KnowledgeArticle::factory()->create([
        'business_id' => $this->business->id,
        'status' => ArticleStatus::Archived,
    ]);

    $response = $this->getJson('/api/v1/knowledge/articles/stats');

    $response->assertStatus(200);
    expect($response->json('total'))->toBe(5);
    expect($response->json('published'))->toBe(3);
    expect($response->json('draft'))->toBe(1);
    expect($response->json('archived'))->toBe(1);
});

it('can track article view', function () {
    $article = KnowledgeArticle::factory()->create(['business_id' => $this->business->id, 'views_count' => 5]);

    $response = $this->postJson("/api/v1/knowledge/articles/{$article->uuid}/view");

    $response->assertStatus(200);
    expect($article->fresh()->views_count)->toBe(6);
});

it('can mark article as helpful', function () {
    $article = KnowledgeArticle::factory()->create(['business_id' => $this->business->id, 'helpful_count' => 10]);

    $response = $this->postJson("/api/v1/knowledge/articles/{$article->uuid}/helpful");

    $response->assertStatus(200);
    expect($article->fresh()->helpful_count)->toBe(11);
});

it('can mark article as not helpful', function () {
    $article = KnowledgeArticle::factory()->create(['business_id' => $this->business->id, 'not_helpful_count' => 3]);

    $response = $this->postJson("/api/v1/knowledge/articles/{$article->uuid}/not-helpful");

    $response->assertStatus(200);
    expect($article->fresh()->not_helpful_count)->toBe(4);
});

// --- Category Tests ---

it('can create a category', function () {
    $response = $this->postJson('/api/v1/knowledge/categories', [
        'name' => 'Getting Started',
        'description' => 'Beginner guides',
    ]);

    $response->assertStatus(201);
    expect($response->json('data.name'))->toBe('Getting Started');
});

it('can list categories', function () {
    KnowledgeCategory::factory()->count(3)->create(['business_id' => $this->business->id]);

    $response = $this->getJson('/api/v1/knowledge/categories');

    $response->assertStatus(200);
    expect($response->json('data'))->toHaveLength(3);
});

it('can get category tree', function () {
    $parent = KnowledgeCategory::factory()->create(['business_id' => $this->business->id, 'name' => 'Parent']);
    KnowledgeCategory::factory()->create(['business_id' => $this->business->id, 'name' => 'Child', 'parent_id' => $parent->id]);

    $response = $this->getJson('/api/v1/knowledge/categories/tree');

    $response->assertStatus(200);
    expect($response->json('data'))->toHaveLength(1);
    expect($response->json('data.0.children'))->toHaveLength(1);
});

it('can update a category', function () {
    $category = KnowledgeCategory::factory()->create(['business_id' => $this->business->id]);

    $response = $this->putJson("/api/v1/knowledge/categories/{$category->id}", [
        'name' => 'Updated Category',
    ]);

    $response->assertStatus(200);
    expect($category->fresh()->name)->toBe('Updated Category');
});

it('can delete a category', function () {
    $category = KnowledgeCategory::factory()->create(['business_id' => $this->business->id]);

    $response = $this->deleteJson("/api/v1/knowledge/categories/{$category->id}");

    $response->assertStatus(200);
    expect(KnowledgeCategory::where('business_id', $this->business->id)->count())->toBe(0);
});

// --- Tag Tests ---

it('can create a knowledge tag', function () {
    $response = $this->postJson('/api/v1/knowledge/tags', [
        'name' => 'Tutorial',
        'color' => '#ff0000',
    ]);

    $response->assertStatus(201);
    expect($response->json('data.name'))->toBe('Tutorial');
});

it('can list knowledge tags', function () {
    KnowledgeTag::factory()->count(3)->create(['business_id' => $this->business->id]);

    $response = $this->getJson('/api/v1/knowledge/tags');

    $response->assertStatus(200);
    expect($response->json('data'))->toHaveLength(3);
});

it('can delete a knowledge tag', function () {
    $tag = KnowledgeTag::factory()->create(['business_id' => $this->business->id]);

    $response = $this->deleteJson("/api/v1/knowledge/tags/{$tag->id}");

    $response->assertStatus(200);
    expect(KnowledgeTag::where('business_id', $this->business->id)->count())->toBe(0);
});

// --- FAQ Tests ---

it('can create a faq', function () {
    $response = $this->postJson('/api/v1/knowledge/faqs', [
        'question' => 'What is this?',
        'answer' => 'This is a knowledge base.',
        'sort_order' => 1,
    ]);

    $response->assertStatus(201);
    expect($response->json('data.question'))->toBe('What is this?');
});

it('can list faqs', function () {
    KnowledgeFaq::factory()->count(3)->create(['business_id' => $this->business->id]);

    $response = $this->getJson('/api/v1/knowledge/faqs');

    $response->assertStatus(200);
    expect($response->json('data'))->toHaveLength(3);
});

it('can update a faq', function () {
    $faq = KnowledgeFaq::factory()->create(['business_id' => $this->business->id]);

    $response = $this->putJson("/api/v1/knowledge/faqs/{$faq->id}", [
        'question' => 'Updated question?',
    ]);

    $response->assertStatus(200);
    expect($faq->fresh()->question)->toBe('Updated question?');
});

it('can delete a faq', function () {
    $faq = KnowledgeFaq::factory()->create(['business_id' => $this->business->id]);

    $response = $this->deleteJson("/api/v1/knowledge/faqs/{$faq->id}");

    $response->assertStatus(200);
    expect(KnowledgeFaq::where('business_id', $this->business->id)->count())->toBe(0);
});

// --- Collection Tests ---

it('can create a collection', function () {
    $response = $this->postJson('/api/v1/knowledge/collections', [
        'name' => 'Onboarding',
        'description' => 'All onboarding articles',
    ]);

    $response->assertStatus(201);
    expect($response->json('data.name'))->toBe('Onboarding');
});

it('can list collections', function () {
    \App\Models\KnowledgeCollection::factory()->count(3)->create(['business_id' => $this->business->id]);

    $response = $this->getJson('/api/v1/knowledge/collections');

    $response->assertStatus(200);
    expect($response->json('data'))->toHaveLength(3);
});

it('can attach articles to collection', function () {
    $collection = \App\Models\KnowledgeCollection::factory()->create(['business_id' => $this->business->id]);
    $article = KnowledgeArticle::factory()->create(['business_id' => $this->business->id]);

    $response = $this->postJson("/api/v1/knowledge/collections/{$collection->id}/articles", [
        'article_ids' => [$article->id],
    ]);

    $response->assertStatus(200);
    expect($collection->fresh()->articles->count())->toBe(1);
});

it('can delete a collection', function () {
    $collection = \App\Models\KnowledgeCollection::factory()->create(['business_id' => $this->business->id]);

    $response = $this->deleteJson("/api/v1/knowledge/collections/{$collection->id}");

    $response->assertStatus(200);
    expect(\App\Models\KnowledgeCollection::where('business_id', $this->business->id)->count())->toBe(0);
});

// --- Tenant Isolation Tests ---

it('enforces article tenant isolation', function () {
    $otherBusiness = Business::factory()->create();
    $article = KnowledgeArticle::factory()->create(['business_id' => $otherBusiness->id]);

    $response = $this->getJson("/api/v1/knowledge/articles/{$article->uuid}");

    $response->assertStatus(403);
});

it('enforces article list tenant isolation', function () {
    KnowledgeArticle::factory()->count(3)->create(['business_id' => $this->business->id]);
    KnowledgeArticle::factory()->count(2)->create(['business_id' => Business::factory()->create()->id]);

    $response = $this->getJson('/api/v1/knowledge/articles');

    $response->assertStatus(200);
    expect($response->json('data'))->toHaveLength(3);
});

it('enforces category tenant isolation', function () {
    $otherBusiness = Business::factory()->create();
    $category = KnowledgeCategory::factory()->create(['business_id' => $otherBusiness->id]);

    $response = $this->getJson("/api/v1/knowledge/categories/{$category->id}");

    $response->assertStatus(403);
});

it('enforces tag tenant isolation', function () {
    $otherBusiness = Business::factory()->create();
    $tag = KnowledgeTag::factory()->create(['business_id' => $otherBusiness->id]);

    $response = $this->putJson("/api/v1/knowledge/tags/{$tag->id}", ['name' => 'Hacked']);

    $response->assertStatus(403);
});

it('enforces faq tenant isolation', function () {
    $otherBusiness = Business::factory()->create();
    $faq = KnowledgeFaq::factory()->create(['business_id' => $otherBusiness->id]);

    $response = $this->getJson("/api/v1/knowledge/faqs/{$faq->id}");

    $response->assertStatus(403);
});

it('enforces collection tenant isolation', function () {
    $otherBusiness = Business::factory()->create();
    $collection = \App\Models\KnowledgeCollection::factory()->create(['business_id' => $otherBusiness->id]);

    $response = $this->deleteJson("/api/v1/knowledge/collections/{$collection->id}");

    $response->assertStatus(403);
});

// --- UUID Assignment Tests ---

it('assigns uuid on article creation', function () {
    $article = KnowledgeArticle::factory()->create(['business_id' => $this->business->id]);

    expect($article->uuid)->not->toBeNull();
    expect(strlen($article->uuid))->toBe(36);
});

// --- Permission Tests ---

it('respects viewer permission on knowledge', function () {
    $viewer = User::factory()->create();
    $viewer->businesses()->attach($this->business->id, [
        'role' => 'viewer',
        'status' => 'active',
        'joined_at' => now(),
    ]);
    $this->actingAs($viewer);

    $response = $this->getJson('/api/v1/knowledge/articles');
    $response->assertStatus(200);
});

it('viewer cannot create knowledge articles', function () {
    $viewer = User::factory()->create();
    $viewer->businesses()->attach($this->business->id, [
        'role' => 'viewer',
        'status' => 'active',
        'joined_at' => now(),
    ]);
    $this->actingAs($viewer);

    $response = $this->postJson('/api/v1/knowledge/articles', [
        'title' => 'Test',
        'content' => 'Content',
    ]);

    $response->assertStatus(403);
});
