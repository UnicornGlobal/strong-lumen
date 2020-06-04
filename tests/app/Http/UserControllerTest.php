<?php

use App\Events\ResendVerification;
use App\Listeners\OnResendVerification;
use App\Mail\ConfirmAccountMessage;
use App\User;
use Faker\Factory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Mail;
use Laravel\Lumen\Testing\DatabaseTransactions;

class UserControllerTest extends TestCase
{
    use DatabaseTransactions;

    public function testResendVerification()
    {
        Mail::fake();
        $this->expectsEvents('App\Events\ResendVerification');

        $this->actingAs($this->user)->get('api/resend/verification');
        $this->assertResponseStatus(200);
    }

    public function testResendVerificationEvent()
    {
        Mail::fake();

        $listener = new OnResendVerification();
        $listener->handle(new ResendVerification($this->user));

        Mail::assertQueued(ConfirmAccountMessage::class, function ($mail) {
            $this->assertStringContainsString(sprintf('%s/confirm/', env('API_URL')), $mail->link);

            $this->assertRegExp(
                '/.*\/[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/',
                $mail->link
            );

            $render = $mail->build();
            $this->assertEquals(sprintf('Confirm Your %s Account', env('APP_NAME')), $render->subject);
            $this->assertEquals($this->user->email, $render->to[0]['address']);

            $this->get($mail->link);

            $this->assertResponseStatus(302);
            $this->assertStringContainsString('Redirecting to <a href="', $this->response->getContent());
            $this->assertStringContainsString('confirmed', $this->response->getContent());
            $this->assertStringContainsString('refresh', $this->response->getContent());

            return true;
        });
    }

    public function testGetUser()
    {
        $user = factory('App\User')->make();

        // The test user in our seed
        $this->actingAs($user)->get('/api/users/'.env('TEST_USER_ID'));

        $resultObject = json_decode($this->response->getContent());
        $resultArray = json_decode($this->response->getContent(), true);

        $this->assertEquals(10, count($resultArray));

        // Should have username `user`
        $this->assertEquals('user', $resultObject->username);

        // Should have an email
        $this->assertEquals('developer@example.com', $resultObject->email);

        $this->assertResponseStatus(200);
    }

    public function testGetSelf()
    {
        $user = User::where('_id', env('TEST_USER_ID'))->first();

        // The test user in our seed
        $this->actingAs($user)->get('/api/me');

        $resultObject = json_decode($this->response->getContent());
        $resultArray = json_decode($this->response->getContent(), true);

        $this->assertEquals(12, count($resultArray));

        // Should have username `user`
        $this->assertEquals('user', $resultObject->username);

        $this->assertObjectHasAttribute('roles', $resultObject);
        $this->assertObjectHasAttribute('documents', $resultObject);
        $this->assertEmpty($resultObject->documents);
        $this->assertEquals(1, count($resultObject->roles));

        // Should have an email
        $this->assertEquals('developer@example.com', $resultObject->email);

        // Has no role
        $this->assertEquals(1, count($resultArray['roles']));

        $this->assertResponseStatus(200);

        // Test with admin user to check roles in response
        $adminUser = User::where('_id', env('ADMIN_USER_ID'))->first();
        $this->actingAs($adminUser)->get('/api/me');
        $result = json_decode($this->response->getContent());
        $this->assertObjectHasAttribute('roles', $result);

        $roles = new Collection($result->roles);

        $this->assertEquals(2, count($roles));
        $this->assertTrue($roles->contains('name', 'admin'));
    }

    public function testGetEmptyUser()
    {
        $user = factory('App\User')->make();

        // The test user in our seed
        $this->actingAs($user)->get('/api/users/0');

        $this->assertEquals('{"error":"There was a problem retrieving the user."}', $this->response->getContent());

        $this->assertEquals('422', $this->response->status());
    }

    public function testGetBadUser()
    {
        $user = factory('App\User')->make();

        $this->actingAs($user)->get('/api/users/12');

        $this->assertEquals('{"error":"Invalid User ID"}', $this->response->getContent());

        $this->assertEquals('422', $this->response->status());
    }

    public function testGetBadUserFormat()
    {
        $user = factory('App\User')->make();

        // The test user in our seed
        $this->actingAs($user)->get('/api/users/xxx');

        $this->assertEquals('{"error":"Invalid User ID"}', $this->response->getContent());

        $this->assertEquals('422', $this->response->status());
    }

    public function testChangeDetails()
    {
        // Get the test user
        $user = User::where('_id', env('TEST_USER_ID'))->first();
        $this->assertEquals('Test', $user->first_name);
        $this->assertEquals('User', $user->last_name);

        // Update own details
        $this->actingAs($user)->post('/api/users/'.env('TEST_USER_ID'), [
            'firstName' => 'Changed',
            'lastName'  => 'Changed',
        ]);

        $this->actingAs($user)->get('/api/users/'.env('TEST_USER_ID'));

        $resultObject = json_decode($this->response->getContent());

        // Details should have changed
        $this->assertEquals('Changed', $resultObject->first_name);
        $this->assertEquals('Changed', $resultObject->last_name);

        // Update mobile and location
        $this->actingAs($user)->post('/api/users/'.env('TEST_USER_ID'), [
            'mobile' => '+27822222222',
            'location'  => 'Somewhere',
        ]);

        $this->actingAs($user)->get('/api/users/'.env('TEST_USER_ID'));

        $resultObject = json_decode($this->response->getContent());

        // Details should have changed
        $this->assertEquals('+27822222222', $resultObject->mobile);
        $this->assertEquals('Somewhere', $resultObject->location);
    }

    public function testChangeBadDetails()
    {
        // Get the test user
        $user = User::where('_id', env('TEST_USER_ID'))->first();
        $this->assertEquals('Test', $user->first_name);
        $this->assertEquals('User', $user->last_name);

        // Invalid
        $this->actingAs($user)->post('/api/users/4', [
            'firstName' => 'Changed',
            'lastName'  => 'Changed',
        ]);

        $this->assertEquals('422', $this->response->status());

        $this->assertEquals(
            '{"error":"Invalid User ID"}',
            $this->response->getContent()
        );

        // Other user
        $this->actingAs($this->user)->post(sprintf('/api/users/%s', $this->secondUserId), [
            'firstName' => 'Changed',
            'lastName'  => 'Changed',
        ]);

        $this->assertEquals('422', $this->response->status());

        $this->assertEquals(
            '{"error":"Illegal attempt to adjust another users details. The suspicious action has been logged."}',
            $this->response->getContent()
        );
    }

    public function testAdminCanGetAllUsers()
    {
        $faker = Factory::create();

        for ($i = 1; $i < 5; $i++) {
            $user = new User([
                '_id'          => $faker->uuid,
                'api_key'      => $faker->uuid,
                'username'     => $faker->userName,
                'password'     => $faker->password,
                'first_name'   => $faker->firstName,
                'last_name'    => $faker->lastName,
                'email'        => $faker->email,
                'confirm_code' => $faker->linuxPlatformToken,
                'confirmed_at' => \Carbon\Carbon::now(),
                'created_by'   => 1,
                'updated_by'   => 1,
            ]);

            $user->save();
        }

        $num_users = User::count();

        $admin_user = User::where('_id', env('ADMIN_USER_ID'))->first();
        $normal_user = User::where('_id', env('TEST_USER_ID'))->first();

        $this->actingAs($normal_user)->get(route('admin.users.all'));
        $this->assertResponseStatus(401);

        $this->actingAs($admin_user)->get(route('admin.users.all'));
        $this->assertResponseOk();

        $response = json_decode($this->response->getContent());
        $this->assertObjectHasAttribute('users', $response);
        $this->assertCount($num_users, $response->users);
    }

    public function testDeleteNewUser()
    {
        $adminUser = User::where('_id', env('ADMIN_USER_ID'))->first();
        $this->post(
            '/register/email',
            [
                'username'  => 'user@example.com',
                'password'  => 'password',
                'firstName' => 'First',
                'lastName'  => 'Last',
                'email'     => 'user@example.com',
            ],
            [
                'Registration-Access-Key' => env('REGISTRATION_ACCESS_KEY'),
            ]
        );

        $this->assertEquals('201', $this->response->status());
        $userId = json_decode($this->response->getContent())->_id;
        $this->actingAs($adminUser)->delete(sprintf('/api/admin/users/%s', $userId));

        $result = json_decode($this->response->getContent());
        $this->assertResponseStatus(202);
        $this->assertTrue($result->success);
        $this->assertNull(User::where('_id', $userId)->first());
        $this->assertNotNull(User::where('_id', $userId)->withTrashed()->first());
    }

    public function testDeleteAdminUser()
    {
        $adminUser = User::where('_id', env('ADMIN_USER_ID'))->first();
        $this->actingAs($adminUser)->delete(sprintf('/api/admin/users/%s', $adminUser->_id));
        $result = json_decode($this->response->getContent());
        $this->assertResponseStatus(404);
        $this->assertEquals('User has a role other than \'user\', cannot delete', $result->error);
    }
}
