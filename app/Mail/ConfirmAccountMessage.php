<?php

namespace App\Mail;

use App\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ConfirmAccountMessage extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $link;

    public function __construct(User $user)
    {
        $this->user = $user;
        $url = url('/');
        $this->link = sprintf('%s/confirm/%s', $url, $user->confirm_code);
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('mail.confirmaccount');
    }
}
