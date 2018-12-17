<?php

use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;

class ResetPasswordTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * @return void
     */
    public function testResetPassword()
    {
        // Register with bad details
        $this->post('/reset', [
            'email' => 'developer@example.com',
        ], [ 'Debug-Token' => env('DEBUG_KEY')]);

        $this->assertEquals('{"success":true}', $this->response->getContent());

        $this->assertEquals('200', $this->response->status());
    }

    /**
     * @return void
     */
    public function testBadResetPassword()
    {
        // Register with bad details
        $this->post('/reset', [
            'email' => 'dev@example.com',
        ], [ 'Debug-Token' => env('DEBUG_KEY')]);

        $this->assertEquals('{"success":false}', $this->response->getContent());

        $this->assertEquals('200', $this->response->status());
    }


    /**
     * @return void
     */
    public function testThrottleResetPassword()
    {
        putenv('THROTTLE_TEST=true');
        // Register with bad details
        $this->post('/reset', ['email' => 'developer@example.com']);
        $this->post('/reset', ['email' => 'developer@example.com']);
        $this->post('/reset', ['email' => 'developer@example.com']);
        $this->post('/reset', ['email' => 'developer@example.com']);
        $this->post('/reset', ['email' => 'developer@example.com']);
        $this->post('/reset', ['email' => 'developer@example.com']);
        $this->post('/reset', ['email' => 'developer@example.com']);
        $this->post('/reset', ['email' => 'developer@example.com']);
        $this->post('/reset', ['email' => 'developer@example.com']);
        $this->post('/reset', ['email' => 'developer@example.com']);
        $this->post('/reset', ['email' => 'developer@example.com']);
        $this->post('/reset', ['email' => 'developer@example.com']);
        $this->post('/reset', ['email' => 'developer@example.com']);
        $this->post('/reset', ['email' => 'developer@example.com']);

        $this->assertContains('Too many consecutive attempts. Try again', $this->response->getContent());

        $this->assertEquals('429', $this->response->status());
    }
}
