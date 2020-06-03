<?php

namespace App\Mail;

use App\Investor;
use App\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AdminNewUserMessage extends Mailable
{
    use Queueable;
    use SerializesModels;

    public $app;
    public $user;
    public $link;

    public function __construct(User $user)
    {
        $this->user = $user;
        $this->app = env('APP_NAME');
        $this->subject = sprintf('New %s Account Created', env('APP_NAME'));
        $this->link = sprintf('%s/admin/users/%s', env('ADMIN_URL'), $user->_id);

    }

    public function build()
    {
        return $this->view('mail.admin-new-user');
    }
}
