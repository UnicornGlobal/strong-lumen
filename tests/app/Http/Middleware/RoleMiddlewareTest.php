<?php

use App\Role;
use App\User;
use Illuminate\Support\Facades\Auth;
use Webpatser\Uuid\Uuid;

class RoleMiddlewareTest extends TestCase
{
    //To keep DB clean
    use \Laravel\Lumen\Testing\DatabaseTransactions;
    private $testUserId;
    public function setUp()
    {
        parent::setUp();
        $this->post('/login', [
            'username' => 'admin',
            'password' => 'admin',
        ], [ 'Debug-Token' => env('DEBUG_TOKEN')]);
        $this->testUserId = '4BFE1010-C11D-4739-8C24-99E1468F08F6';
    }

    public function testBadRole()
    {
        // before adding admin role
        $user = User::where('_id', $this->testUserId)->first();

        $this->actingAs($user)->get(
            '/roles/getUserRoles/' . $this->testUserId,
            ['Debug-Token' => env('DEBUG_TOKEN')]
        );
        $this->assertResponseStatus(401);
        $this->assertEquals('{"error":"Incorrect Role"}', $this->response->getContent());
    }

    public function testAssignRole()
    {
        $this->actingAs(Auth::user())->post('/roles/assignRole/' . $this->testUserId . '/admin');

        $this->actingAs(Auth::user())->get('/roles/getUserRoles/' . $this->testUserId);

        //Check to see if the role was assigned successfully
        $roles = json_decode($this->response->getContent());
        $this->assertEquals('admin', $roles[0]->name);
    }

    public function testRevokeRole()
    {
        $testUser = User::where('_id', $this->testUserId)->first();

        // Add user_role
        $this->actingAs(Auth::user())->post('roles/assignRole/' . $this->testUserId . '/admin');

        $this->actingAs($testUser)
               ->get('/roles/getUserRoles/' . $this->testUserId);

        $roles = json_decode($this->response->getContent());

        // Ensure we added the role correctly
        $this->assertEquals('admin', $roles[0]->name);

        //remove the role
        $this->actingAs(Auth::user())->post('roles/revokeRole/' . $this->testUserId .'/admin');

        $this->actingAs($testUser)->get('/roles/getUserRoles/' . $this->testUserId);

        //ensure we get access denied error
        $this->assertResponseStatus(401);
    }

    public function testCreateRole()
    {
        $this->actingAs(Auth::user())->post('/roles/createRole/intern');

        $this->actingAs(Auth::user())->post('/roles/assignRole/' . $this->testUserId . '/intern');
        $this->actingAs(Auth::user())->get('/roles/getUserRoles/' . $this->testUserId);
        $roles = json_decode($this->response->getContent());
        //dd($this->response);
        $this->assertEquals('intern', $roles[0]->name);
    }

    public function testDeleteRole()
    {
        $this->actingAs(Auth::user())->post('/roles/createRole/intern');
        $this->assertNotNull(Role::where('name', 'intern')->first());

        $this->actingAs(Auth::user())->post('/roles/deleteRole/intern');
        $this->assertNull(Role::where('name', 'intern')->first());

        $this->assertNotNull(Role::withTrashed()->where('name', 'intern')->first());
    }

    public function testDeleteRoleFail()
    {
        $this->actingAs(Auth::user())->post('/roles/createRole/intern');
        $this->assertNotNull(Role::where('name', 'intern')->first());
        $this->actingAs(Auth::user())->post('roles/assignRole/' . $this->testUserId . '/intern');
        $this->actingAs(Auth::user())->post('/roles/deleteRole/intern');
        $this->assertNotNull(Role::where('name', 'intern')->first());
    }

    public function testInactiveRole()
    {
        $this->assertEquals(1, Role::where('name', 'admin')->first()->isActive());

        $this->actingAs(Auth::user())->get('/roles/getUserRoles/5FFA95F4-5EB4-46FB-94F1-F2B27254725B');
        $this->assertResponseStatus(200);

        $this->actingAs(Auth::user())->post('/roles/deactivate/admin');

        $this->actingAs(Auth::user())->get('/roles/getUserRoles/5FFA95F4-5EB4-46FB-94F1-F2B27254725B');
        $this->assertResponseStatus(401);
    }

    public function testActivateRole()
    {
        $this->actingAs(Auth::user())->post('/roles/createRole/intern');
        $this->actingAs(Auth::user())->post('/roles/assignRole/' . $this->testUserId . '/intern');
        $this->actingAs(Auth::user())->get('/roles/getUserRoles/' . $this->testUserId);
        $roles = json_decode($this->response->getContent());
        $this->assertEquals(1, $roles[0]->is_active);

        $this->actingAs(Auth::user())->post('/roles/deactivate/intern');

        $this->actingAs(Auth::user())->get('roles/getUserRoles/' . $this->testUserId);
        $roles = json_decode($this->response->getContent());
        $this->assertEquals(0, $roles[0]->is_active);
    }

    public function testEmptyRoles()
    {
        $router = $this->app->router;
        $this->app->router->group(['middleware' => ['roles']], function () use ($router) {
            $router->get('/test', function () {
                return "Test";
            });
        });
        $this->actingAs(Auth::user())->get('/test');
        $this->assertEquals('Test', $this->response->getContent());

        $noRole = new User();

        $this->actingAs($noRole)->get('/test');

        $this->assertResponseStatus(401);
        $this->assertEquals('{"error":"Incorrect Role"}', $this->response->getContent());
    }

    public function testMultipleRoles()
    {
        $router = $this->app->router;
        $this->app->router->group(['middleware' => ['roles:system,intern']], function () use ($router) {
            $router->get('/test', function () {
                return 'Test';
            });
        });
        $this->actingAs(Auth::user())->post('/roles/createRole/intern');
        $this->actingAs(Auth::user())->post('/roles/createRole/system');

        //Fail
        $this->actingAs(User::where('id', 2)->first())->get('/test');
        $this->assertResponseStatus(401);
        $this->assertEquals('{"error":"Incorrect Role"}', $this->response->getContent());

        //Only 1st role
        $this->actingAs(User::where('id', 3)->first())->post('/roles/assignRole/' . $this->testUserId .'/intern');
        $this->actingAs(User::where('id', 2)->first())->get('/test');
        $this->assertResponseStatus(200);
        $this->assertEquals('Test', $this->response->getContent());

        //Only 2nd role
        $this->actingAs(User::where('id', 3)->first())->post('/roles/revokeRole/' . $this->testUserId . '/intern');
        $this->actingAs(User::where('id', 3)->first())->post('/roles/assignRole/' . $this->testUserId . '/system');

        $this->actingAs(User::where('id', 2)->first())->get('/test');
        $this->assertResponseStatus(200);
        $this->assertEquals('Test', $this->response->getContent());

        //both roles
        $this->actingAs(User::where('id', 3)->first())->post('/roles/assignRole/' . $this->testUserId . '/intern');

        $this->actingAs(User::where('id', 2)->first())->get('/test');
        $this->assertResponseStatus(200);
        $this->assertEquals('Test', $this->response->getContent());
    }

    public function testIncorrectRole()
    {
        $router = $this->app->router;
        $this->app->router->group(['middleware' => ['roles:system,intern']], function () use ($router) {
            $router->get('/test', function () {
                return 'Test';
            });
        });
        $this->actingAs(Auth::user())->get('/roles/getRole/intern');
        $this->assertNull(json_decode($this->response->getContent()));
        $this->actingAs(Auth::user())->get('/test');
        $this->assertResponseStatus(500);
        $this->assertEquals('{"error":"Undefined role on route"}', $this->response->getContent());
    }

    public function testGetRoles()
    {
        $this->actingAs(Auth::user())->get('/roles/getAllRoles');
        $roles = json_decode($this->response->getContent());
        $this->assertEquals('admin', $roles[0]->name);

        $this->actingAs(Auth::user())->post('/roles/createRole/intern');

        $this->actingAs(Auth::user())->get('/roles/getAllRoles');
        $roles = json_decode($this->response->getContent());
        $this->assertEquals('intern', $roles[1]->name);
    }

    public function testGetUserForRole()
    {
        $this->actingAs(Auth::user())->get('/roles/getUsers/admin');
        $users = json_decode($this->response->getContent());
        $this->assertEquals('5FFA95F4-5EB4-46FB-94F1-F2B27254725B', $users[0]->_id);
    }
}
