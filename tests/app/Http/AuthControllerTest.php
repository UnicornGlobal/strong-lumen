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
        $this->assertEquals(8, count((array) $result->user));

        $this->assertStringContainsString('Bearer', $this->response->headers->get('Authorization'));

        $this->assertObjectHasAttribute('user', $result);
        $this->assertObjectHasAttribute('jwt', $result);

        $this->assertEquals($user->_id, $result->user->_id);
        $this->assertEquals($user->email, $result->user->email);
    }

    /**
     * @return void
     */
    public function testLogout()
    {
        // Get the test user
        $user = User::where('_id', env('TEST_USER_ID'))->first();

        // Login without those those details
        $this->post('/login', [
            'username' => 'user',
            'password' => 'user',
        ], ['Debug-Token' => env('DEBUG_TOKEN')]);

        $result = json_decode($this->response->getContent());

        $this->assertStringContainsString('Bearer', $this->response->headers->get('Authorization'));

        $this->assertObjectHasAttribute('user', $result);
        $this->assertObjectHasAttribute('jwt', $result);

        $token = $result->jwt;

        $this->post('/logout', [], [
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
        // Get the test user
        $user = User::where('_id', env('TEST_USER_ID'))->first();

        // Login without those those details
        $this->post('/login', [
            'username' => 'user',
            'password' => 'user',
        ], ['Debug-Token' => env('DEBUG_TOKEN')]);

        $this->assertEquals('200', $this->response->status());

        $result = json_decode($this->response->getContent());

        $this->assertStringContainsString('Bearer', $this->response->headers->get('Authorization'));

        $this->assertObjectHasAttribute('user', $result);
        $this->assertObjectHasAttribute('jwt', $result);

        $token = $result->jwt;

        // Login without those those details
        $this->post('/api/users/change-password', [
            'username'    => 'user',
            'password'    => 'user',
            'newpassword' => 'newpassword',
        ], [
            'Debug-Token'   => env('DEBUG_TOKEN'),
            'Authorization' => sprintf('Bearer %s', $token),
        ]);

        $this->assertEquals('200', $this->response->status());

        $this->post('/logout', [], [
            'Authorization' => sprintf('Bearer %s', $token),
        ]);

        $this->assertEquals('200', $this->response->status());

        $result = json_decode($this->response->getContent());

        $this->assertStringContainsString('Successfully logged out', $result->message);

        // Login without those those details
        $this->post('/login', [
            'username' => 'user',
            'password' => 'newpassword',
        ], ['Debug-Token' => env('DEBUG_TOKEN')]);

        $result = json_decode($this->response->getContent());

        $this->assertEquals(5, count((array) $result));
        $this->assertEquals(8, count((array) $result->user));

        $this->assertStringContainsString('Bearer', $this->response->headers->get('Authorization'));

        $this->assertObjectHasAttribute('user', $result);
        $this->assertObjectHasAttribute('jwt', $result);

        $this->assertEquals($user->_id, $result->user->_id);
        $this->assertEquals($user->email, $result->user->email);

        $token = $result->jwt;

        // Login without those those details
        $this->post('/api/users/change-password', [
            'username'    => 'user',
            'password'    => 'newpassword',
            'newpassword' => 'user',
        ], [
            'Debug-Token'   => env('DEBUG_TOKEN'),
            'Authorization' => sprintf('Bearer %s', $token),
        ]);

        $this->assertEquals('200', $this->response->status());

        $this->post('/logout', [], [
            'Authorization' => sprintf('Bearer %s', $token),
        ]);

        $this->assertEquals('200', $this->response->status());
    }

    /**
     * @return void
     */
    public function testChangePasswordSamePassword()
    {
        // Get the test user
        $user = User::where('_id', env('TEST_USER_ID'))->first();

        // Login without those those details
        $this->post('/login', [
            'username' => 'user',
            'password' => 'user',
        ], ['Debug-Token' => env('DEBUG_TOKEN')]);

        $this->assertEquals('200', $this->response->status());

        $result = json_decode($this->response->getContent());
        $token = $result->jwt;

        $this->post('/api/users/change-password', [
            'username'    => 'user',
            'password'    => 'user',
            'newpassword' => 'user',
        ], [
            'Authorization' => sprintf('Bearer %s', $token),
        ]);

        $this->assertEquals('422', $this->response->status());
        $this->assertEquals('{"newpassword":["The newpassword and password must be different."]}', $this->response->getContent());

        $this->post('/api/users/change-password', [
            'username'    => 'user',
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
            'username' => 'user',
            'password' => 'user',
        ], [
            'Authorization' => sprintf('Bearer %s', $token),
        ]);

        $this->assertEquals('{"newpassword":["The newpassword field is required."]}', $this->response->getContent());
        $this->assertEquals('422', $this->response->status());
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
        ], ['Debug-Token' => env('DEBUG_TOKEN')]);

        $result = json_decode($this->response->getContent());

        $this->assertStringContainsString('Bearer', $this->response->headers->get('Authorization'));

        $this->assertObjectHasAttribute('user', $result);
        $this->assertObjectHasAttribute('jwt', $result);

        $token = $result->jwt;

        $this->post('/refresh', [], [
            'Authorization' => sprintf('Bearer %s', $token),
        ]);

        $this->assertEquals('200', $this->response->status());

        $result = json_decode($this->response->getContent());

        $this->assertStringContainsString('Bearer', $this->response->headers->get('Authorization'));
    }
}
