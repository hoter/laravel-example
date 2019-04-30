<?php

namespace App\Http\Controllers\Admin;



use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AuthController extends Controller
{

    public function login()
    {
        return view('admin.login');
    }

    public function attempt(Request $request)
    {
	    $user = config('auth.admin');

	    if($request->password == $user['password'] &&  $request->username == $user['username'])
        {
            session(['is_authenticated' => true]);

            return redirect()->route('home');
        }

        return redirect()->route('login');
    }


    public function logout()
    {
	    \Session::forget('is_authenticated');
	    return redirect()->route('login');
    }
}
