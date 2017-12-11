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
        $user = User::where('_id', '4BFE1010-C11D-4739-8C24-99E1468F08F6')->first();

        if(is_null($user->role)){
            //dd($user->role);
            $user->roles()->syncWithoutDetaching(
                Role::where('name', 'admin')->first(),
                                [
                    'created_by' => $user->id,
                    'updated_by' => $user->id,
                ]
            );
        }


        $this->actingAs($user)->get('/test/roles', ['Debug-Token' => env('DEBUG_TOKEN')]);
        //$this->assertResponseStatus(201);
        dd($this->response);
    }

    public function adminTest(){
        return 'OK';
    }
}