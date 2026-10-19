<?php

namespace Database\Factories;

use App\Group;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Group>
 */
class GroupFactory extends Factory
{
    protected $model = Group::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'colorHex' => null,
            'branch_id' => BranchFactory::new(),
        ];
    }
}
