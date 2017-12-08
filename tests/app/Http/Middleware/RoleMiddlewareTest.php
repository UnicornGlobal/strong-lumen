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
        $user = new \App\User(['roles' => ['user']]);
        $this->be($user);

        $this->get('/_test/roles');
        //$this->assertResponseStatus(201);
        dd($this->response);
    }

    public function adminTest(){
        return 'OK';
    }
}