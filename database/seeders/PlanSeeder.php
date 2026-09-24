<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            ['name' => 'free',     'min_slug_length' => 6, 'custom_subdomain' => false, 'slug_length_choice' => false, 'rate_limit_per_minute' => 10,  'max_links_per_day' => 50],
            ['name' => 'pro',      'min_slug_length' => 5, 'custom_subdomain' => false, 'slug_length_choice' => false, 'rate_limit_per_minute' => 60,  'max_links_per_day' => 500],
            ['name' => 'business', 'min_slug_length' => 3, 'custom_subdomain' => true,  'slug_length_choice' => true,  'rate_limit_per_minute' => 120, 'max_links_per_day' => null],
            ['name' => 'partner',  'min_slug_length' => 3, 'custom_subdomain' => true,  'slug_length_choice' => true,  'rate_limit_per_minute' => 120, 'max_links_per_day' => null],
            ['name' => 'family',   'min_slug_length' => 1, 'custom_subdomain' => true,  'slug_length_choice' => true,  'rate_limit_per_minute' => 300, 'max_links_per_day' => null],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(['name' => $plan['name']], $plan);
        }
    }
}
