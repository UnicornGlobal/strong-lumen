<?php

use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Webpatser\Uuid\Uuid;

class Users extends Seeder
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
            DB::table('users')->delete();
        }

        if (app()->environment('staging')) {
            DB::table('users')->delete();
        }

        /*
         * The system user.
         *
         * Any actions performed by the system should use this ID for the
         * users under `created_by`, `updated_by` etc
         */
        User::create([
            'id'      => 1,
            '_id'     => env('SYSTEM_USER_ID'),
            'api_key' => null,
            // Nobody must ever be able to log into this account
            'username'     => Uuid::generate(4),
            'password'     => Hash::make(Uuid::generate(4)),
            'first_name'   => 'System',
            'last_name'    => 'User',
            'email'        => env('SYSTEM_USER_EMAIL'),
            'confirm_code' => '1234',
            'created_by'   => 1,
            'updated_by'   => 1,
        ]);

        /*
         * It's important to only seed dev with this
         *
         * Also used for unit tests
         */
        if (app()->environment('local')) {
            User::create([
                'id'           => 2,
                '_id'          => env('TEST_USER_ID'),
                'api_key'      => '653FDC8C-0FB7-4C72-98F2-2A3A565C7467',
                'username'     => 'user',
                'password'     => Hash::make('password'),
                'first_name'   => 'Test',
                'last_name'    => 'User',
                'email'        => 'developer@example.com',
                'confirm_code' => '0000',
                'created_by'   => 1,
                'updated_by'   => 1,
            ]);

            User::create([
                'id'           => 3,
                '_id'          => env('ADMIN_USER_ID'),
                'api_key'      => 'C87A9108-1568-4CBB-88E1-B90B5A451C67',
                'username'     => 'admin',
                'password'     => Hash::make('admin'),
                'first_name'   => 'Admin',
                'last_name'    => 'User',
                'email'        => 'admin@example.com',
                'confirm_code' => '1111',
                'created_by'   => 1,
                'updated_by'   => 1,
            ]);
        }
    }
}
