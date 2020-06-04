<?php

namespace App\Mail;

use App\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ConfirmAccountMessage extends Mailable
{
    use Queueable;
    use SerializesModels;

    public $user;
    public $app;
    public $button;
    public $link;

    public function __construct(User $user)
    {
        $this->user = $user;
        $url = env('API_URL');
        $this->app = env('APP_NAME');
        $this->subject = sprintf('Confirm Your %s Account', env('APP_NAME'));
        $this->button = 'CONFIRM';
        $this->link = sprintf('%s/confirm/%s', $url, $user->confirm_code);
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('mail.confirm-account');
    }
}
