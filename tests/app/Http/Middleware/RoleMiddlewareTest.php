<?php

use App\Role;
use App\User;
use Illuminate\Support\Facades\Auth;
use Webpatser\Uuid\Uuid;

class RoleMiddlewareTest extends TestCase
{
    //To keep DB clean
    use \Laravel\Lumen\Testing\DatabaseTransactions;
    public function setUp()
    {
        parent::setUp();
        $this->post('/login', [
            'username' => 'user',
            'password' => 'password',
        ], [ 'Debug-Token' => env('DEBUG_TOKEN')]);
    }

    public function testBadRole()
    {
        //before adding admin role
        $user = Auth::user();

        $this->actingAs($user)->get('/test/roles', ['Debug-Token' => env('DEBUG_TOKEN')]);
        $this->assertResponseStatus(401);
        $this->assertEquals('{"error":"Incorrect Role"}', $this->response->getContent());
    }

    public function testAddRole()
    {
        $this->actingAs(Auth::user())->post('/test/addRole/admin');

        $this->actingAs(Auth::user())->get('/test/roles', ['Debug-Token' => env('DEBUG_TOKEN')]);
        //Check to see if the role was checked successfully
        $this->assertEquals('OK', $this->response->getContent());

    }
}