<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);
        $price = fake()->randomFloat(2, 50, 5000);

        return [
            'category_id' => Category::factory(),
            'name' => ucfirst($name),
            'slug' => str($name)->slug()->value(),
            'description' => fake()->paragraph(),
            'price' => $price,
            'compare_at_price' => round($price * 1.15, 2),
            'cashback_percent' => fake()->randomElement([1.5, 2.0, 3.0, 5.0]),
            'stock' => fake()->numberBetween(5, 50),
            'image_path' => null,
            'is_active' => true,
        ];
    }
}
