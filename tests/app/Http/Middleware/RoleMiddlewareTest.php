<?php

use App\Role;
use App\User;
use Laravel\Lumen\Testing\DatabaseTransactions;
use Webpatser\Uuid\Uuid;

class RoleMiddlewareTest extends TestCase
{
    // To keep DB clean
    use DatabaseTransactions;

    public function testGoodRole()
    {
        // Admin user
        $this->actingAs($this->adminUser)->get(
            sprintf('users/%s/roles', $this->userId)
        );

        $roles = json_decode($this->response->getContent());
        $this->assertEquals(1, count($roles));

        $this->assertResponseStatus(200);
        $this->assertObjectNotHasAttribute('id', $roles[0]);
        $this->assertObjectNotHasAttribute('created_by', $roles[0]);
        $this->assertObjectNotHasAttribute('updated_by', $roles[0]);
        $this->assertObjectNotHasAttribute('updated_at', $roles[0]);
        $this->assertObjectNotHasAttribute('created_at', $roles[0]);
        $this->assertObjectNotHasAttribute('deleted_at', $roles[0]);
        $this->assertObjectHasAttribute('_id', $roles[0]);
        $this->assertObjectHasAttribute('name', $roles[0]);
        $this->assertObjectHasAttribute('is_active', $roles[0]);

        // Admin User (own roles)
        $this->actingAs($this->adminUser)->get(
            sprintf('users/%s/roles', $this->adminUserId)
        );

        $roles = json_decode($this->response->getContent());
        $this->assertResponseStatus(200);
        $this->assertEquals(2, count($roles));

        // Second User (other users roles)
        $this->actingAs($this->secondUser)->get(
            sprintf('users/%s/roles', $this->userId)
        );

        $this->assertResponseStatus(401);
        $this->assertEquals('{"error":"Incorrect Role"}', $this->response->getContent());
    }

    public function testBadRole()
    {
        $this->actingAs($this->user)->get(
            sprintf('users/%s/roles', $this->userId)
        );
        $this->assertResponseStatus(401);
        $this->assertEquals('{"error":"Incorrect Role"}', $this->response->getContent());
    }

    public function testAssignRole()
    {
        $this->actingAs($this->adminUser)->post(
            sprintf(
                '%s/%s/%s/%s',
                'users',
                $this->userId,
                'roles/assign',
                $this->adminRoleId
            )
        );

        $this->actingAs($this->adminUser)->get(sprintf('users/%s/roles', $this->userId));

        // Check to see if the role was assigned successfully
        $roles = json_decode($this->response->getContent());
        $this->assertEquals(2, count($roles));
        $this->assertEquals('admin', $roles[1]->name);

        // Bad role ID
        $this->actingAs($this->adminUser)->post(
            sprintf(
                '%s/%s/%s/%s',
                'users',
                $this->userId,
                'roles/assign',
                Uuid::generate(4)->string
            )
        );

        $this->assertEquals('{"error":"Invalid Role ID"}', $this->response->getContent());
    }

    public function testRevokeRole()
    {
        // Add user_role
        $this->actingAs($this->adminUser)->post(
            sprintf(
                '%s/%s/%s/%s',
                'users',
                $this->userId,
                'roles/assign',
                $this->adminRoleId
            )
        );

        $this->actingAs($this->adminUser)
             ->get(sprintf('users/%s/roles', $this->userId));

        $roles = json_decode($this->response->getContent());

        // Ensure we added the role correctly
        $this->assertTrue(array_search('admin', array_column($roles, 'name')) !== false);

        // removing a non-existent role
        $this->actingAs($this->adminUser)->post(
            sprintf(
                '%s/%s/%s/%s',
                'users',
                $this->userId,
                'roles/revoke',
                Uuid::generate(4)->string
            )
        );

        $this->assertEquals('{"error":"Invalid Role ID"}', $this->response->getContent());

        // remove the admin role
        $this->actingAs($this->adminUser)->post(
            sprintf(
                '%s/%s/%s/%s',
                'users',
                $this->userId,
                'roles/revoke',
                $this->adminRoleId
            )
        );

        $this->assertEquals('OK', $this->response->getContent());
        $this->assertResponseStatus(200);

        // remove the admin role again
        $this->actingAs($this->adminUser)->post(
            sprintf(
                '%s/%s/%s/%s',
                'users',
                $this->userId,
                'roles/revoke',
                $this->adminRoleId
            )
        );

        $this->assertEquals('OK', $this->response->getContent());
        $this->assertResponseStatus(200);
    }

    public function testCreateRole()
    {
        $this->actingAs($this->adminUser)->post('/roles/intern');
        $internId = json_decode($this->response->getContent())->_id;

        $this->actingAs($this->adminUser)->post(
            sprintf(
                '%s/%s/%s/%s',
                'users',
                $this->userId,
                'roles/assign',
                $internId
            )
        );

        $this->actingAs($this->adminUser)->get(sprintf('users/%s/roles', $this->userId));
        $roles = json_decode($this->response->getContent());

        $this->assertTrue(array_search('intern', array_column($roles, 'name')) !== false);

        $this->actingAs($this->adminUser)->post('/roles/mm');
        $this->assertEquals('{"error":"Role name invalid"}', $this->response->getContent());

        $this->actingAs($this->adminUser)->post('/roles/intern');

        $this->assertEquals('{"error":"Role name invalid"}', $this->response->getContent());
    }

    public function testDeleteRole()
    {
        $this->actingAs($this->adminUser)->post('/roles/intern');
        $internId = json_decode($this->response->getContent())->_id;

        $this->assertNotNull(Role::where('name', 'intern')->first());

        $this->actingAs($this->adminUser)->delete(sprintf('roles/%s', $internId));
        $this->assertNull(Role::where('name', 'intern')->first());

        $this->assertNotNull(Role::withTrashed()->where('name', 'intern')->first());
    }

    public function testDeleteRoleFail()
    {
        $this->actingAs($this->adminUser)->post('roles/intern');
        $internId = json_decode($this->response->getContent())->_id;

        $this->actingAs($this->adminUser)->post(
            sprintf(
                '%s/%s/%s/%s',
                'users',
                $this->userId,
                'roles/assign',
                $internId
            )
        );

        $this->actingAs($this->adminUser)->delete(sprintf('roles/%s', $internId));
        $this->assertNotNull(Role::where('name', 'intern')->first());
    }

    public function testInactiveRole()
    {
        $this->assertEquals(1, Role::loadFromUuid($this->adminRoleId)->is_active);

        $this->actingAs($this->adminUser)->get(sprintf('/users/%s/roles', $this->adminUserId));
        $this->assertResponseStatus(200);

        $this->actingAs($this->adminUser)->post(sprintf('roles/%s/deactivate', $this->adminRoleId));

        $this->actingAs($this->adminUser)->get(sprintf('/users/%s/roles', $this->adminUserId));
        $this->assertResponseStatus(401);
    }

    public function testActivateRole()
    {
        $this->actingAs($this->adminUser)->post('/roles/intern');
        $internId = json_decode($this->response->getContent())->_id;
        $this->actingAs($this->adminUser)->post(
            sprintf(
                '%s/%s/%s/%s',
                'users',
                $this->userId,
                'roles/assign',
                $internId
            )
        );
        $this->actingAs($this->adminUser)->get(sprintf('users/%s/roles', $this->userId));
        $roles = json_decode($this->response->getContent());
        $this->assertEquals(1, $roles[0]->is_active);

        $this->actingAs($this->adminUser)->post(sprintf('roles/%s/deactivate', $internId));

        $this->actingAs($this->adminUser)->get(sprintf('users/%s/roles', $this->userId));
        $roles = json_decode($this->response->getContent());
        $roleIndex = array_search('intern', array_column($roles, 'name'));
        $this->assertEquals(0, $roles[$roleIndex]->is_active);

        $this->actingAs($this->adminUser)->post(sprintf('roles/%s/activate', $internId));

        $this->actingAs($this->adminUser)->get(sprintf('users/%s/roles', $this->userId));
        $roles = json_decode($this->response->getContent());
        $this->assertEquals(1, $roles[0]->is_active);
    }

    public function testCorrectRole()
    {
        $router = $this->app->router;
        $this->app->router->group(['middleware' => ['role:admin']], function () use ($router) {
            $router->get('/test', function () {
                return 'Test';
            });
        });
        $this->actingAs($this->adminUser)->get('/test');
        $this->assertEquals('Test', $this->response->getContent());
    }

    public function testCorrectMultiRole()
    {
        $router = $this->app->router;
        $this->app->router->group(['middleware' => ['role:admin|user']], function () use ($router) {
            $router->get('/test', function () {
                return 'Test';
            });
        });
        $this->actingAs($this->adminUser)->get('/test');
        $this->assertEquals('Test', $this->response->getContent());

        $this->actingAs($this->user)->get('/test');
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

        $this->actingAs($this->adminUser)->get('/test');
        $this->assertResponseStatus(401);
        $this->assertEquals('{"error":"Incorrect Role"}', $this->response->getContent());
    }

    public function testNoRole()
    {
        $router = $this->app->router;
        $this->app->router->group(['middleware' => ['role:admin|user']], function () use ($router) {
            $router->get('/test', function () {
                return 'Test';
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
        $this->actingAs($this->adminUser)->get(sprintf('%s/%s', 'roles', Uuid::generate(4)->string));
        $this->assertEquals('{"error":"Invalid Role ID"}', $this->response->getContent());
    }

    public function testGetRoles()
    {
        $this->actingAs($this->adminUser)->get('/roles');
        $roles = json_decode($this->response->getContent());
        $this->assertEquals('system', $roles[0]->name);

        $this->actingAs($this->adminUser)->post('/roles/intern');

        $this->actingAs($this->adminUser)->get('/roles');
        $roles = json_decode($this->response->getContent());
        $this->assertEquals('intern', $roles[4]->name);
    }

    public function testGetUserForRole()
    {
        $this->actingAs($this->adminUser)->get(sprintf('%s/%s/%s', 'roles', $this->adminRoleId, 'users'));
        $users = json_decode($this->response->getContent());
        $this->assertEquals(env('ADMIN_USER_ID'), $users[0]->_id);
    }

    public function testRequestWithoutLogin()
    {
        $this->get('/roles');
        $result = json_decode($this->response->getContent());
        $this->assertEquals('User not logged in.', $result->error);
    }
}
