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
            'password' => 'password',
        ], ['Debug-Token' => env('DEBUG_KEY')]);

        $result = json_decode($this->response->getContent());

        $this->assertEquals(5, count((array) $result));
        $this->assertEquals(7, count((array) $result->user));

        $this->assertContains('Bearer', $this->response->headers->get('Authorization'));

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
            'password' => 'password',
        ], ['Debug-Token' => env('DEBUG_KEY')]);

        $result = json_decode($this->response->getContent());

        $this->assertContains('Bearer', $this->response->headers->get('Authorization'));

        $this->assertObjectHasAttribute('user', $result);
        $this->assertObjectHasAttribute('jwt', $result);

        $token = $result->jwt;

        $this->post('/logout', [], [
            'Authorization' => sprintf('Bearer %s', $token),
        ]);

        $this->assertEquals('200', $this->response->status());

        $result = json_decode($this->response->getContent());

        $this->assertContains('Successfully logged out', $result->message);
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
            'password' => 'password',
        ], ['Debug-Token' => env('DEBUG_KEY')]);

        $this->assertEquals('200', $this->response->status());

        $result = json_decode($this->response->getContent());

        $this->assertContains('Bearer', $this->response->headers->get('Authorization'));

        $this->assertObjectHasAttribute('user', $result);
        $this->assertObjectHasAttribute('jwt', $result);

        $token = $result->jwt;

        // Login without those those details
        $this->post('/api/users/change-password', [
            'username'    => 'user',
            'password'    => 'password',
            'newpassword' => 'newpassword',
        ], [
            'Debug-Token'   => env('DEBUG_KEY'),
            'Authorization' => sprintf('Bearer %s', $token),
        ]);

        $this->assertEquals('200', $this->response->status());

        $this->post('/logout', [], [
            'Authorization' => sprintf('Bearer %s', $token),
        ]);

        $this->assertEquals('200', $this->response->status());

        $result = json_decode($this->response->getContent());

        $this->assertContains('Successfully logged out', $result->message);

        // Login without those those details
        $this->post('/login', [
            'username' => 'user',
            'password' => 'newpassword',
        ], ['Debug-Token' => env('DEBUG_KEY')]);

        $result = json_decode($this->response->getContent());

        $this->assertEquals(5, count((array) $result));
        $this->assertEquals(7, count((array) $result->user));

        $this->assertContains('Bearer', $this->response->headers->get('Authorization'));

        $this->assertObjectHasAttribute('user', $result);
        $this->assertObjectHasAttribute('jwt', $result);

        $this->assertEquals($user->_id, $result->user->_id);
        $this->assertEquals($user->email, $result->user->email);

        $token = $result->jwt;

        // Login without those those details
        $this->post('/api/users/change-password', [
            'username'    => 'user',
            'password'    => 'newpassword',
            'newpassword' => 'password',
        ], [
            'Debug-Token'   => env('DEBUG_KEY'),
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
            'password' => 'password',
        ], ['Debug-Token' => env('DEBUG_KEY')]);

        $this->assertEquals('200', $this->response->status());

        $result = json_decode($this->response->getContent());
        $token = $result->jwt;

        $this->post('/api/users/change-password', [
            'username'    => 'user',
            'password'    => 'password',
            'newpassword' => 'password',
        ], [
            'Authorization' => sprintf('Bearer %s', $token),
        ]);

        $this->assertEquals('{"error":"The given data was invalid."}', $this->response->getContent());
        $this->assertEquals('500', $this->response->status());

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
        // Get the test user
        $user = User::where('_id', env('TEST_USER_ID'))->first();

        // Login without those those details
        $this->post('/login', [
            'username' => 'user',
            'password' => 'password',
        ], ['Debug-Token' => env('DEBUG_KEY')]);

        $this->assertEquals('200', $this->response->status());

        $result = json_decode($this->response->getContent());
        $token = $result->jwt;

        $this->post('/api/users/change-password', [
            'username' => 'user',
            'password' => 'password',
        ], [
            'Authorization' => sprintf('Bearer %s', $token),
        ]);

        $this->assertEquals('{"error":"The given data was invalid."}', $this->response->getContent());
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
            'password' => 'password',
        ], ['Debug-Token' => env('DEBUG_KEY')]);

        $result = json_decode($this->response->getContent());

        $this->assertContains('Bearer', $this->response->headers->get('Authorization'));

        $this->assertObjectHasAttribute('user', $result);
        $this->assertObjectHasAttribute('jwt', $result);

        $token = $result->jwt;

        $this->post('/refresh', [], [
            'Authorization' => sprintf('Bearer %s', $token),
        ]);

        $this->assertEquals('200', $this->response->status());

        $result = json_decode($this->response->getContent());

        $this->assertContains('Bearer', $this->response->headers->get('Authorization'));
    }
}
