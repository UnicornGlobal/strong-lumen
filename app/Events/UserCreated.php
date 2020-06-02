<?php

namespace App\Events;

use App\User;
use Illuminate\Support\Facades\Log;

class UserCreated extends Event
{
    public $user;

    public function __construct(User $user)
    {
        Log::info('User Created Event MMM<<<<<<<<<<<<<<<<<<<');
        $this->user = $user;
    }
}
