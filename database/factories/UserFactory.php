<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'password' => $this->faker->password(),
            'phone' => $this->faker->phoneNumber(),
            'dob' => $this->faker->dateTimeBetween('-60 years', '-18 years')->format('Y-m-d'),
            'status' => $this->faker->randomElement(\App\Enums\UserStatusEnum::cases()),
            'lock_pin' => bcrypt($this->faker->randomNumber(6, true)),
        ];
    }

    public function active(): static
    {
        return $this->state(fn() => ['status' => \App\Enums\UserStatusEnum::Active]);
    }

    public function inactive(): static
    {
        return $this->state(fn() => ['status' => \App\Enums\UserStatusEnum::Inactive]);
    }

    public function suspended(): static
    {
        return $this->state(fn() => ['status' => \App\Enums\UserStatusEnum::Suspended]);
    }
}
