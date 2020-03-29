<?php

use App\Mail\PasswordResetMessage;
use Illuminate\Support\Facades\Mail;
use Laravel\Lumen\Testing\DatabaseTransactions;

class ResetPasswordTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * @return void
     */
    public function testResetPassword()
    {
        Mail::fake();

        $this->post('/reset', [
            'email' => 'developer@example.com',
        ], ['Debug-Token' => env('DEBUG_KEY')]);

        $this->assertEquals('{"success":true}', $this->response->getContent());

        $this->assertEquals('200', $this->response->status());

        // Try with invalid token
        $this->post(sprintf('/reset/%s', '123123123'), [
            'email'                 => 'developer@example.com',
            'password'              => '123abc^&*',
            'password_confirmation' => '123abc^&*',
            'token'                 => '123123123',
        ]);
        $this->assertEquals('{"success":false}', $this->response->getContent());

        Mail::assertSent(PasswordResetMessage::class, function ($mail) {
            $this->post(sprintf('/reset/%s', $mail->token), [
                'email'                 => 'developer@example.com',
                'password'              => '123abc^&*',
                'password_confirmation' => '123abc^&*',
                'token'                 => $mail->token,
            ]);
            $this->assertEquals('{"success":true}', $this->response->getContent());

            return $mail->hasTo('developer@example.com');
        });
    }

    /**
     * @return void
     */
    public function testBadResetPassword()
    {
        // Register with bad details
        $this->post('/reset', [
            'email' => 'dev@example.com',
        ], ['Debug-Token' => env('DEBUG_KEY')]);

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

        $this->assertStringContainsString('Too many consecutive attempts. Try again', $this->response->getContent());

        $this->assertEquals('429', $this->response->status());
    }
}
