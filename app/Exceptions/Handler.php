<?php

namespace App\Exceptions;

use App\Helpers\LogHelper;
use App\Services\Harlib\HarlibApiException;
use App\Services\Harlib\HarlibGeneralException;
use App\Services\Harlib\HarlibValidationException;
use Exception;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Tymon\JWTAuth\Exceptions\TokenBlacklistedException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        \Illuminate\Auth\AuthenticationException::class,
        \Illuminate\Auth\Access\AuthorizationException::class,
        \Symfony\Component\HttpKernel\Exception\HttpException::class,
        \Illuminate\Database\Eloquent\ModelNotFoundException::class,
        \Illuminate\Session\TokenMismatchException::class,
        \Illuminate\Validation\ValidationException::class,
    ];

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Exception $exception
     * @return void
     */
    public function report(Exception $exception)
    {
        if (app()->bound('sentry') && $this->shouldReport($exception)) {
            app('sentry')->captureException($exception);
        }

        // for Harlib exceptions, which may have data, pass a copy with encoded JSON so as not break Monolog
        if ($exception instanceof HarlibApiException && !!$exception->data && !is_string($exception->data)) {
            $exception = new HarlibApiException($exception->getMessage(), json_encode($exception->data));
        }
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Exception               $e
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $e)
    {
//        dd($exception instanceof ValidationException);
        // API V2
        if ($request->is('api/*')) {

            // DATA ----------------------------------------------------------------------------------------------------

            $status = 500;
            $data = [
                'type' => 'server',
                'message' => $e->getMessage(),
            ];

            // RESPONSE ------------------------------------------------------------------------------------------------

            // 404; return route info
            if ($e instanceof NotFoundHttpException) {
                $data['message'] = "Endpoint '{$request->path()}' not found";
                $data['type'] = 'route';
                $status = 404;
            }

            // validation error; return error messages
            else if ($e instanceof ValidationException) {
                $data['errors'] = $e->validator->errors()->all();
                $data['fields'] = $e->validator->errors();
                $data['type'] = 'validation';
                $status = 422;
            }

            // Harlib API validation exception; return Harlib response
            else if ($e instanceof HarlibValidationException) {
                $data['errors'] = $e->data['errors'];
                $data['fields'] = $e->data['fields'];
                $data['type'] = 'validation';
                $status = 422;
            }

            // Harlib general exception; return Harlib html response
            else if ($e instanceof HarlibGeneralException) {
                return $e->html;
            }

            // Harlib API exception; return Harlib response
            else if ($e instanceof HarlibApiException) {
                $data['response'] = $e->data;
                $data['type'] = 'api';
                $status = 400;
            }

            else if ($e instanceof AuthenticationException) {
                $data['type'] = 'auth';
                $status = 401;
            }

            // FIXME this is supposed to catch JWT exxceptions, but isn't. @see CUS-94
            else if ($e instanceof UnauthorizedHttpException) {
                switch (get_class($e->getPrevious())) {
                    case TokenExpiredException::class:
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Token has expired'
                        ], $e->getStatusCode());
                    case TokenInvalidException::class:
                    case TokenBlacklistedException::class:
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Token is invalid'
                        ], $e->getStatusCode());
                    default:
                        break;
                }
            }

            // anything else
            else {

                // api error; return error message
                if ($e instanceof ApiException) {
                    $data['type'] = 'api';
                    $status = $e->getCode();
                }

                // log api or 500 errors
                $this->logError($request, $e);
            }

            // RETURN --------------------------------------------------------------------------------------------------

            // 500
            if ($status >= 500) {

                // local; return HTML stack
                if (getenv('APP_ENV') === 'local') {
                    return parent::render($request, $e);
                }

                // production; uncomment next line to cloak the error type
                // $data['message'] = 'Internal server error';
            }

            // everything else; json error
            $data['status'] = $status;
            return response()->json($data, $status);
        }

        // html stack trace
        return parent::render($request, $e);
    }

    protected function logError($request, Exception $exception)
    {
        // TODO update correct logging signature
        try {
            $path = $request->path();
            $message = LogHelper::getExceptionStr($exception);
            //CommonHelper::saveLog($path . ' ' . $message);
        } catch (Exception $e) {
            // unable to log
        }
    }

    /**
     * Convert an authentication exception into an unauthenticated response.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Illuminate\Auth\AuthenticationException $exception
     * @return \Illuminate\Http\Response
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        if ($request->expectsJson()) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        return redirect()->guest(route('login'));
    }

    protected function getJSONTrace(Exception $exception, $data = [])
    {
        try {
            $data['file'] = $exception->getFile();
            $data['line'] = $exception->getLine();
            $data['stack'] = array_map(function ($item) {
                return @ (str_replace(base_path() . DIRECTORY_SEPARATOR, '', $item['file']) . '@' . $item['line']);
            }, $exception->getTrace());
        } catch (Exception $e) {
            // unable to generate error
        }
        return $data;
    }
}
