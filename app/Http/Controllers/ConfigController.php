<?php

namespace App\Http\Controllers;

class ConfigController extends Controller
{
    /**
     * Example for retrieving config info with a key.
     */
    public function getAppConfig()
    {
        return response()->json([
            'app_id'  => env('APP_ID'),
            'api_url' => env('API_URL'),
            'name'    => env('APP_NAME'),
            'version' => env('APP_VERSION'),
        ]);
    }
}
