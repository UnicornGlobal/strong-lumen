<?php

use App\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Lumen\Application;

abstract class TestCase extends Laravel\Lumen\Testing\TestCase
{
    use InteractsWithExceptionHandling;

    public function setUp(): void
    {
        parent::setUp();

        $this->user = factory(User::class)->create([
            'password' => Hash::make('password'),
        ]);
    }

    /**
     * Creates the application.
     *
     * @return \Laravel\Lumen\Application
     */
    public function createApplication()
    {
        return require __DIR__.'/../bootstrap/app.php';
    }
}
