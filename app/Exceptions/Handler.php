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
     * @param \Exception $e
     *
     * @return void
     */
    public function report(Exception $e)
    {
        parent::report($e);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Exception               $e
     *
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $e)
    {
        $environment = env('APP_ENV');
        $required = env('DEBUG_TOKEN');
        $debugMode = env('APP_DEBUG');
        $token = $request->header('Debug-Token');
        if (!is_null($token) &&
            $token === $required &&
            $environment !== 'production' &&
            $debugMode === true) {
            return parent::render($request, $e);
        }

        if ($e instanceof ValidationException) {
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
