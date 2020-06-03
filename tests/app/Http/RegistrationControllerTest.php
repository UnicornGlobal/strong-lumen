<?php

use App\Events\UserCreated;
use App\Listeners\OnUserCreated;
use App\Mail\AdminNewUserMessage;
use App\Mail\ConfirmAccountMessage;
use App\User;
use Carbon\Carbon;
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
            'mobile'    => '+27822222222',
            'location'  => 'Nowhere',
            'agree'     => true,
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
    public function testRegisterEmailNumberFormats()
    {
        $variants = [
            '+27822222222',
            '+27 82 2222222',
            '+27 82 222 2222',
            '+1 888-222-1234',
            '+1-888-222-1234',
            '+1 888 222 1234',
            '+1 888 2221234',
            '+1 (888) 222-1234',
            '+1(888)222-1234',
            '+1(888)2221234',
            '+18882221234',
        ];

        foreach ($variants as $variant) {
            $this->post('/register/email', [
                'email'     => sprintf('test%s@example.com', md5($variant)),
                'username'  => sprintf('test%s@example.com', md5($variant)),
                'mobile'    => $variant,
                'password'  => 'password',
                'firstName' => 'First',
                'lastName'  => 'Last',
                'mobile'    => '+27822222222',
                'location'  => 'Nowhere',
                'agree'     => true,
            ], ['Registration-Access-Key' => env('REGISTRATION_ACCESS_KEY')]);

            $this->assertEquals('201', $this->response->status());
            $result = json_decode($this->response->getContent());

            $this->assertRegExp(
                '/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/',
                $result->_id
            );
        }
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
        ], ['Debug-Token' => env('DEBUG_TOKEN')]);

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

        $this->assertEquals('{"username":["The username has already been taken."],"firstName":["The first name field is required."],"lastName":["The last name field is required."],"email":["The email field is required."]}', $this->response->getContent());
        $this->assertEquals('422', $this->response->status());
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
            'mobile'    => '+27822222222',
            'location'  => 'Nowhere',
            'agree'     => true,
        ], ['Registration-Access-Key' => env('REGISTRATION_ACCESS_KEY')]);

        $this->assertEquals('{"username":["The username has already been taken."],"email":["The email has already been taken."]}', $this->response->getContent());
        $this->assertEquals('422', $this->response->status());
    }

    public function testRegConfirmEmail()
    {
        Mail::fake();

        // $this->withoutExceptionHandling();
        $this->expectsEvents('App\Events\UserCreated');

        $this->post('/register/email', [
            'username'  => 'user123',
            'password'  => 'password',
            'firstName' => 'First',
            'lastName'  => 'Last',
            'email'     => 'asd@example.com',
            'mobile'    => '+27822222222',
            'location'  => 'Nowhere',
            'agree'     => true,
        ], ['Registration-Access-Key' => env('REGISTRATION_ACCESS_KEY')]);
    }

    public function testRegConfirmEmailEvent()
    {
        Mail::fake();

        $listener = new OnUserCreated();
        $listener->handle(new UserCreated($this->user));

        Mail::assertQueued(ConfirmAccountMessage::class, function ($mail) {
            $this->assertStringContainsString(sprintf('%s/confirm/', env('API_URL')), $mail->link);

            $this->assertRegExp(
                '/.*\/[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/',
                $mail->link
            );

            $render = $mail->build();
            $this->assertEquals(sprintf('Confirm Your %s Account', env('APP_NAME')), $render->subject);
            $this->assertEquals($this->user->email, $render->to[0]['address']);

            $this->get($mail->link);

            $this->assertResponseStatus(302);
            $this->assertStringContainsString('Redirecting to <a href="', $this->response->getContent());
            $this->assertStringContainsString('confirmed', $this->response->getContent());
            $this->assertStringContainsString('refresh', $this->response->getContent());

            return true;
        });

        Mail::assertQueued(AdminNewUserMessage::class, function ($mail) {
            $this->assertStringContainsString(sprintf('%s/admin/users/%s', env('ADMIN_URL'), $this->user->_id), $mail->link);

            $this->assertRegExp(
                '/.*\/[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/',
                $mail->link
            );

            $render = $mail->build();
            $this->assertEquals(env('ADMIN_NOTIFICATIONS_MAIL'), $render->to[0]['address']);
            $this->assertEquals(sprintf('New %s Account Created', env('APP_NAME')), $render->subject);

            return true;
        });
    }

    public function testBadConfirmCode()
    {
        $this->get('confirm/112358');
        $this->assertResponseStatus(302);
        $this->assertStringContainsString('Redirecting to <a href="', $this->response->getContent());
        $this->assertStringContainsString('invalidconfirmation', $this->response->getContent());
    }

    public function testUsedConfirmCode()
    {
        Mail::fake();

        $this->post('/register/email', [
            'username'  => 'user12345',
            'password'  => 'password1',
            'firstName' => 'First',
            'lastName'  => 'Last',
            'email'     => 'asdfg@example.com',
            'mobile'    => '+27822222222',
            'location'  => 'Nowhere',
            'agree'     => true,
        ], ['Registration-Access-Key' => env('REGISTRATION_ACCESS_KEY')]);

        // Get the users confirm code
        $result = json_decode($this->response->getContent());
        $user = User::loadFromUuid($result->_id);

        // Confirm account
        $user->confirmed_at = Carbon::now();
        $user->save();

        // Try use code again
        $url = sprintf('confirm/%s', $user->confirm_code);
        $this->get($url);
        $this->assertResponseStatus(302);
        $this->assertStringContainsString('login?invalidconfirmation=true', $this->response->getContent());
    }

    public function testLoginToken()
    {
        Mail::fake();

        $this->post('/register/email', [
            'username'  => 'user12345',
            'password'  => 'password1',
            'firstName' => 'First',
            'lastName'  => 'Last',
            'email'     => 'asdfg@example.com',
            'mobile'    => '+27822222222',
            'location'  => 'Nowhere',
            'agree'     => true,
        ], ['Registration-Access-Key' => env('REGISTRATION_ACCESS_KEY')]);

        // Get the users confirm code
        $result = json_decode($this->response->getContent());
        $user = User::loadFromUuid($result->_id);
        $token = encrypt('secret');
        $user->otp = 'secret';
        $user->otp_created_at = Carbon::now();
        $user->save();
        $user = $user->fresh();

        $this->post('/login/token', [
            'token' => $token,
        ]);

        $this->assertResponseStatus(200);
        $this->seeJsonStructure(['_id', 'jwt', 'token_type', 'expires', 'user']);

        // Try again
        $this->post('/login/token', [
            'token' => $token,
        ]);
        $this->assertResponseStatus(401);
    }

    public function testLoginOldToken()
    {
        Mail::fake();

        $this->post('/register/email', [
            'username'  => 'user12345',
            'password'  => 'password1',
            'firstName' => 'First',
            'lastName'  => 'Last',
            'email'     => 'asdfg@example.com',
            'mobile'    => '+27822222222',
            'location'  => 'Nowhere',
            'agree'     => true,
        ], ['Registration-Access-Key' => env('REGISTRATION_ACCESS_KEY')]);

        // Get the users confirm code
        $result = json_decode($this->response->getContent());
        $user = User::loadFromUuid($result->_id);
        $user->otp_created_at = Carbon::now()->subDays(2);
        $token = encrypt('stale');
        $user->otp = 'stale';
        $user->save();
        $user = $user->fresh();

        $this->post('/login/token', [
            'token' => $token,
        ]);
        $this->assertResponseStatus(401);
    }

    public function testLoginTokenNoDate()
    {
        Mail::fake();

        $this->post('/register/email', [
            'username'  => 'user12345',
            'password'  => 'password1',
            'firstName' => 'First',
            'lastName'  => 'Last',
            'email'     => 'asdfg@example.com',
            'mobile'    => '+27822222222',
            'location'  => 'Nowhere',
            'agree'     => true,
        ], ['Registration-Access-Key' => env('REGISTRATION_ACCESS_KEY')]);

        // Get the users confirm code
        $result = json_decode($this->response->getContent());
        $user = User::loadFromUuid($result->_id);

        $token = encrypt('awesome');
        $user->otp = 'awesome';
        // intentionally leave off created at
        $user->save();

        $this->post('/login/token', [
            'token' => $token,
        ]);

        $this->assertResponseStatus(401);
    }
}
