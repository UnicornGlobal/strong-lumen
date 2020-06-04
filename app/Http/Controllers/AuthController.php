<?php

namespace App\Http\Controllers;

use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Laravel\Lumen\Routing\Controller as BaseController;

class AuthController extends BaseController
{
    /**
     * post: /login.
     *
     * @return string
     */
    public function login(Request $req)
    {
        $credentials = $req->only('username', 'password');

        if ($token = $this->guard()->attempt($credentials)) {
            return $this->respondWithToken($token);
        }

        return response('Unauthorized.', 401);
    }

    /**
     * post: /login/token.
     *
     * @return string
     */
    public function loginToken(Request $req)
    {
        $credentials = $req->only('token');
        $decrypted = decrypt($credentials['token']);

        $user = User::where('otp', $decrypted)->first();

        if (!$user || !$user->otp_created_at) {
            return response('Unauthorized.', 401);
        }

        if ($user->otp_created_at < Carbon::now()->subSeconds(env('LOGIN_OTP_TIMEOUT', 60))) {
            return response('Unauthorized.', 401);
        }

        if ($token = Auth::login($user)) {
            $user->otp = null;
            $user->otp_created_at = null;
            $user->save();

            Cache::tags([
                'users',
            ])->flush();

            return $this->respondWithToken($token);
        }

        return response('Unauthorized.', 401);
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        $this->guard()->logout(true);

        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        // Refreshed token oken comes as an auth header
        return response()->json();
    }

    /**
     * Get the token array structure.
     *
     * @param string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token)
    {
        return response()->json([
            '_id'        => Auth::user()->_id,
            'jwt'        => $token,
            'token_type' => 'bearer',
            'expires'    => $this->guard()->factory()->getTTL() * 60,
            'user'       => $this->guard()->user(),
        ])->header('Authorization', sprintf('Bearer %s', $token));
    }

    /**
     * Get the guard to be used during authentication.
     *
     * @return \Illuminate\Contracts\Auth\Guard
     */
    public function guard()
    {
        return Auth::guard();
    }
}
