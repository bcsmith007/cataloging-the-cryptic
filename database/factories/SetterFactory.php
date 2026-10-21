<?php

namespace Database\Factories;

use App\Models\Setter;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Setter>
 */
class SetterFactory extends Factory
{
    protected $model = Setter::class;

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
            'bio' => fake()->paragraph(),
            'patreon_url' => 'https://patreon.com/'.Str::slug($name),
        ];
    }
}
