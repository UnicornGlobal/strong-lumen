<?php

use App\User;
use Laravel\Lumen\Testing\DatabaseTransactions;

class AuthControllerTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * @return void
     */
    public function testLogin()
    {
        // Get the test user
        $user = User::where('_id', env('TEST_USER_ID'))->first();

        // Login without those those details
        $this->post('/login', [
            'username' => 'user',
            'password' => 'user',
        ], ['Debug-Token' => env('DEBUG_TOKEN')]);

        $result = json_decode($this->response->getContent());

        $this->assertEquals(5, count((array) $result));
        $this->assertEquals(9, count((array) $result->user));

        $this->assertStringContainsString('Bearer', $this->response->headers->get('Authorization'));

        $this->assertObjectHasAttribute('user', $result);
        $this->assertObjectHasAttribute('jwt', $result);

        $this->assertObjectHasAttribute('username', $result->user);
        $this->assertObjectHasAttribute('first_name', $result->user);
        $this->assertObjectHasAttribute('last_name', $result->user);
        $this->assertObjectHasAttribute('email', $result->user);
        $this->assertObjectHasAttribute('mobile', $result->user);
        $this->assertObjectHasAttribute('confirmed', $result->user);
        $this->assertObjectHasAttribute('profile_picture', $result->user);

        $this->assertEquals($user->_id, $result->user->_id);
        $this->assertEquals($user->email, $result->user->email);
    }

    /**
     * @return void
     */
    public function testLogout()
    {
        // Get the test user
        $this->post('/login', [
            'username' => $this->user->username,
            'password' => 'password',
        ]);

        $result = json_decode($this->response->getContent());
        $token = $result->jwt;

        $this->actingAs($this->user)->post('/logout', [], [
            'Authorization' => sprintf('Bearer %s', $token),
        ]);

        $this->assertEquals('200', $this->response->status());

        $result = json_decode($this->response->getContent());

        $this->assertStringContainsString('Successfully logged out', $result->message);
    }

    /**
     * @return void
     */
    public function testChangePassword()
    {
        $this->post('/login', [
            'username' => $this->user->username,
            'password' => 'password',
        ]);

        $this->assertEquals('200', $this->response->status());

        $result = json_decode($this->response->getContent());
        $token = $result->jwt;

        $this->actingAs($this->user)->post('/api/users/change-password', [
            'username'    => $this->user->username,
            'password'    => 'password',
            'newpassword' => 'newpassword',
        ], [
            'Authorization' => sprintf('Bearer %s', $token),
        ]);

        $this->assertEquals('200', $this->response->status());

        $this->actingAs($this->user)->post('/logout');

        $this->assertEquals('200', $this->response->status());

        $result = json_decode($this->response->getContent());

        $this->assertStringContainsString('Successfully logged out', $result->message);

        $this->user->refresh();

        // Login without those those details
        $this->post('/login', [
            'username' => $this->user->username,
            'password' => 'newpassword',
        ]);

        $result = json_decode($this->response->getContent());
        $token = $result->jwt;

        // Login without those those details
        $this->post('/api/users/change-password', [
            'username'    => $this->user->username,
            'password'    => 'newpassword',
            'newpassword' => 'password',
        ], [
            'Authorization' => sprintf('Bearer %s', $token),
        ]);

        $this->assertEquals('200', $this->response->status());

        $this->actingAs($this->user)->post('/logout');
        $this->assertEquals('200', $this->response->status());
    }

    /**
     * @return void
     */
    public function testChangePasswordSamePassword()
    {
        $this->post('/login', [
            'username' => $this->user->username,
            'password' => 'password',
        ]);

        $this->assertEquals('200', $this->response->status());
        $result = json_decode($this->response->getContent());
        $token = $result->jwt;

        $this->post('/api/users/change-password', [
            'username'    => $this->user->username,
            'password'    => 'password',
            'newpassword' => 'password',
        ], [
            'Authorization' => sprintf('Bearer %s', $token),
        ]);

        $this->assertEquals('422', $this->response->status());
        $this->assertEquals('{"newpassword":["The newpassword and password must be different."]}', $this->response->getContent());
    }

    /**
     * @return void
     */
    public function testChangePasswordBadPassword()
    {
        // Login without those details
        $this->post('/login', [
            'username' => 'user',
            'password' => 'user',
        ], ['Debug-Token' => env('DEBUG_TOKEN')]);

        $this->assertEquals('200', $this->response->status());

        $result = json_decode($this->response->getContent());
        $token = $result->jwt;

        $this->post('/api/users/change-password', [
            'username' => $this->user->username,
            'password' => 'password',
        ], [
            'Authorization' => sprintf('Bearer %s', $token),
        ]);

        $this->assertEquals('{"newpassword":["The newpassword field is required."]}', $this->response->getContent());
        $this->assertEquals('422', $this->response->status());


        $this->post('/api/users/change-password', [
            'username' => $this->user->username,
            'newpassword' => 'password',
        ], [
            'Authorization' => sprintf('Bearer %s', $token),
        ]);

        $this->assertEquals('{"password":["The password field is required."]}', $this->response->getContent());
        $this->assertEquals('422', $this->response->status());

        $this->post('/api/users/change-password', [
            'username'    => $this->user->username,
            'password'    => 'xyz',
            'newpassword' => 'newstrongpassword',
        ], [
            'Authorization' => sprintf('Bearer %s', $token),
        ]);

        $this->assertEquals('{"error":"There was a problem changing the password."}', $this->response->getContent());
        $this->assertEquals('500', $this->response->status());
    }

    /**
     * @return void
     */
    public function testRefresh()
    {
        // Login without those those details
        $this->post('/login', [
            'username' => 'user',
            'password' => 'user',
        ]);

        $result = json_decode($this->response->getContent());
        $token = $result->jwt;

        $this->post('/refresh', [], [
            'Authorization' => sprintf('Bearer %s', $token),
        ]);

        $this->assertEquals('200', $this->response->status());
        $result = json_decode($this->response->getContent());

        $this->assertStringContainsString('Bearer', $this->response->headers->get('Authorization'));
    }
}
