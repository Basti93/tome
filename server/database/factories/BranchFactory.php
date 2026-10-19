<?php

namespace Database\Factories;

use App\Branch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Branch>
 */
class BranchFactory extends Factory
{
    protected $model = Branch::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'short_name' => strtoupper(fake()->lexify('???')),
            'colorHex' => '#336699',
        ];
    }
}
