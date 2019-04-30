<?php

namespace App\Http\Controllers\Api\v2\Account;

use App\Http\Controllers\Api\v2\ApiController;
use App\Http\Requests\Conversion\PaymentRequest;
use App\Http\Requests\Conversion\SecurePaymentRequest;
use App\Services\Harlib\HarlibApi;
use Worldpay\WorldpayException;
use App\Models\Customer;

/**
 * Manages Worldpay payments
 */
class PaymentController extends ApiController
{
    /**
     * Makes a normal Worldpay payment
     *
     * @param PaymentRequest $request
     * @param int            $applicationId
     * @return array
     */
    public function makePayment(PaymentRequest $request, $applicationId)
    {
        // call worldpay
        try
        {
            $response = app('worldpay')->createOrder($request->all());
        }
        catch (WorldpayException $e)
        {
            return $this->getErrorData($e);
        }
        catch (\Exception $e)
        {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }

        // log payment with back end
        $result = $this->logPayment($applicationId, $response['orderCode'], $request->amount);

        return [
            'success'           => true,
            'worldpay_response' => $response,
            'harlib_response'   => $result,
        ];
    }

    /**
     * Begins a 3DS Worldpay payment
     *
     * A Worldpay 3D Secure payment works as follows:
     *
     *   1 - front end:     POST payment info to front office
     *   2 - front office:  POST all data to worldpay and get redirect url (this function)
     *   3 - front office:  send redirect url and order number back to front end (this function)
     *   4 - front end:     create temp HTML form with PaReq and TermUrl and submit it
     *   5 - card issuer:   page loads and payment is authenticated
     *   6 - card issuer:   POSTs to our supplied TermUrl (complete3dsPayment() below)
     *   7 - front office:  process request from card issuer - payment data in POST and forwarded application data in URL
     *   8 - front office:  final POST to Worldpay with PaRes response code
     *   9 - front office:  redirect to page to load SPA with GET string indicating successful payment
     *  10 - front end:     route to previous page and alert user
     *
     * @see https://developer.worldpay.com/jsonapi/docs/3d-secure
     *
     * @param PaymentRequest $request
     * @param int            $applicationId
     * @return array
     */
    public function startSecurePayment(PaymentRequest $request, $applicationId)
    {
        session_start();
        // call worldpay
        try
        {
            $params               = $request->all();

            $params['is3DSOrder'] = true;
            $response             = app('worldpay')->createOrder($params);

            $sessionId = $_SESSION['worldpay_sessionid'];
            \Redis::set($response['orderCode'], $sessionId);
            \Redis::set($response['orderCode'] . "-api-token", Customer::current()->api_token);
            \Redis::set($response['orderCode'] . "-http-accept", $_SERVER["HTTP_ACCEPT"]);

            /**
             * Add user token
            */
        }
        catch (WorldpayException $e)
        {
            return $this->getErrorData($e);
        }
        catch (\Exception $e)
        {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }

        // if card issuer supports 3D Secure orders, we'll have a redirect URL
        $redirectURL = array_get($response, 'redirectURL');

        // if 3D Secure, indicate to the client that action is needed by returning the 3ds node
        if ($redirectURL)
        {
            $data = [
                'orderCode'       => $response['orderCode'],
                'oneTime3DsToken' => $response['oneTime3DsToken'],
                'redirectURL'     => $redirectURL,
            ];
            return [
                'success'           => true,
                'worldpay_response' => $response,
                'data'              => $data,
            ];
        }

        // otherwise, just log and return payment
        $result = $this->logPayment($applicationId, $response['orderCode'], $request->amount);
        return [
            'success'           => true,
            'worldpay_response' => $response,
            'harlib_response'   => $result,
        ];
    }

    /**
     * Complete Worldpay 3DS payment
     *
     * This is the URL that the card issuer is instructed to call when the payment is authorised
     *
     * @param SecurePaymentRequest $request
     * @param                      $applicationId
     * @return array
     */
    public function completeSecurePayment(SecurePaymentRequest $request, $applicationId)
    {
        session_start();
        $sessionId = \Redis::get($request->orderCode);
        $httpAccept = \Redis::get($request->orderCode . "-http-accept");
        $_SESSION['worldpay_sessionid'] = $sessionId;
        $_SERVER["HTTP_ACCEPT"] = $httpAccept;

        // make payment
        try
        {
            $response = app('worldpay')->authorize3DSOrder($request->orderCode, $request->PaRes);
        }
        catch (WorldpayException $e)
        {
            return $this->getErrorData($e);
        }
        catch (\Exception $e)
        {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }

        // if we have a successful Worldpay response, log payment and reload site
        if (isset($response['paymentStatus']) && $response['paymentStatus'] === 'SUCCESS')
        {
            // log payment
            $result = $this->logPayment($applicationId, $request->orderCode, $request->amount);

            // redirect to the client URL
            // header('Location: ' . $request->clientUrl); // client URL may not redirect correctly in dev - might need to be tested on a live server

            return [
                'success' => true
            ];

        }
        else {
            return [
                'success' => false,
                'message' => 'There was a problem authorising the 3DS order',
            ];
        }

    }

    /**
     * Log Worldpay response with Harlib
     *
     * @param int    $applicationId The application id
     * @param string $reference     The payment reference
     * @param int    $amount        The amount paid in PENCE
     *
     * @return \App\Services\Harlib\HarlibApiResponse
     */
    protected function logPayment($applicationId, $reference, $amount)
    {
        $data = [
            'transaction_reference' => $reference,
            'application_id'        => $applicationId,
            'amount'                => (float) $amount / 100,
        ];
        $apiToken = \Redis::get($reference . "-api-token");
        $harlib = app(HarlibApi::class);
        $harlib->setApiToken($apiToken);
        return $harlib->post("payment/create", $data)->all();
    }

    /**
     * Return a human readable error format
     *
     * @param WorldpayException $e
     * @return array
     */
    protected function getErrorData($e)
    {
        return [
            'success'     => false,
            'code'        => $e->getCustomCode(),
            'status'      => $e->getHttpStatusCode(),
            'description' => $e->getDescription(),
            'message'     => $e->getMessage(),
            // 'stack'       => explode('#', $e->getTraceAsString()),
        ];
    }

}

