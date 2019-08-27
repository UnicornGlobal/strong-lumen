<?php

use App\Mail\ConfirmAccountMessage;
use Illuminate\Support\Facades\Mail;
use Laravel\Lumen\Testing\DatabaseTransactions;

class RegistrationControllerTest extends TestCase
{
    use DatabaseTransactions;

    public static $code;

    /**
     * @return void
     */
    public function testRegisterEmail()
    {
        $this->post('/register/email', [
            'username'  => 'usertest@example.com',
            'password'  => 'password',
            'firstName' => 'First',
            'lastName'  => 'Last',
            'email'     => 'usertest@example.com',
        ], ['Registration-Access-Key' => env('REGISTRATION_ACCESS_KEY')]);

        $this->assertEquals('201', $this->response->status());
        $result = json_decode($this->response->getContent());

        $this->assertRegExp(
            '/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/',
            $result->_id
        );
    }

    /**
     * @return void
     */
    public function testMissingRegisterKey()
    {
        // Register with bad details
        $this->post('/register/email', [
            'username' => 'user',
            'password' => 'password',
        ], ['Debug-Token' => env('DEBUG_KEY')]);

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
        ], ['Registration-Access-Key' => env('REGISTRATION_ACCESS_KEY')]);

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
            'username'  => 'user',
            'password'  => 'password',
            'firstName' => 'First',
            'lastName'  => 'Last',
            'email'     => 'developer@example.com',
        ], ['Registration-Access-Key' => env('REGISTRATION_ACCESS_KEY')]);

        $this->assertEquals('{"error":"The given data was invalid."}', $this->response->getContent());

        $this->assertEquals('500', $this->response->status());
    }

    public function testRegConfirmEmail()
    {
        Mail::fake();

        $this->post('/register/email', [
            'username'  => 'user123',
            'password'  => 'password',
            'firstName' => 'First',
            'lastName'  => 'Last',
            'email'     => 'asd@example.com',
        ], ['Registration-Access-Key' => env('REGISTRATION_ACCESS_KEY')]);

        Mail::assertSent(ConfirmAccountMessage::class, function ($mail) {
            $this->get($mail->link);
            $this->assertResponseStatus(200);
            $this->assertEquals('{"result":"OK"}', $this->response->getContent());

            return true;
        });
    }

    public function testGoodConfirmCode()
    {
        Mail::fake();

        $this->post('/register/email', [
            'username' => 'user12345',
            'password' => 'password1',
            'firstName' => 'First',
            'lastName' => 'Last',
            'email' => 'asdfg@example.com',
        ], ['Registration-Access-Key' => env('REGISTRATION_ACCESS_KEY')]);

        Mail::assertSent(ConfirmAccountMessage::class, function ($mail) {
            $this->get($mail->link);
            $this->assertResponseStatus(200);
            $this->assertEquals('{"result":"OK"}', $this->response->getContent());
            self::$code = $mail->user->confirm_code;
            return true;
        });

        $url = sprintf('confirm/%s', self::$code);
        $this->get($url);
        $this->assertResponseStatus(200);
        $this->assertEquals('{"result":"OK"}', $this->response->getContent());
    }

    public function testBadConfirmCode()
    {
        $this->get('confirm/112358');
        $this->assertResponseStatus(500);
        $this->assertEquals('{"error":"There was a problem with the code."}', $this->response->getContent());
    }
}
