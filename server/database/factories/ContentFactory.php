<?php

namespace Database\Factories;

use App\Content;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Content>
 */
class ContentFactory extends Factory
{
    protected $model = Content::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'branch_id' => BranchFactory::new(),
            'order' => null,
            'image_name' => null,
        ];
    }
}
