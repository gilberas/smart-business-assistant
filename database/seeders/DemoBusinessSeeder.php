<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\BusinessSetting;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\KnowledgeBaseArticle;
use App\Models\Message;
use App\Models\Product;
use App\Models\User;
use App\Support\RoleRegistry;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class DemoBusinessSeeder extends Seeder
{
    public function run(): void
    {
        $business = Business::factory()->create([
            'name' => 'Demo Business',
            'slug' => 'demo-business',
        ]);

        BusinessSetting::factory()->create([
            'business_id' => $business->id,
        ]);

        $users = User::factory(3)->create();
        $adminRole = Role::findByName(RoleRegistry::BUSINESS_ADMIN);
        $agentRole = Role::findByName(RoleRegistry::AGENT);

        $business->users()->attach($users[0]->id, [
            'role' => 'admin',
            'status' => 'active',
            'joined_at' => now(),
        ]);
        $users[0]->assignRole($adminRole);

        foreach ($users->slice(1) as $user) {
            $business->users()->attach($user->id, [
                'role' => 'agent',
                'status' => 'active',
                'joined_at' => now(),
            ]);
            $user->assignRole($agentRole);
        }

        $categories = $business->categories()->createMany([
            ['name' => 'Electronics', 'slug' => 'electronics', 'type' => 'product'],
            ['name' => 'Clothing', 'slug' => 'clothing', 'type' => 'product'],
            ['name' => 'FAQs', 'slug' => 'faqs', 'type' => 'knowledge_base'],
            ['name' => 'Shipping', 'slug' => 'shipping', 'type' => 'knowledge_base'],
        ]);

        Product::factory(10)->create([
            'business_id' => $business->id,
            'category_id' => $categories[0]->id,
        ]);

        Product::factory(8)->create([
            'business_id' => $business->id,
            'category_id' => $categories[1]->id,
        ]);

        KnowledgeBaseArticle::factory(6)->create([
            'business_id' => $business->id,
            'category_id' => $categories[2]->id,
        ]);

        KnowledgeBaseArticle::factory(4)->create([
            'business_id' => $business->id,
            'category_id' => $categories[3]->id,
        ]);

        $customers = Customer::factory(15)->create([
            'business_id' => $business->id,
        ]);

        foreach ($customers->take(5) as $customer) {
            $conversation = Conversation::factory()->create([
                'business_id' => $business->id,
                'customer_id' => $customer->id,
                'assigned_to' => $users->random()->id,
            ]);

            Message::factory(3)->create([
                'conversation_id' => $conversation->id,
                'business_id' => $business->id,
                'sender_type' => 'customer',
                'sender_id' => null,
            ]);

            Message::factory(2)->create([
                'conversation_id' => $conversation->id,
                'business_id' => $business->id,
                'sender_type' => 'ai',
                'sender_id' => null,
            ]);
        }
    }
}
