<?php

use Illuminate\Support\Facades\Hash;
use Webpatser\Uuid\Uuid;

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

$factory->define(App\User::class, function (Faker\Generator $faker) {
    $username = rand(0, 500).$faker->email;

    return [
        'username'     => $username,
        'password'     => Hash::make($faker->password),
        'first_name'   => $faker->firstName,
        'last_name'    => $faker->lastName,
        'email'        => $username,
        'mobile'       => $faker->e164PhoneNumber,
        'confirm_code' => Uuid::generate(4),
        'confirmed_at' => null,
        'created_by'   => 1,
        'updated_by'   => 1,
    ];
});
