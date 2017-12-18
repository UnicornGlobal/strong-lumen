<?php

use App\Role;
use App\User;
use Illuminate\Support\Facades\Auth;
use Webpatser\Uuid\Uuid;

class RoleMiddlewareTest extends TestCase
{
    // To keep DB clean
    use \Laravel\Lumen\Testing\DatabaseTransactions;
    private $testUserId;
    private $adminId, $userId, $systemId;
    public function setUp()
    {
        parent::setUp();
        $this->post('/login', [
            'username' => 'admin',
            'password' => 'admin',
        ], [ 'Debug-Token' => env('DEBUG_TOKEN')]);
        $this->testUserId = '4BFE1010-C11D-4739-8C24-99E1468F08F6';
        $this->actingAs(Auth::user())->get('/roles');
        $roles = json_decode($this->response->getContent());

        foreach($roles as $role){
            switch ($role->name) {
                case 'admin':
                    $this->adminId = $role->_id;
                    break;
                case 'user':
                    $this->userId = $role->_id;
                    break;
                case 'system':
                    $this->systemId = $role->_id;

            }

        }
    }

    public function testBadRole()
    {
        // before adding admin role
        $user = User::loadFromUuid($this->testUserId);

        $this->actingAs($user)->get(
            '/users/' . $this->testUserId . '/roles',
            ['Debug-Token' => env('DEBUG_TOKEN')]
        );
        $this->assertResponseStatus(401);
        $this->assertEquals('{"error":"Incorrect Role"}', $this->response->getContent());
    }

    public function testAssignRole()
    {
        $this->actingAs(Auth::user())->post(
            '/users/'
          . $this->testUserId
          . '/roles/assign/'
          . $this->adminId
        );

        $this->actingAs(Auth::user())->get('/users/' . $this->testUserId . '/roles');

        // Check to see if the role was assigned successfully
        $roles = json_decode($this->response->getContent());
        $this->assertEquals('admin', $roles[0]->name);

        $this->actingAs(Auth::user())->post(
            '/users/'
            . $this->testUserId
            . '/roles/assign/'
            . Uuid::generate(4)
        );

        $this->assertEquals('{"error":"Invalid Role ID"}', $this->response->getContent());
    }

    public function testRevokeRole()
    {
        $testUser = User::loadFromUuid($this->testUserId);

        // Add user_role
        $this->actingAs(Auth::user())->post(
            '/users/'
          . $this->testUserId . '/roles/assign/'
          . $this->adminId
        );

        $this->actingAs($testUser)
               ->get('/users/' . $this->testUserId . '/roles');

        $roles = json_decode($this->response->getContent());

        // Ensure we added the role correctly
        $this->assertEquals('admin', $roles[0]->name);

        // removing a non-existent role
        $this->actingAs(Auth::user())->post(
            '/users/'
            . $this->testUserId
            . '/roles/revoke/'
            . Uuid::generate(4)
        );
        $this->assertEquals('{"error":"Invalid Role ID"}', $this->response->getContent());

        // remove the role
        $this->actingAs(Auth::user())->post(
            '/users/'
          . $this->testUserId
          . '/roles/revoke/'
          . $this->adminId
        );

        $this->actingAs($testUser)->get('/users/' . $this->testUserId . '/roles');

        // ensure we get access denied error
        $this->assertResponseStatus(401);
    }

    public function testCreateRole()
    {
        $this->actingAs(Auth::user())->post('/roles/intern');

        $internId = json_decode($this->response->getContent())->uuid;

        $this->actingAs(Auth::user())->post('/users/' . $this->testUserId . '/roles/assign/' . $internId);
        $this->actingAs(Auth::user())->get('/users/' . $this->testUserId . '/roles');
        $roles = json_decode($this->response->getContent());

        $this->assertEquals('intern', $roles[0]->name);
        $this->actingAs(Auth::user())->post('/roles/mm');
        $this->assertEquals('Role name invalid', $this->response->getContent());

        $this->actingAs(Auth::user())->post('/roles/intern');

        $this->assertEquals('Role name invalid', $this->response->getContent());
    }

    public function testDeleteRole()
    {
        $this->actingAs(Auth::user())->post('/roles/intern');
        $internId = json_decode($this->response->getContent())->uuid;

        $this->assertNotNull(Role::where('name', 'intern')->first());

        $this->actingAs(Auth::user())->delete('/roles/' . $internId);
        $this->assertNull(Role::where('name', 'intern')->first());

        $this->assertNotNull(Role::withTrashed()->where('name', 'intern')->first());
    }

    public function testDeleteRoleFail()
    {
        $this->actingAs(Auth::user())->post('/roles/intern');
        $internId = json_decode($this->response->getContent())->uuid;
        $this->assertNotNull(Role::where('name', 'intern')->first());
        $this->actingAs(Auth::user())->post('users/' . $this->testUserId . '/roles/assign/' . $internId);
        $this->actingAs(Auth::user())->delete('/roles/' . $internId);
        $this->assertNotNull(Role::where('name', 'intern')->first());
    }

    public function testEmptyRole()
    {
        $this->actingAs(Auth::user())->get('/roles/');
        dd($this->response->getContent());
    }

    public function testInactiveRole()
    {
        $this->assertEquals(1, Role::loadFromUuid($this->adminId)->is_active);

        $this->actingAs(Auth::user())->get('/users/5FFA95F4-5EB4-46FB-94F1-F2B27254725B/roles');
        $this->assertResponseStatus(200);

        $this->actingAs(Auth::user())->post('/roles/' . $this->adminId . '/deactivate');

        $this->actingAs(Auth::user())->get('/users/5FFA95F4-5EB4-46FB-94F1-F2B27254725B/roles');
        $this->assertResponseStatus(401);
    }

    public function testActivateRole()
    {
        $this->actingAs(Auth::user())->post('/roles/intern');
        $internId = json_decode($this->response->getContent())->uuid;
        $this->actingAs(Auth::user())->post('/users/' . $this->testUserId . '/roles/assign/' . $internId);
        $this->actingAs(Auth::user())->get('/users/' . $this->testUserId . '/roles');
        $roles = json_decode($this->response->getContent());
        $this->assertEquals(1, $roles[0]->is_active);

        $this->actingAs(Auth::user())->post('/roles/' . $internId . '/deactivate');

        $this->actingAs(Auth::user())->get('users/' . $this->testUserId . '/roles');
        $roles = json_decode($this->response->getContent());
        $this->assertEquals(0, $roles[0]->is_active);

        $this->actingAs(Auth::user())->post('/roles/' . $internId . '/activate');

        $this->actingAs(Auth::user())->get('users/' . $this->testUserId . '/roles');
        $roles = json_decode($this->response->getContent());
        $this->assertEquals(1, $roles[0]->is_active);
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
        $this->actingAs(Auth::user())->post('/roles/intern');
        $internId = json_decode($this->response->getContent())->uuid;

        // Fail
        $this->actingAs(User::where('id', 2)->first())->get('/test');
        $this->assertResponseStatus(401);
        $this->assertEquals('{"error":"Incorrect Role"}', $this->response->getContent());

        // Only 1st role
        $this->actingAs(User::where('id', 3)->first())->post('/users/' . $this->testUserId . '/roles/assign/' . $internId);
        $this->actingAs(User::where('id', 2)->first())->get('/test');
        $this->assertResponseStatus(200);
        $this->assertEquals('Test', $this->response->getContent());

        // Only 2nd role
        $this->actingAs(User::where('id', 3)->first())->post('/users/' . $this->testUserId . '/roles/revoke/' . $internId);
        $this->actingAs(User::where('id', 3)->first())->post('/users/' . $this->testUserId . '/roles/assign/' . $this->systemId);

        $this->actingAs(User::where('id', 2)->first())->get('/test');
        $this->assertResponseStatus(200);
        $this->assertEquals('Test', $this->response->getContent());

        // both roles
        $this->actingAs(User::where('id', 3)->first())->post('/users/' . $this->testUserId . '/roles/assign/' . $internId);

        $this->actingAs(User::where('id', 2)->first())->get('/test');
        $this->assertResponseStatus(200);
        $this->assertEquals('Test', $this->response->getContent());

        // both assigned, 1 inactive
        $this->actingAs(User::where('id', 3)->first())->post('roles/' . $internId . '/deactivate');
        $this->actingAs(User::where('id', 2)->first())->get('/test');
        $this->assertResponseStatus(200);
        $this->assertEquals('Test', $this->response->getContent());

        // both assigned, both inactive
        $this->actingAs(User::where('id', 3)->first())->post('roles/' . $this->systemId . '/deactivate');
        $this->actingAs(User::where('id', 2)->first())->get('/test');

        $this->assertResponseStatus(401);
        $this->assertEquals('{"error":"Incorrect Role"}', $this->response->getContent());
    }

    public function testIncorrectRole()
    {
        $router = $this->app->router;
        $this->app->router->group(['middleware' => ['roles:system,intern']], function () use ($router) {
            $router->get('/test', function () {
                return 'Test';
            });
        });
        $this->actingAs(Auth::user())->get('/roles/' . Uuid::generate(4));
        $this->assertEquals('{"error":"Invalid Role ID"}', $this->response->getContent());
        $this->actingAs(Auth::user())->get('/test');
        $this->assertResponseStatus(401);
        $this->assertEquals('{"error":"Incorrect Role"}', $this->response->getContent());
    }

    public function testGetRoles()
    {
        $this->actingAs(Auth::user())->get('/roles');
        $roles = json_decode($this->response->getContent());
        $this->assertEquals('system', $roles[0]->name);

        $this->actingAs(Auth::user())->post('/roles/intern');

        $this->actingAs(Auth::user())->get('/roles');
        $roles = json_decode($this->response->getContent());
        $this->assertEquals('intern', $roles[4]->name);
    }

    public function testGetUserForRole()
    {
        $this->actingAs(Auth::user())->get('/roles/' . $this->adminId . '/users');
        $users = json_decode($this->response->getContent());
        $this->assertEquals('5FFA95F4-5EB4-46FB-94F1-F2B27254725B', $users[0]->_id);
    }
}
