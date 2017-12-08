<?php

class RoleMiddlewareTest extends TestCase
{
    use \Laravel\Lumen\Testing\DatabaseTransactions;

    public function setUp()
    {
        parent::setUp();

        $router = new \Laravel\Lumen\Routing\Router($this->createApplication());
        $router->group(['middleware' => ['roles:admin']], function () use ($router) {
            $router->get('/_test/roles', 'RoleMiddlewareTest@adminTest');
        });

    }

    public function testRole()
    {
        $user = new \App\User(['roles' => ['admin']]);
        $this->be($user);
        try {
            $this->get('/_test/roles');
            $this->assertResponseStatus(201);
        } catch (HttpException $e) {
            $this->assertEquals(401, $e->getStatusCode());
            $this->assertEquals('Incorrect Role', $e->getMessage());
        }
    }

    public function adminTest(){
        return 'OK';
    }
}