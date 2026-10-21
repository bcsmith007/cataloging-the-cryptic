<?php

namespace Database\Factories;

use App\Models\Constraint;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Constraint>
 */
class ConstraintFactory extends Factory
{
    protected $model = Constraint::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name' => ucfirst($name),
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(100, 9999),
            'description' => fake()->sentence(),
        ];
    }
}
