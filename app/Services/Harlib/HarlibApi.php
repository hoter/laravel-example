<?php

namespace App\Services\Harlib;

use App\Exceptions\NotImplementedException;
use App\Models\Customer;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\ServerException;
use Log;

/**
 * HarlibApi class
 *
 * Central class to make all API calls to harlib
 *
 * Features:
 *
 * - Makes GET and POST calls
 * - Adds api username, password and token fields (when user logged in)
 * - Converts 200 responses into HarlibApiResponse instances
 * - Throws proper errors for 400, 404, 422 and 500
 */
class HarlibApi
{
    // -----------------------------------------------------------------------------------------------------------------
    // properties
    // -----------------------------------------------------------------------------------------------------------------

    /**
     * @var string $url
     */
    protected $url;

    /**
     * @var array $config
     */
    protected $config;

    /**
     * @var array $headers
     */
    protected $headers;

    protected $apiToken;

    // -----------------------------------------------------------------------------------------------------------------
    // instantiation
    // -----------------------------------------------------------------------------------------------------------------

    public function __construct($config)
    {
        $this->url    = $config['api_url'];
        $this->config = array_only($config, ['api_username', 'api_password']);
    }


    // -----------------------------------------------------------------------------------------------------------------
    // public methods
    // -----------------------------------------------------------------------------------------------------------------

    /**
     * Make a GET request
     *
     * @param   string $path
     * @param   array  $params
     *
     * @return  HarlibApiResponse
     */
    public function get($path, $params = [])
    {
        return $this->call('get', $path, $params);
    }

    /**
     * Make a POST request
     *
     * @param   string $path
     * @param   array  $params
     * @param   array  $files
     *
     * @return  HarlibApiResponse
     */
    public function post($path, $params = [], $files = [])
    {
        return $this->call('post', $path, $params, $files);
    }

    public function setApiToken($token){
        $this->apiToken = $token;
    }


    // -----------------------------------------------------------------------------------------------------------------
    // protected methods
    // -----------------------------------------------------------------------------------------------------------------

    protected function call($method, $path, $params = [], $files = null)
    {
        // -------------------------------------------------------------------------------------------------------------
        // prepare body
        // -------------------------------------------------------------------------------------------------------------

        // variables
        $url    = $this->url . $path;
        $auth   = $this->config;
        $method = strtolower($method);
        $client = new Client();

        // options field
        $field = array_get([
            'post' => 'form_params',
            'put'  => 'json',
        ], $method, 'query');

        // files
        if (!empty($files))
        {
            $field = 'multipart';
            if(!empty($params)){
                foreach ($params as $param_key => $param_value){
                    $params[] = [
                        'name' => $param_key,
                        'contents' => $param_value
                    ] ;
                    unset($params[$param_key]);
                }
            }
            foreach ($files as $file_name => $file){
                $params [] = [
                    'name' => $file_name,
                    'filename' => $file->getClientOriginalName(),
                    'Mime-Type'=> $file->getmimeType(),
                    'contents' => fopen( $file->getPathname(), 'r' ),
                ];
            }
//            throw new NotImplementedException('File upload has not been implemented yet!');
        }

        $apiToken = $this->apiToken ?? Customer::current()->api_token;
        // params
        $params = [
            'headers' => [
                'api-username' => $auth['api_username'],
                'api-password' => $auth['api_password'],
                'api-token'    => $apiToken,
            ],
            $field    => $params,
        ];

        // log
        Log::info(print_r($url, 1));
        Log::info(print_r($params, 1));


        // -------------------------------------------------------------------------------------------------------------
        // call API
        // -------------------------------------------------------------------------------------------------------------

        try
        {
            $response = $client->request($method, $url, $params);
            $output   = $response->getBody()->getContents();
            $status   = $response->getStatusCode();
            $this->headers  = $response->getHeaders();
        }
        catch (ConnectException $e)
        {
            throw new HarlibConnectionException("Could not make API connection to '$path'");
        }
        catch (ClientException $e)
        {
            $response = $e->getResponse();
            $output   = $response->getBody()->getContents();
            $status   = $response->getStatusCode();
        }


        // -------------------------------------------------------------------------------------------------------------
        // handle exceptions / return
        // -------------------------------------------------------------------------------------------------------------

        // handle specific statuses
        if ($status == 404)
        {
            throw new HarlibApiException("Path not found '$path'");
        }
        else if ($status >= 500)
        {
            throw new HarlibGeneralException($output, $status);
        }

        // data
        try
        {
            $data = json_decode($output, true);
        }
        catch (Exception $e)
        {
            throw new HarlibApiException($e->getMessage(), $output);
        }

        // validation
        if ($status == 422)
        {
            throw new HarlibValidationException(array_get($data, 'message'), $data);
        }
        // other 4xx
        else if ($status >= 400 && $status < 500)
        {
            $message = $data['message'] ??$data['error'];
            throw new HarlibApiException($message);
        }

        // success!
        return new HarlibApiResponse($data);
    }

    /*
     * Return headers
     * */
    public function getHeaders(){
        return $this->headers ? $this->headers : [];
    }
}
