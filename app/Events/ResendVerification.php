<?php

namespace App\Events;

use App\User;

class ResendVerification extends Event
{
    public $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }
}
