<?php

use App\User;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;

class AuthControllerTest extends TestCase
{
    /**
     * @return void
     */
    public function testLogin()
    {
        // Get the test user
        $user = User::where('_id', '4BFE1010-C11D-4739-8C24-99E1468F08F6')->first();

        // Login without those those details
        $this->post('/login', [
            'username' => 'user',
            'password' => 'user',
        ], [ 'Debug-Token' => env('DEBUG_KEY')]);

        $result = json_decode($this->response->getContent());

        $this->assertEquals(5, count((array)$result));
        $this->assertEquals(7, count((array)$result->user));

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
        $user = User::where('_id', '4BFE1010-C11D-4739-8C24-99E1468F08F6')->first();

        // Login without those those details
        $this->post('/login', [
            'username' => 'user',
            'password' => 'user',
        ], [ 'Debug-Token' => env('DEBUG_KEY')]);

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
    public function testRefresh()
    {
        // Get the test user
        $user = User::where('_id', '4BFE1010-C11D-4739-8C24-99E1468F08F6')->first();

        // Login without those those details
        $this->post('/login', [
            'username' => 'user',
            'password' => 'user',
        ], [ 'Debug-Token' => env('DEBUG_KEY')]);

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
