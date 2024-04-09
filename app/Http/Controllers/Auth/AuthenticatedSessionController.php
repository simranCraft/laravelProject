<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Listeners\LogSuccessfulLogout;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;


class AuthenticatedSessionController extends Controller
{

    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->user()->update(['login_time' => now()]);
      
        $request->session()->regenerate();

        return redirect()->intended(route('employees', absolute: false));
    }


    public function destroy(Request $request): RedirectResponse
    {
        $request->user()->update(['logout_time' => now()]);

        event(new LogSuccessfulLogout($request->user()));

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
