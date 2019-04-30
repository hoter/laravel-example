<?php

namespace App\Http\Controllers\Api\v2\Dev;

use App\Exceptions\ApiException;
use App\Exceptions\NotImplementedException;
use App\Helpers\JsonPaginator;
use App\Helpers\QueryHelper;
use App\Http\Controllers\Api\v2\ApiController;
use Helpers\ArrayHelper;
use Illuminate\Http\Request;

class ResponseController extends ApiController
{

    // -----------------------------------------------------------------------------------------------------------------
    // Success
    // -----------------------------------------------------------------------------------------------------------------

    /**
     * Successful responses should now just be the returned data
     */
    public function format()
    {
        $data = [
            'foo' => 'Use any format that suits the request',
            'bar' => true,
            'baz' => [1, 2, 3],
        ];

        return $this->send($data);
    }

    /**
     * Send back a message and input data to ensure front end has something to work with
     */
    public function input()
    {
        return $this->sendInput();
    }

    /**
     * Always return 200 for empty result sets unless you specifically need to imply there is an error
     *
     * If a validation error causes empty results, you could throw a ValidationException
     *
     * Don't use a 404! That implies a missing resource, not an empty resource
     */
    public function results()
    {
        $length = \Request::get('length');
        $results = array_fill(0, $length, true);

        $data = [
            'message' => sprintf('There are %s results', count($results)),
            'results' => $results,
        ];

        return $this->send($data);
    }


    // -----------------------------------------------------------------------------------------------------------------
    // Utility
    // -----------------------------------------------------------------------------------------------------------------

    /**
     * Standardize paginated results using the new helper
     */
    public function paginate()
    {
        $data = array_map(function () {
            static $i;
            $i++;
            return "item $i";
        }, array_fill(0, 100, null));

        return $this->send(JsonPaginator::create($data));
    }

    /**
     * Return dummy data when API in flux
     */
    public function getDummyData()
    {
        $incompleteData = [1, 2, 3, 4];

        return $this->isDummy()
            ? $this->sendDummy('finance/offers')
            : $this->send($incompleteData);
    }


    // -----------------------------------------------------------------------------------------------------------------
    // Validation / Api
    // -----------------------------------------------------------------------------------------------------------------

    /**
     * Validate request data using rules and the controller's validate function
     *
     * Invalid requests throw a ValidationException and return a 422 JSON response with validation errors
     *
     * @param Request $request
     */
    public function validateRequest(Request $request)
    {
        $rules = [
            'foo' => 'required|numeric|min:0',
            'bar' => 'required',
        ];

        $this->validate($request, $rules);
    }

    /**
     * Validate any arbitrary data using rules and the controller's validateWith function
     *
     * Invalid requests throw a ValidationException and return a 422 JSON response with validation errors
     */
    public function validateData()
    {
        $data = [
            'yin' => '12345',
        ];

        $rules = [
            'yin'  => 'string|alpha',
            'yang' => 'number|between:0,10',
        ];

        $this->validateWith($rules, new Request($data));
    }

    /**
     * For API-specific feedback that isn't covered by any other error type, throw an ApiException
     *
     * Pass an optional status code to return JSON (4xx) or HTML (5xx) (defaults to 400)
     *
     * @throws ApiException
     */
    public function apiError()
    {
        $status = \Request::get('status');

        throw new ApiException('Parameter X not found', $status); // status is optional, and defaults to 400
    }

    public function notImplemented()
    {
        $reason = \Request::get('reason');
        throw $reason
            ? new NotImplementedException($reason)
            : new NotImplementedException();
    }

    // -----------------------------------------------------------------------------------------------------------------
    // System / Uncaught
    //
    // Returns:
    //
    // - full HTML stack trace in development
    // - JSON in production
    // -----------------------------------------------------------------------------------------------------------------

    /**
     * Normal errors will get caught
     */
    public function normalError()
    {
        echo 100 / 0;
    }

    /**
     * Let query or other errors show in the stack trace
     */
    public function queryError()
    {
        \DB::table('foo')->get(['bar']);
    }

    /**
     * Unexpected conditions will be visible
     */
    public function uncaughtError()
    {
        doSomething();
    }

}

function doSomething()
{
    throw new \Exception('Some arbitrary user error');
}
