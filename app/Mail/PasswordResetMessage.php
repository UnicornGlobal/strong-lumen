<?php

namespace App\Mail;

use App\ResetPasswordTrait;
use App\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use function preg_replace;

class PasswordResetMessage extends Mailable
{
    use Queueable;
    use SerializesModels;
    use ResetPasswordTrait;

    /**
     * @var link
     */
    public $link;

    /**
     * @var user
     */
    public $user;

    /**
     * @var token
     */
    public $token;

    public function __construct(User $user, String $token)
    {
        $this->user = $user;
        $this->token = $token;
        # The URL for your UnicornGlobal/quick-dash projet
        $url = env('ADMIN_URL');
        $link = sprintf('%s/password-reset/%s/%s', $url, $user->email, $token);
        $this->subject = sprintf('%s Password Reset', env('APP_NAME'));
        $this->link = preg_replace('/([^:])(\/{2,})/', '$1/', $link);
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('mail.reset-password')
                    ->subject($this->getEmailSubject());
    }
}
