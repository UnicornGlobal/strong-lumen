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
    private $adminUserId;
    private $userId;
    private $systemUserId;

    public function setUp()
    {
        parent::setUp();
        $this->post('/login', [
            'username' => 'admin',
            'password' => 'admin',
        ], [ 'Debug-Token' => env('DEBUG_TOKEN')]);

        $this->testUserId = User::where('id', 2)->first()->_id;
        $this->actingAs(Auth::user())->get('/roles');
        $roles = json_decode($this->response->getContent());

        foreach ($roles as $role) {
            switch ($role->name) {
                case 'admin':
                    $this->adminUserId = $role->_id;
                    break;
                case 'user':
                    $this->userId = $role->_id;
                    break;
                case 'system':
                    $this->systemUserId = $role->_id;
            }
        }
    }

    public function testBadRole()
    {
        // before adding admin role
        $user = User::loadFromUuid($this->testUserId);

        $this->actingAs($user)->get(
            sprintf('%s/%s/%s', 'users', $this->testUserId, 'roles'),
            ['Debug-Token' => env('DEBUG_TOKEN')]
        );
        $this->assertResponseStatus(401);
        $this->assertEquals('{"error":"Incorrect Role"}', $this->response->getContent());
    }

    public function testAssignRole()
    {
        $this->actingAs(Auth::user())->post(
            sprintf(
                '%s/%s/%s/%s',
                'users',
                $this->testUserId,
                'roles/assign',
                $this->adminUserId
            )
        );

        $this->actingAs(Auth::user())->get(sprintf('%s/%s/%s', 'users', $this->testUserId, 'roles'));

        // Check to see if the role was assigned successfully
        $roles = json_decode($this->response->getContent());
        $this->assertEquals('admin', $roles[0]->name);

        $this->actingAs(Auth::user())->post(
            sprintf(
                '%s/%s/%s/%s',
                'users',
                $this->testUserId,
                'roles/assign',
                Uuid::generate(4)->string
            )
        );

        $this->assertEquals('{"error":"Invalid Role ID"}', $this->response->getContent());
    }

    public function testRevokeRole()
    {
        $testUser = User::loadFromUuid($this->testUserId);

        // Add user_role
        $this->actingAs(Auth::user())->post(
            sprintf(
                '%s/%s/%s/%s',
                'users',
                $this->testUserId,
                'roles/assign',
                $this->adminUserId
            )
        );

        $this->actingAs($testUser)
               ->get(sprintf('%s/%s/%s', 'users', $this->testUserId, 'roles'));

        $roles = json_decode($this->response->getContent());

        // Ensure we added the role correctly
        $this->assertTrue(array_search('admin', array_column($roles, 'name')) !== false);

        // removing a non-existent role
        $this->actingAs(Auth::user())->post(
            sprintf(
                '%s/%s/%s/%s',
                'users',
                $this->testUserId,
                'roles/revoke',
                Uuid::generate(4)->string
            )
        );
        $this->assertEquals('{"error":"Invalid Role ID"}', $this->response->getContent());

        // remove the role
        $this->actingAs(Auth::user())->post(
            sprintf(
                '%s/%s/%s/%s',
                'users',
                $this->testUserId,
                'roles/revoke',
                $this->adminUserId
            )
        );

        $this->actingAs($testUser)->get(sprintf('%s/%s/%s', 'users', $this->testUserId, 'roles'));

        // ensure we get access denied error
        $this->assertResponseStatus(401);
    }

    public function testCreateRole()
    {
        $this->actingAs(Auth::user())->post('/roles/intern');

        $internId = json_decode($this->response->getContent())->_id;

        $this->actingAs(Auth::user())->post(
            sprintf(
                '%s/%s/%s/%s',
                'users',
                $this->testUserId,
                'roles/assign',
                $internId
            )
        );

        $this->actingAs(Auth::user())->get(sprintf('%s/%s/%s', 'users', $this->testUserId, 'roles'));
        $roles = json_decode($this->response->getContent());

        $this->assertTrue(array_search('intern', array_column($roles, 'name')) !== false);

        $this->actingAs(Auth::user())->post('/roles/mm');
        $this->assertEquals('Role name invalid', $this->response->getContent());

        $this->actingAs(Auth::user())->post('/roles/intern');

        $this->assertEquals('Role name invalid', $this->response->getContent());
    }

    public function testDeleteRole()
    {
        $this->actingAs(Auth::user())->post('/roles/intern');
        $internId = json_decode($this->response->getContent())->_id;

        $this->assertNotNull(Role::where('name', 'intern')->first());

        $this->actingAs(Auth::user())->delete(sprintf('%s/%s', 'roles', $internId));
        $this->assertNull(Role::where('name', 'intern')->first());

        $this->assertNotNull(Role::withTrashed()->where('name', 'intern')->first());
    }

    public function testDeleteRoleFail()
    {
        $this->actingAs(Auth::user())->post('roles/intern');
        $internId = json_decode($this->response->getContent())->_id;
        $this->assertNotNull(Role::where('name', 'intern')->first());
        $this->actingAs(Auth::user())->post(
            sprintf(
                '%s/%s/%s/%s',
                'users',
                $this->testUserId,
                'roles/assign',
                $internId
            )
        );

        $this->actingAs(Auth::user())->delete(sprintf('%s/%s', 'roles', $internId));
        $this->assertNotNull(Role::where('name', 'intern')->first());
    }

    public function testInactiveRole()
    {
        $this->assertEquals(1, Role::loadFromUuid($this->adminUserId)->is_active);

        $this->actingAs(Auth::user())->get('/users/5FFA95F4-5EB4-46FB-94F1-F2B27254725B/roles');
        $this->assertResponseStatus(200);

        $this->actingAs(Auth::user())->post(sprintf('%s/%s/%s', 'roles', $this->adminUserId, 'deactivate'));

        $this->actingAs(Auth::user())->get('/users/5FFA95F4-5EB4-46FB-94F1-F2B27254725B/roles');
        $this->assertResponseStatus(401);
    }

    public function testActivateRole()
    {
        $this->actingAs(Auth::user())->post('/roles/intern');
        $internId = json_decode($this->response->getContent())->_id;
        $this->actingAs(Auth::user())->post(
            sprintf(
                '%s/%s/%s/%s',
                'users',
                $this->testUserId,
                'roles/assign',
                $internId
            )
        );
        $this->actingAs(Auth::user())->get(sprintf('%s/%s/%s', 'users', $this->testUserId, 'roles'));
        $roles = json_decode($this->response->getContent());
        $this->assertEquals(1, $roles[0]->is_active);

        $this->actingAs(Auth::user())->post(sprintf('%s/%s/%s', 'roles', $internId, 'deactivate'));

        $this->actingAs(Auth::user())->get(sprintf('%s/%s/%s', 'users', $this->testUserId, 'roles'));
        $roles = json_decode($this->response->getContent());
        $roleIndex = array_search('intern', array_column($roles, 'name'));
        $this->assertEquals(0, $roles[$roleIndex]->is_active);

        $this->actingAs(Auth::user())->post(sprintf('%s/%s/%s', 'roles', $internId, 'activate'));

        $this->actingAs(Auth::user())->get(sprintf('%s/%s/%s', 'users', $this->testUserId, 'roles'));
        $roles = json_decode($this->response->getContent());
        $this->assertEquals(1, $roles[0]->is_active);
    }

    public function testCorrectRole()
    {
        $router = $this->app->router;
        $this->app->router->group(['middleware' => ['role:admin']], function () use ($router) {
            $router->get('/test', function () {
                return "Test";
            });
        });
        $this->actingAs(Auth::user())->get('/test');
        $this->assertEquals('Test', $this->response->getContent());
    }

    public function testIncorrectRole()
    {
        $router = $this->app->router;
        $this->app->router->group(['middleware' => ['role:system']], function () use ($router) {
            $router->get('/test', function () {
                return 'Test';
            });
        });

        $this->actingAs(Auth::user())->get('/test');
        $this->assertResponseStatus(401);
        $this->assertEquals('{"error":"Incorrect Role"}', $this->response->getContent());
    }

    public function testNoRole()
    {
        $router = $this->app->router;
        $this->app->router->group(['middleware' => ['role:admin']], function () use ($router) {
            $router->get('/test', function () {
                return "Test";
            });
        });

        $noRole = new User();

        $this->actingAs($noRole)->get('/test');

        $this->assertResponseStatus(401);
        $this->assertEquals('{"error":"Incorrect Role"}', $this->response->getContent());
    }

    public function testGetInvalidRole()
    {
        //Getting invalid role
        $this->actingAs(Auth::user())->get(sprintf('%s/%s', 'roles', Uuid::generate(4)->string));
        $this->assertEquals('{"error":"Invalid Role ID"}', $this->response->getContent());
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
        $this->actingAs(Auth::user())->get(sprintf('%s/%s/%s', 'roles', $this->adminUserId, 'users'));
        $users = json_decode($this->response->getContent());
        $this->assertEquals('5FFA95F4-5EB4-46FB-94F1-F2B27254725B', $users[0]->_id);
    }
}
