<?php

namespace App\Http\Controllers\Api\v2\Auth;

use App\Http\Controllers\Api\v2\ApiController;
use App\Mail\PasswordResetLinkEmail;
use App\Models\Customer;
use App\Services\Api\HarlibService;
use App\Services\Harlib\HarlibConnectionException;
use Exception;
use GuzzleHttp\Exception\ConnectException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class AuthController extends ApiController
{
    public function login(Request $request)
    {
        // validation and input
        $this->validate($request, [
            'email'    => 'required|email',
            'password' => 'required',
        ]);
        $credentials = $request->only('email', 'password');

        $customer = Customer::where('email',$credentials['email'])->first();

        if(is_null($customer)){
            throw new AuthenticationException('Either your email address or your password is incorrect. Please try again.');
        }

        if($customer->is_blocked) {
            throw new AuthenticationException("The account has been blocked. Please contact customer support.");
        }

        // try JWT login
        try
        {
            $loginAttemptKey = "customer_{$customer->id}_login_attempt";
            // attempt to verify the credentials and create a token for the user
            if (!$token = JWTAuth::attempt($credentials)) {

                if(Cache::has($loginAttemptKey)) {
                    $newValue = Cache::increment($loginAttemptKey);
                    if($newValue >= config('auth.account_blocked.max_attempt')) {
                        $customer->is_blocked = 1;
                        $customer->save();
                        Cache::forget($loginAttemptKey);
                    }
                } else {
                    Cache::put($loginAttemptKey , 1, config('auth.account_blocked.attempt_refresh_after'));
                }

                throw new AuthenticationException('Either your email address or your password is incorrect. Please try again.');
            }

            Cache::forget($loginAttemptKey);
        } catch (JWTException $e)
        {
            // something went wrong whilst attempting to encode the token
            throw new Exception('Could not create JWT token');
        }

        // login user in harlib
        $message = null;
        try {
            app(HarlibService::class)->loginUser($credentials);
        }
        // ignore connection exceptions in dev modes
        catch (HarlibConnectionException $e)
        {
            if(env('APP_ENV') != 'local')
            {
                throw $e;
            }
            $message = 'Logged in, but not to Harlib';
        }

        // return
        $headers = [
            // FIXME can't seem to get axios to see this header right now, think it
            'Authorization' => "Bearer $token"
        ];
        return $this->send(Customer::current()->index(compact('token', 'message')), $headers);
    }

    public function logout()
    {
        // destroy jwt token
        // log out from harlib
        // logout locally
        Auth::logout();
        return $this->send([]);
    }

    public function refresh()
    {
        return JWTAuth::refresh(JWTAuth::getToken());
    }


    /**
     * Send the password reset link to customer email address is email exists in our our database
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendPasswordResetLink(Request $request)
    {
        $this->validate($request, ['email' => 'required|email']);
        $customer = Customer::where('email', $request->email)->first();

        if($customer) {
            if($customer)
            {
                $customer->password_reset_token = md5($customer->id . time());
                $customer->save();
            }

            try{
                Mail::to($customer->email)->send(new PasswordResetLinkEmail($customer));
            }
            catch (Exception $e)
            {
                Log::error("Password reset mail sending failed.");
                return $this->send([
                    "type"      => "auth",
                    "message"    => "Something errors please try again after sometimes.",
                    "status"     => 500
                ]);
            }
        }

        return $this->send([
            'message'    => 'We have sent you an email with instructions on how to reset your password. If you don’t receive an email make sure to check your spam folder.'
        ]);
    }


    /**
     * Set a user defined new password if request have a valid password reset token
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function setNewPassword(Request $request)
    {

        $this->validate($request,
            [
                'new_password'=> 'required|confirmed',
                'password_reset_token'   => 'required|exists:customers,password_reset_token',
            ],
            [
                'new_password.required'  => "Password field required.",
                'new_password.confirmed' => "Password and confirm password does not match.",
                'password_reset_token.required'     => "Password reset token required",
                'password_reset_token.exists'       => "Invalid password reset token or already used.",
            ]);

        $customer = Customer::where('password_reset_token', $request->password_reset_token)->first();

        $customer->password = bcrypt($request->new_password);
        $customer->password_reset_token = null;

        try{
            app(HarlibService::class)->resetPassword($customer->email, $request->new_password);
            $customer->save();
        }
        catch (Exception $e)
        {
            Log::error("Harlib service exception. Line no: " . $e->getLine() . " Data: " . $customer );
            return $this->send([
                "type"     => "auth",
                "message"  => "Something wrong please try again later",
                "status"   => 500,
            ]);
        }

        return $this->send( [
            "message"  => "Password successfully changed",
        ]);
    }

}