<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\BusinessInformation;
use App\Models\KnowledgeArticle;
use App\Models\KnowledgeFaq;
use App\Models\KnowledgeCategory;
use App\Models\Product;
use App\Models\User;
use App\Services\RAG\DocumentProcessingService;
use Illuminate\Database\Seeder;

class RagContentSeeder extends Seeder
{
    public function run(DocumentProcessingService $processor): void
    {
        $business = Business::first();
        if (!$business) {
            $this->command->warn('No business found. Run DemoBusinessSeeder first.');
            return;
        }

        $agent = User::whereHas('businesses', fn($q) => $q->where('business_id', $business->id))->first();

        $existingArticles = KnowledgeArticle::where('business_id', $business->id)->count();
        $existingFaqs = KnowledgeFaq::where('business_id', $business->id)->count();
        $existingProducts = Product::where('business_id', $business->id)->count();
        $existingInfo = BusinessInformation::where('business_id', $business->id)->count();

        if ($existingArticles === 0) {
            $faqCategory = KnowledgeCategory::firstOrCreate(
                ['business_id' => $business->id, 'slug' => 'faqs'],
                ['name' => 'FAQs', 'type' => 'knowledge_base']
            );
            $shippingCategory = KnowledgeCategory::firstOrCreate(
                ['business_id' => $business->id, 'slug' => 'shipping'],
                ['name' => 'Shipping', 'type' => 'knowledge_base']
            );

            KnowledgeArticle::factory(6)->create([
                'business_id' => $business->id,
                'category_id' => $faqCategory->id,
                'author_id' => $agent?->id,
            ]);

            KnowledgeArticle::factory(4)->create([
                'business_id' => $business->id,
                'category_id' => $shippingCategory->id,
                'author_id' => $agent?->id,
            ]);
        }

        if ($existingFaqs === 0) {
            $faqs = [
                ['question' => 'What are your business hours?', 'answer' => 'We are open Monday through Friday from 9:00 AM to 6:00 PM, and Saturdays from 10:00 AM to 4:00 PM. We are closed on Sundays and major holidays.'],
                ['question' => 'What payment methods do you accept?', 'answer' => 'We accept Visa, Mastercard, American Express, Discover, PayPal, and Apple Pay. All payments are processed securely through encrypted channels.'],
                ['question' => 'How do I reset my password?', 'answer' => 'To reset your password, click on "Forgot Password" on the login page. Enter your registered email address and we will send you a password reset link. Follow the instructions in the email to create a new password.'],
                ['question' => 'Do you offer refunds?', 'answer' => 'Yes, we offer a 30-day money-back guarantee on all products. If you are not satisfied with your purchase, contact our support team within 30 days of purchase to initiate a return and receive a full refund.'],
                ['question' => 'How can I contact customer support?', 'answer' => 'You can reach our customer support team via live chat on our website, by email at support@example.com, or by phone at (555) 123-4567 during business hours. We typically respond to all inquiries within 2 hours.'],
            ];

            foreach ($faqs as $faq) {
                KnowledgeFaq::create(array_merge($faq, [
                    'business_id' => $business->id,
                    'is_published' => true,
                    'sort_order' => 0,
                ]));
            }
        }

        if ($existingProducts === 0) {
            $products = Product::where('business_id', $business->id)->count();
            if ($products === 0) {
                $prodCategory = KnowledgeCategory::firstOrCreate(
                    ['business_id' => $business->id, 'slug' => 'products'],
                    ['name' => 'Products', 'type' => 'product']
                );

                $productData = [
                    ['name' => 'Premium Widget', 'description' => 'Our flagship premium widget made from high-quality materials. Features include durable construction, ergonomic design, and a 5-year warranty. Perfect for both personal and professional use.'],
                    ['name' => 'Basic Gadget', 'description' => 'An affordable entry-level gadget that delivers reliable performance. Ideal for beginners and casual users who need a simple, effective solution.'],
                    ['name' => 'Pro Toolkit', 'description' => 'A comprehensive toolkit for professionals. Includes 12 essential tools in a rugged carrying case. Each tool is crafted from stainless steel with non-slip handles.'],
                ];

                foreach ($productData as $pd) {
                    Product::create([
                        'business_id' => $business->id,
                        'category_id' => $prodCategory->id,
                        'name' => $pd['name'],
                        'description' => $pd['description'],
                        'price' => rand(1000, 99900) / 100,
                        'is_active' => true,
                    ]);
                }
            }
        }

        if ($existingInfo === 0) {
            $infoItems = [
                ['key' => 'return_policy', 'value' => 'Items can be returned within 30 days of purchase in original condition. Refunds are processed within 5-7 business days after we receive the returned item.'],
                ['key' => 'shipping_policy', 'value' => 'Free standard shipping on orders over $50. Express shipping available for an additional fee. Orders are typically processed within 1-2 business days.'],
                ['key' => 'warranty_info', 'value' => 'All products come with a minimum 1-year manufacturer warranty. Extended warranty plans are available for purchase at checkout.'],
                ['key' => 'store_location', 'value' => 'Our physical store is located at 123 Main Street, Suite 100, Anytown, USA 12345. Free parking is available in the adjacent parking garage.'],
            ];

            foreach ($infoItems as $item) {
                BusinessInformation::create(array_merge($item, [
                    'business_id' => $business->id,
                    'type' => 'text',
                    'is_public' => true,
                    'sort_order' => 0,
                ]));
            }
        }

        $processable = [];

        KnowledgeArticle::where('business_id', $business->id)->chunk(50, function ($articles) use (&$processable) {
            $processable = array_merge($processable, $articles->all());
        });

        KnowledgeFaq::where('business_id', $business->id)
            ->where('is_published', true)
            ->chunk(50, function ($faqs) use (&$processable) {
                $processable = array_merge($processable, $faqs->all());
            });

        Product::where('business_id', $business->id)
            ->where('is_active', true)
            ->chunk(50, function ($products) use (&$processable) {
                $processable = array_merge($processable, $products->all());
            });

        BusinessInformation::where('business_id', $business->id)
            ->where('is_public', true)
            ->chunk(50, function ($info) use (&$processable) {
                $processable = array_merge($processable, $info->all());
            });

        $processed = 0;
        foreach ($processable as $item) {
            try {
                $processor->process($item);
                $processed++;
            } catch (\Throwable $e) {
                $this->command->warn("Failed to process " . get_class($item) . " #{$item->id}: {$e->getMessage()}");
            }
        }

        $totalChunks = \App\Models\RAG\KnowledgeChunk::where('business_id', $business->id)->count();
        $this->command->info("Processed {$processed} items into {$totalChunks} knowledge chunks.");
    }
}
