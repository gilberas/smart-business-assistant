<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Business;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'uuid' => (string) Str::uuid(),
            'name' => fake()->name(),
            'phone' => fake()->unique()->e164PhoneNumber(),
            'email' => fake()->safeEmail(),
            'status' => 'active',
            'source' => fake()->randomElement(['manual', 'import', 'whatsapp', 'web_form']),
            'customer_type' => 'individual',
            'lifecycle_stage' => fake()->randomElement(['lead', 'prospect', 'active', 'vip']),
            'company_name' => fake()->optional(0.3)->company(),
            'job_title' => fake()->optional(0.4)->jobTitle(),
            'timezone' => fake()->timezone(),
            'preferred_language' => 'en',
            'last_interacted_at' => fake()->dateTimeBetween('-30 days'),
        ];
    }

    public function active(): static
    {
        return $this->state(['status' => 'active']);
    }

    public function archived(): static
    {
        return $this->state(['status' => 'archived']);
    }

    public function vip(): static
    {
        return $this->state(['lifecycle_stage' => 'vip']);
    }

    public function lead(): static
    {
        return $this->state(['lifecycle_stage' => 'lead']);
    }
}
