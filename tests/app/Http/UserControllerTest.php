<?php

use App\User;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;

class UserControllerTest extends TestCase
{
    use DatabaseTransactions;

    public function testGetUser()
    {
        $user = factory('App\User')->make();

        // The test user in our seed
        $this->actingAs($user)->get('/api/users/4BFE1010-C11D-4739-8C24-99E1468F08F6');

        $resultObject = json_decode($this->response->getContent());
        $resultArray = json_decode($this->response->getContent(), true);

        $this->assertEquals(7, count($resultArray));

        // Should have username `user`
        $this->assertEquals('user', $resultObject->username);

        // Should have an email
        $this->assertEquals('developer@example.com', $resultObject->email);

        // Response should be a 200
        $this->assertEquals('200', $this->response->status());
    }

    public function testGetSelf()
    {
        $user = User::where('_id', '4BFE1010-C11D-4739-8C24-99E1468F08F6')->first();

        // The test user in our seed
        $this->actingAs($user)->get('/api/me');

        $resultObject = json_decode($this->response->getContent());
        $resultArray = json_decode($this->response->getContent(), true);

        $this->assertEquals(7, count($resultArray));

        // Should have username `user`
        $this->assertEquals('user', $resultObject->username);

        // Should have an email
        $this->assertEquals('developer@example.com', $resultObject->email);

        // Response should be a 200
        $this->assertEquals('200', $this->response->status());
    }

    public function testGetEmptyUser()
    {
        $user = factory('App\User')->make();

        // The test user in our seed
        $this->actingAs($user)->get('/api/users/0');

        $this->assertEquals('{"error":"There was a problem retrieving the user."}', $this->response->getContent());

        $this->assertEquals('500', $this->response->status());
    }

    public function testGetBadUser()
    {
        $user = factory('App\User')->make();

        // The test user in our seed
        $this->actingAs($user)->get('/api/users/4BFE1010-C11D-4739-8C24-000000000000');

        $this->assertEquals('{"error":"Invalid User ID"}', $this->response->getContent());

        $this->assertEquals('500', $this->response->status());
    }

    public function testGetBadUserFormat()
    {
        $user = factory('App\User')->make();

        // The test user in our seed
        $this->actingAs($user)->get('/api/users/xxx');

        $this->assertEquals('{"error":"Invalid User ID"}', $this->response->getContent());

        $this->assertEquals('500', $this->response->status());
    }

    public function testChangeDetails()
    {
        // Get the test user
        $user = User::where('_id', '4BFE1010-C11D-4739-8C24-99E1468F08F6')->first();
        $this->assertEquals('Test', $user->first_name);
        $this->assertEquals('User', $user->last_name);

        // Update own details
        $this->actingAs($user)->post('/api/users/4BFE1010-C11D-4739-8C24-99E1468F08F6', [
            'firstName' => 'Changed',
            'lastName' => 'Changed',
        ]);

        $this->actingAs($user)->get('/api/users/4BFE1010-C11D-4739-8C24-99E1468F08F6');

        $resultObject = json_decode($this->response->getContent());
        $resultArray = json_decode($this->response->getContent(), true);

        $this->assertEquals(7, count($resultArray));

        // Details should have changed
        $this->assertEquals('Changed', $resultObject->first_name);
        $this->assertEquals('Changed', $resultObject->last_name);
    }

    public function testChangeBadDetails()
    {
        // Get the test user
        $user = User::where('_id', '4BFE1010-C11D-4739-8C24-99E1468F08F6')->first();
        $this->assertEquals('Test', $user->first_name);
        $this->assertEquals('User', $user->last_name);

        // Update own details
        $this->actingAs($user)->post('/api/users/4', [
            'firstName' => 'Changed',
            'lastName' => 'Changed',
        ]);

        $this->assertEquals('{"error":"Illegal attempt to adjust another users details. The suspicious action has been logged."}', $this->response->getContent());

        $this->assertEquals('500', $this->response->status());
    }
}
