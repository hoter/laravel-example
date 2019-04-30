<?php

namespace App\Http\Controllers\Api\v2;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * ApiController class
 *
 * Base methods for sending API to client
 */
class ApiController extends Controller
{
    /**
     * Send a standard JSON response
     *
     * @param null  $data
     * @param array $headers
     *
     * @return JsonResponse
     */
    protected function send($data = null, $headers = [])
    {
        return response()->json($data)->withHeaders($headers);
    }

    /**
     * Send a dummy JSON response, including route and original input
     *
     * @param string $message
     *
     * @return JsonResponse
     */
    protected function sendInput($message = 'This is your input data')
    {
        return response()->json([
            'message' => $message,
            'route'   => \Request::path(),
            'data'    => \Request::input(),
        ]);
    }

    /**
     * Send an error response
     *
     * @depreciated Throw an exception instead
     *
     * @param     $data
     * @param int $status
     *
     * @return JsonResponse
     */
    protected function sendError($data, $status = 400)
    {
        return response()->json($data, $status);
    }

    /**
     * Send JSON file data
     *
     * @param string $file
     *
     * @return JsonResponse
     */
    protected function sendDummy($file)
    {
        return response()->json($this->getDummy($file));
    }

    /**
     * Get dummy data
     *
     * Files are stored in /resources/api/
     *
     * @param string $file The file path to the dummy data file, i.e. finance/check-deposit (.json extension optional)
     *
     * @return mixed
     */
    protected function getDummy($file)
    {
        $path = str_replace('.json', '', $file);
        $data = json_decode(file_get_contents(resource_path("api/$path.json")));
        return $data;
    }

    /**
     * Helper function to check if request is asking for dummy data
     *
     * @return bool
     */
    protected function isDummy()
    {
        return env('APP_ENV') != 'production' && !!\Request::get('dummy', 0);
    }

    // -----------------------------------------------------------------------------------------------------------------
    // depreciated functions
    // -----------------------------------------------------------------------------------------------------------------

    /**
     * Send a standard JSON response with message and data
     *
     * @deprecated Use `send()` instead
     *
     * @param string $message
     * @param null   $data
     *
     * @return JsonResponse
     */
    protected function sendData($message = '', $data = null)
    {
        return response()->json([
            'message' => $message,
            'data'    => $data,
        ]);
    }

}
