<?php

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Console\Application as ConsoleApplication;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

trait InteractsWithExceptionHandling
{
    /**
     * The original exception handler.
     *
     * @var ExceptionHandler|null
     */
    protected $originalExceptionHandler;

    /**
     * Restore exception handling.
     *
     * @return $this
     */
    protected function withExceptionHandling()
    {
        if ($this->originalExceptionHandler) {
            $this->app->instance(ExceptionHandler::class, $this->originalExceptionHandler);
        }

        return $this;
    }

    /**
     * Only handle the given exceptions via the exception handler.
     *
     * @param array $exceptions
     *
     * @return $this
     */
    protected function handleExceptions(array $exceptions)
    {
        return $this->withoutExceptionHandling($exceptions);
    }

    /**
     * Only handle validation exceptions via the exception handler.
     *
     * @return $this
     */
    protected function handleValidationExceptions()
    {
        return $this->handleExceptions([ValidationException::class]);
    }

    /**
     * Disable exception handling for the test.
     *
     * @param array $except
     *
     * @return $this
     */
    protected function withoutExceptionHandling(array $except = [])
    {
        if ($this->originalExceptionHandler == null) {
            $this->originalExceptionHandler = app(ExceptionHandler::class);
        }

        $this->app->instance(ExceptionHandler::class, new class($this->originalExceptionHandler, $except) implements ExceptionHandler {
            protected $except;
            protected $originalHandler;

            /**
             * Create a new class instance.
             *
             * @param ExceptionHandler $originalHandler
             * @param array            $except
             *
             * @return void
             */
            public function __construct($originalHandler, $except = [])
            {
                $this->except = $except;
                $this->originalHandler = $originalHandler;
            }

            /**
             * Report the given exception.
             *
             * @param Throwable $e
             *
             * @return void
             */
            public function report(Throwable $e)
            {
                // no-op
            }

            /**
             * Determine if the exception should be reported.
             *
             * @param Throwable $e
             *
             * @return bool
             */
            public function shouldReport(Throwable $e)
            {
                return false;
            }

            /**
             * Render the given exception.
             *
             * @param Request   $request
             * @param Throwable $e
             *
             *@throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException|Exception
             *
             * @return mixed
             */
            public function render($request, Throwable $e)
            {
                if ($e instanceof NotFoundHttpException) {
                    throw new NotFoundHttpException(
                        "{$request->method()} {$request->url()}",
                        null,
                        $e->getCode()
                    );
                }

                foreach ($this->except as $class) {
                    if ($e instanceof $class) {
                        return $this->originalHandler->render($request, $e);
                    }
                }

                throw $e;
            }

            /**
             * Render the exception for the console.
             *
             * @param OutputInterface $output
             * @param Throwable       $e
             *
             * @return void
             */
            public function renderForConsole($output, Throwable $e)
            {
                (new ConsoleApplication())->renderException($e, $output);
            }
        });

        return $this;
    }
}
