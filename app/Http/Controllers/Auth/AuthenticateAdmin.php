<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Routing\Controller as BaseController;

use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

use App\User;

use Validator;
use Auth;
use Lang;

class AuthenticateAdmin extends BaseController
{
    /*
    |--------------------------------------------------------------------------
    | Registration & Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users, as well as the
    | authentication of existing users. By default, this controller uses
    | a simple trait to add these behaviors. Why don't you explore it?
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login / registration.
     *
     * @var string
     */
    protected $redirectTo = 'cms';
    protected function guard() {
        return Auth::guard('admin');
    }
    public function showLoginForm()
    {
        return view('admin.login');
    }

    public function postLogin(Request $request)
    {
        if (Auth::guard('admin')->attempt( ['username' => $request->input('username'), 'password' => $request->input('password') ], $request->input('remember') )) {
            return redirect()->intended('');
        } else {
            return redirect('cms/login')
            ->withInput()
            ->with('error-message', trans('admin.page.login.error'));;
        }
    }

    public function getLogout() {
        Auth::guard('admin')->logout();
        return redirect('cms/');
    }

}