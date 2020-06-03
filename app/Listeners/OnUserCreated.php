<?php

namespace App\Listeners;

use App\Events\UserCreated;
use App\Mail\ConfirmAccountMessage;
use App\Mail\AdminNewUserMessage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;

class OnUserCreated
{
    protected $broker = 'users';

    public function handle(UserCreated $event)
    {
        // Welcome the user to the application
        Mail::to($event->user)
            ->queue(new ConfirmAccountMessage($event->user));

        // Tell the admin user about the new signup
        Mail::to(env('ADMIN_NOTIFICATIONS_MAIL'))
            ->queue(new AdminNewUserMessage($event->user));

        Password::broker($this->broker)
            ->sendResetLink([
                'email' => $event->user->email
            ]);
    }
}
