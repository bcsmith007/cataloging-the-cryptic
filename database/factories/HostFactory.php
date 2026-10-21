<?php

namespace Database\Factories;

use App\Models\Host;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Host>
 */
class HostFactory extends Factory
{
    protected $model = Host::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->name();

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(100, 9999),
            'avatar_url' => fake()->imageUrl(),
        ];
    }
}
