<?php

use App\User;
use Faker\Generator;
use Illuminate\Database\Eloquent\Factory;

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| Here you may define all of your model factories. Model factories give
| you a convenient way to create models for testing and seeding your
| database. Just tell the factory how a default model should look.
|
*/

/** @var Factory $factory */
$factory->define(User::class, function (Generator $faker) {
    static $password;

    return [
        'firstName' => $faker->name,
        'familyName' => $faker->name,
        'email' => $faker->unique()->safeEmail,
        'password' => 'bla',
        'remember_token' => str_random(10),
    ];
});
