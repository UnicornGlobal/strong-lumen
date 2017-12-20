<?php

use App\Role;
use App\UserRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Webpatser\Uuid\Uuid;

class Roles extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();

        if (app()->environment('local')) {
            DB::table('roles')->delete();
        }

        Role::create([
            'id' => 1,
            '_id' => Uuid::generate(4),
            'name' => 'system',
            'is_active' => true,
            'created_by' => 1,
            'updated_by' => 1,
        ]);

        Role::create([
            'id' => 2,
            '_id' => Uuid::generate(4),
            'name' => 'admin',
            'is_active' => true,
            'created_by' => 1,
            'updated_by' => 1,
        ]);

        Role::create([
            'id' => 3,
            '_id' => Uuid::generate(4),
            'name' => 'user',
            'is_active' => true,
            'created_by' => 1,
            'updated_by' => 1,
        ]);

        Role::create([
            'id' => 4,
            '_id' => Uuid::generate(4),
            'name' => 'developer',
            'is_active' => true,
            'created_by' => 1,
            'updated_by' => 1,
        ]);

        if (app()->environment('local')) {
            DB::table('user_role')->delete();
        }

        /**
         * Create relations between roles and users
         */

        // Assign user 3 as admin
        UserRole::create([
            'user_id' => 3,
            'role_id' => 2,
            'created_by' => 1,
            'updated_by' => 1,
        ]);

        // Assign system user to system role
        UserRole::create([
            'user_id' => 1,
            'role_id' => 1,
            'created_by' => 1,
            'updated_by' => 1,
        ]);
    }
}
