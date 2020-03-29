<?php

class RootServerErrorTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testRootError()
    {
        $this->get('/');

        $this->assertEquals(
            '{"error":"Internal Server Error"}',
            $this->response->getContent()
        );
    }

    public function testUnauthorized()
    {
        $this->get('/api/users/me');

        $this->assertEquals(
            'Unauthorized.',
            $this->response->getContent()
        );
    }

    public function testFailedLogin()
    {
        $this->post('/login');

        $this->assertEquals(
            'Unauthorized.',
            $this->response->getContent()
        );

        $this->assertEquals(
            '401',
            $this->response->status()
        );
    }

    public function testRegisterWithoutKey()
    {
        $this->post('/register/email');

        $this->assertEquals(
            '{"error":"Missing Registration Key"}',
            $this->response->getContent()
        );

        $this->assertEquals(
            '401',
            $this->response->status()
        );
    }

    public function testBadMethod()
    {
        $this->delete('/config/app');

        $this->assertEquals(
            '{"error":"Internal Server Error"}',
            $this->response->getContent()
        );

        $this->assertEquals(
            '500',
            $this->response->status()
        );
    }

    public function testVersion()
    {
        $this->get('/api');

        $this->assertEquals(
            'Unauthorized.',
            $this->response->getContent()
        );

        $user = factory('App\User')->make();

        $this->actingAs($user)->get('/api');

        $this->assertEquals(
            'Lumen (6.3.3) (Laravel Components ^6.0)',
            $this->response->getContent()
        );

        $this->assertEquals(
            '200',
            $this->response->status()
        );
    }
}
