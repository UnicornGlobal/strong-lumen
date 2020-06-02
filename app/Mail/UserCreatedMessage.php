<?php

namespace App\Mail;

use App\Investor;
use App\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class UserCreatedMessage extends Mailable
{
    use Queueable;
    use SerializesModels;

    public $user;

    public function __construct(User $user)
    {
        $this->user = $user;
        $this->app = env('APP_NAME');
        $this->subject = sprintf('%s User Created', env('APP_NAME'));
    }

    public function build()
    {
        return $this->view('mail.user-created');
    }
}
