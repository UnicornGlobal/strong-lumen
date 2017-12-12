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
            'username' => 'admin',
            'password' => 'admin',
        ], [ 'Debug-Token' => env('DEBUG_TOKEN')]);
    }

    public function testBadRole()
    {
        //before adding admin role
        $user = User::where('id', 2)->first();

        $this->actingAs($user)->get('/roles/1', ['Debug-Token' => env('DEBUG_TOKEN')]);
        $this->assertResponseStatus(401);
        $this->assertEquals('{"error":"Incorrect Role"}', $this->response->getContent());
    }

    public function testAddRole()
    {
        $this->actingAs(Auth::user())->post('roles/assignRole/2/admin');

        $this->actingAs(Auth::user())->get('/roles/2', ['Debug-Token' => env('DEBUG_TOKEN')]);
        //Check to see if the role was assigned successfully
        $roles = json_decode($this->response->getContent());
        $this->assertEquals('admin', $roles[0]->name);
    }

    public function testRemoveRole()
    {
        $testUser = User::where('id', 2)->first();

        $this->actingAs(Auth::user())->post('roles/assignRole/2/admin'); //Add user_role

        $this->actingAs($testUser)
               ->get('/roles/2', ['Debug-Token' => env('DEBUG_TOKEN')]);

        $roles = json_decode($this->response->getContent());
        $this->assertEquals('admin', $roles[0]->name); // Ensure we added the role correctly

        $this->actingAs(Auth::user())->post('roles/revokeRole/2/admin'); //remove the role

        $this->actingAs($testUser)->get('/roles/2', ['Debug-Token' => env('DEBUG_TOKEN')]);
        $this->assertResponseStatus(401); //ensure we get access denied error
    }

    public function testCreateRole()
    {
        $this->actingAs(Auth::user())->post('/roles/createRole/intern');

        $testUser = User::where('id', 2)->first();
        $this->actingAs(Auth::user())->post('roles/assignRole/2/intern'); //Assign new role
        $this->actingAs(Auth::user())->get('/roles/2', ['Debug-Token' => env('DEBUG_TOKEN')]);
        $roles = json_decode($this->response->getContent());
        $this->assertEquals('intern', $roles[0]->name);

    }

    public function testDeleteRole()
    {

    }

//    public function testInactiveRole()
//    {
//
//    }
//
//    public function testEmptyRoles()
//    {
//
//    }
//
//    public function testMultipleRoles()
//    {
//
//    }
//
//    public function testIncorrectRole(){
//
//    }

}