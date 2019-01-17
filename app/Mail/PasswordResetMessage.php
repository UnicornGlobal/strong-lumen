<?php

namespace App\Mail;

use App\User;
use App\ResetPasswordTrait;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use function preg_replace;

class PasswordResetMessage extends Mailable
{
    use Queueable, SerializesModels, ResetPasswordTrait;

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

    public function __construct(User $user, $token)
    {
        $this->user = $user;
        $this->token = $token;
        $url = env('PASSWORD_RESET_URL');
        $link = sprintf('%s/%s', $url, $token);
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
