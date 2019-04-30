<?php

namespace App\Http\Controllers;

use App\Mail\PasswordResetLinkEmail;
use App\Models\Customer;
use App\Services\Api\HarlibService;
use App\Services\Harlib\HarlibApi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Mockery\Exception;
use Validator;

class AuthController extends Controller
{
    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function forgetPassword()
    {
        return view('auth.forget-password');
    }


    /**
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function sendPasswordResetLink(Request $request)
    {
        $this->validate($request, ['email' => 'required|email|exists:customers,email']);
        $customer = Customer::where('email', $request->email)->first();

        if(!$customer->password_reset_token)
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
            return back()->withErrors(['Something errors please try again after sometimes.']);
        }

        return back()->with('message', 'We have sent you an email with instructions on how to reset your password. If you don’t receive an email make sure to check your spam folder.');
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' =>  'required|exists:customers,password_reset_token',
        ]);

        if ($validator->fails()) {
            return redirect()
                ->route('forget-password')
                ->withErrors(["You don't have valid link to reset password."]);
        }

        $customer = Customer::where('password_reset_token', $request->token)->first();
        return view('auth.reset-password',['customer' => $customer, 'token' => $request->token]);
    }


    /**
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updatePassword(Request $request)
    {

        $this->validate($request, [
            'password'=> 'required|confirmed',
            'token'   => 'required|exists:customers,password_reset_token',
        ]);

        $customer = Customer::where('password_reset_token', $request->token)->first();

        $customer->password = bcrypt($request->password);
        $customer->password_reset_token = null;
        try{
            app(HarlibService::class)->resetPassword($customer->email, $request->password);
            $customer->save();
        }
        catch (Exception $e)
        {
            return redirect()->route('reset-password')->withErrors(['Please try again.']);
        }

        return redirect()->route('forget-password')->with('password-reset-success', true);
    }
}