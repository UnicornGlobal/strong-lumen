<?php

class RoleMiddlewareTest extends TestCase
{
    use \Laravel\Lumen\Testing\DatabaseTransactions;

    public function setUp()
    {
        parent::setUp();

        \Route::middleware('roles:admin')->any('/_test/roles', function () {
            return 'OK';
        });
    }

    public function testRole()
    {
        try {
            $response = $this->get('/_test/roles');
        } catch (HttpException $e) {
            $this->assertEquals(401, $e->getStatusCode());
            $this->assertEquals('Incorrect Role', $e->getMessage());
        }
    }
}