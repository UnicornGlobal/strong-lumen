<?php

use App\Role;
use App\User;
use App\UserRole;
use Illuminate\Support\Facades\Hash;
use Laravel\Lumen\Application;

abstract class TestCase extends Laravel\Lumen\Testing\TestCase
{
    use InteractsWithExceptionHandling;

    public function setUp(): void
    {
        parent::setUp();

        // Roles
        $this->userRole = Role::getByName('user');
        $this->userRoleId = $this->userRole->_id;

        $this->adminRole = Role::getByName('admin');
        $this->adminRoleId = $this->adminRole->_id;

        $this->systemRole = Role::getByName('system');
        $this->systemRoleId = $this->systemRole->_id;

        // Normal User
        $this->user = factory(User::class)->create([
            'password' => Hash::make('password'),
        ]);
        $this->userId = $this->user->_id;

        UserRole::create([
            'created_by' => 1,
            'updated_by' => 1,
            'user_id'    => $this->user->id,
            'role_id'    => $this->userRole->id,
        ]);

        // Second Normal User
        $this->secondUser = factory(User::class)->create([
            'password' => Hash::make('password'),
        ]);
        $this->secondUserId = $this->secondUser->_id;

        UserRole::create([
            'created_by' => 1,
            'updated_by' => 1,
            'user_id'    => $this->secondUser->id,
            'role_id'    => $this->userRole->id,
        ]);

        // Admin User
        $this->adminUser = factory(User::class)->create([
            'password' => Hash::make('password'),
        ]);
        $this->adminUserId = $this->adminUser->_id;

        UserRole::create([
            'created_by' => 1,
            'updated_by' => 1,
            'user_id'    => $this->adminUser->id,
            'role_id'    => $this->userRole->id,
        ]);

        UserRole::create([
            'created_by' => 1,
            'updated_by' => 1,
            'user_id'    => $this->adminUser->id,
            'role_id'    => $this->adminRole->id,
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
