<?php

namespace Database\Factories;

use App\Branch;
use App\Group;
use App\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    public const PASSWORD = 'Password1!';

    protected $model = User::class;

    public function definition(): array
    {
        return [
            'firstName' => fake()->firstName(),
            'familyName' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'password' => self::PASSWORD,
            'active' => 1,
            'registered' => 1,
            'email_verified_at' => now(),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn () => ['email_verified_at' => null, 'email_verification_token' => 'token-'.fake()->unique()->numerify('########')]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['active' => 0]);
    }

    /** A user without e-mail/password, as created by "createUnregistered". */
    public function unregistered(): static
    {
        return $this->state(fn () => ['registered' => 0, 'email' => null, 'password' => null, 'email_verified_at' => null]);
    }

    /** Assign the spatie role (roles must have been seeded). */
    public function role(string $role): static
    {
        return $this->afterCreating(fn (User $user) => $user->assignRole($role));
    }

    /** @param  iterable<Group|int>  $groups */
    public function inGroups(iterable $groups): static
    {
        return $this->afterCreating(function (User $user) use ($groups) {
            $user->groups()->attach(collect($groups)->map(fn ($g) => is_object($g) ? $g->id : $g)->all());
        });
    }

    /** @param  iterable<Branch|int>  $branches */
    public function trainerOfBranches(iterable $branches): static
    {
        return $this->afterCreating(function (User $user) use ($branches) {
            $user->trainerBranches()->attach(collect($branches)->map(fn ($b) => is_object($b) ? $b->id : $b)->all());
        });
    }
}
