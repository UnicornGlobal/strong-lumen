<?php

use App\Role;
use App\User;
use Webpatser\Uuid\Uuid;

class RoleMiddlewareTest extends TestCase
{
    use \Laravel\Lumen\Testing\DatabaseTransactions;
    //protected $router;
    public function setUp()
    {

        parent::setUp();
        $router = new \Laravel\Lumen\Routing\Router($this->createApplication());;

        $router->group(['middleware' => ['roles:admin']], function () use ($router) {
            $router->get('/test/roles', function () {
                return 'OK';
            });
        });
        $this->app->router = $router;
    }

    public function testRole()
    {
        $user = factory('App\User')->make();



        $user->roles()->attach('admin');


        //dd($this->router);
        $this->actingAs($user)->get('/test/roles', ['Debug-Token' => env('DEBUG_TOKEN')]);
        //$this->assertResponseStatus(201);
        dd($this->response);
    }

    public function adminTest(){
        return 'OK';
    }
}