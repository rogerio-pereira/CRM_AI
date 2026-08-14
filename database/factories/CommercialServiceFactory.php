<?php

namespace Database\Factories;

use App\Enums\CommercialServiceCategory;
use App\Models\CommercialService;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CommercialService>
 */
class CommercialServiceFactory extends Factory
{
    protected $model = CommercialService::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $categories = CommercialServiceCategory::cases();
        $category = fake()->randomElement($categories);
        $uniqueFaker = fake()->unique();
        $name = $uniqueFaker->words(3, true);
        $description = fake()->paragraph();
        $price = fake()->randomFloat(2, 50, 10000);

        return [
            'category_slug' => $category,
            'name' => $name,
            'description' => $description,
            'default_unit_price' => $price,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state([
            'is_active' => false,
        ]);
    }
}
