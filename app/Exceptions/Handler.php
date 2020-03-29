<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Laravel\Lumen\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\HttpException;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        AuthorizationException::class,
        HttpException::class,
        ModelNotFoundException::class,
        ValidationException::class,
    ];

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param \Throwable $e
     *
     * @return void
     * @throws Exception
     */
    public function report(\Throwable $e)
    {
        parent::report($e);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Throwable $e
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Throwable
     */
    public function render($request, \Throwable $e)
    {
        $environment = env('APP_ENV');
        $required = env('DEBUG_KEY');
        $debugMode = env('APP_DEBUG');
        $token = $request->header('Debug-Token');
        if (!is_null($token) &&
            $token === $required &&
            $environment !== 'production' &&
            $debugMode === true) {
            return parent::render($request, $e);
        }

        $message = $e->getMessage();

        if (!$message) {
            $message = 'Internal Server Error';
        }

        $user = Auth::user();

        if (isset($user->_id)) {
            Log::error(sprintf('Exception Message: %s - User: %s', $message, $user->_id));
        } else {
            Log::error(sprintf('Exception Message: %s', $message));
        }

        return response()->json([
            'error' => $message,
        ], 500)
            ->header('X-Content-Type-Options', 'nosniff')
            ->header('X-Frame-Options', 'DENY')
            ->header('X-XSS-Protection', '1; mode=block')
            ->header('Strict-Transport-Security', 'max-age=7776000; includeSubDomains');
    }
}
