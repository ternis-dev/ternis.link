<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sso_sub' => (string) Str::uuid(),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'avatar_url' => null,
            'sso_user_type' => 'general',
            'role' => UserRole::User,
            'plan_id' => Plan::first()?->id,
        ];
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::Admin,
            'sso_user_type' => 'ternis_member',
        ]);
    }

    public function partner(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::Partner,
            'sso_user_type' => 'partner',
        ]);
    }

    public function family(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::Family,
            'sso_user_type' => 'ternis_member',
        ]);
    }
}
