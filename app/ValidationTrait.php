<?php

namespace App;

use App\Http\Controllers\ConfigController;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

trait ValidationTrait
{
    private function isValidUserID($uuid)
    {
        $this->checkValid($uuid, User::class);
    }

    private function checkValid($uuid, $class)
    {
        $this->checkEmpty($uuid, $class);
        $this->checkUuid($uuid, $class);
        $this->checkExists($uuid, $class);
    }

    private function checkEmpty($uuid, $name)
    {
        if (empty($uuid || !isset($uuid) || is_null($uuid))) {
            $this->throwExceptionMessage('Empty', $name);
        }
    }

    private function checkUuid($uuid, $name)
    {
        if (!preg_match('/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/', $uuid)) {
            $this->throwExceptionMessage('Invalid', $name);
        }
    }

    private function checkExists($uuid, $class)
    {
        if (!$class::where('_id', $uuid)->first()) {
            $this->throwExceptionMessage('Invalid', $class);
        }
    }

    private function throwExceptionMessage($state, $className)
    {
        $name = substr($className, strrpos($className, '\\') + 1);
        throw new \Exception(sprintf('%s %s ID', $state, $name));
    }
}
