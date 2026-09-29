<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\PasswordResetOtpService;
use Illuminate\Http\Request;

class ForgotPasswordController extends Controller
{
    public function create()
    {
        return view('auth.forgot-password');
    }

    public function store(Request $request, PasswordResetOtpService $otp)
    {
        $data = $request->validate(['email' => ['required', 'email']]);

        $user = User::where('email', $data['email'])->first();

        // Always the same response whether or not the account exists, or has a
        // phone on file — confirming/denying an email is registered is itself
        // a data leak, so this stays deliberately non-committal either way.
        if ($user && $user->phone && $user->isActive()) {
            $otp->issue($user);
            $request->session()->put('password_reset_user_id', $user->id);
        }

        return redirect()->route('password.reset.verify')
            ->with('status', 'If that account exists and has a phone number on file, we sent a 6-digit code to it.');
    }
}
