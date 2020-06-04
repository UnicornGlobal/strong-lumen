<?php

namespace App\Listeners;

use App\Events\ResendVerification;
use App\Mail\ConfirmAccountMessage;
use Illuminate\Support\Facades\Mail;

class OnResendVerification
{
    public function handle(ResendVerification $event)
    {
        Mail::to($event->user)
            ->queue(new ConfirmAccountMessage($event->user));
    }
}
