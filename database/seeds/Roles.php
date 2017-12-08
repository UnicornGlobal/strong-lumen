<?php

use App\UserRole;
use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Webpatser\Uuid\Uuid;
use App\Role;

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

        DB::table('roles')->delete();

        Role::create([
            'id' => 1,
            '_id' => Uuid::generate(4),
            'name' => 'admin',
            'created_by' => 1,
            'updated_by' => 1,
        ]);

        DB::table('user_roles')->delete();
        UserRole::create([
            'user_id' => 1,
            'role_id' => 1,
        ]);
    }
}
