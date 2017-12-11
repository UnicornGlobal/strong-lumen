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
            'name' => 'admin',
            'created_by' => 1,
            'updated_by' => 1,
        ]);

        if (app()->environment('local')) {
            DB::table('user_role')->delete();
        }
        UserRole::create([
            'user_id' => 1,
            'role_id' => 1,
        ]);
    }
}