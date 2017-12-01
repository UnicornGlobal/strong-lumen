<?php

namespace App\Http\Controllers;

use App\ResetPasswordTrait;
use Laravel\Lumen\Routing\Controller as BaseController;

class ResetController extends BaseController
{
    use ResetPasswordTrait;

    public function __construct()
    {
        $this->broker = 'users';
    }
}
