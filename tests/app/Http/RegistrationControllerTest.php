<?php

use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;

class RegistrationControllerTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * @return void
     */
    public function testMissingRegisterKey()
    {
        // Register with bad details
        $this->post('/register/email', [
            'username' => 'user',
            'password' => 'password',
        ], [ 'Debug-Token' => env('DEBUG_KEY')]);

        $this->assertEquals('{"error":"Missing Registration Key"}', $this->response->getContent());

        $this->assertEquals('401', $this->response->status());
    }

    /**
     * @return void
     */
    public function testMissingRegisterDetails()
    {
        // Register with bad details
        $this->post('/register/email', [
            'username' => 'user',
            'password' => 'password',
        ], [ 'Registration-Access-Key' => env('REGISTRATION_ACCESS_KEY')]);

        $this->assertEquals('{"error":"The given data was invalid."}', $this->response->getContent());

        $this->assertEquals('500', $this->response->status());
    }

    /**
     * @return void
     */
    public function testMissingRegisterExistingDetails()
    {
        // Register with bad details
        $this->post('/register/email', [
            'username' => 'user',
            'password' => 'password',
            'firstName' => 'First',
            'lastName' => 'Last',
            'email' => 'developer@example.com',
        ], [ 'Registration-Access-Key' => env('REGISTRATION_ACCESS_KEY')]);

        $this->assertEquals('{"error":"The given data was invalid."}', $this->response->getContent());

        $this->assertEquals('500', $this->response->status());
    }

    /**
     * @return void
     */
    public function testMissingRegisterNewDetails()
    {
        // Register with bad details
        $this->post('/register/email', [
            'username' => 'username',
            'password' => 'password',
            'lastName' => 'User',
            'email' => 'developer2@example.com',
        ], [ 'Registration-Access-Key' => env('REGISTRATION_ACCESS_KEY')]);

        $result = json_decode($this->response->getContent());

        // Should have 1 element
        $this->assertEquals(1, count((array)$result));
        $this->assertEquals('The given data was invalid.', $result->error);
        $this->assertEquals('500', $this->response->status());
    }
}
