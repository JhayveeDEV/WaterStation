<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\PasswordResetOtpService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

class ResetPasswordController extends Controller
{
    public function create(Request $request)
    {
        abort_unless($request->session()->has('password_reset_user_id'), 404);

        return view('auth.reset-password');
    }

    public function store(Request $request, PasswordResetOtpService $otp)
    {
        $userId = $request->session()->get('password_reset_user_id');
        abort_unless($userId, 404);

        $data = $request->validate([
            'code' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = User::findOrFail($userId);

        if (! $otp->verify($user, $data['code'])) {
            return back()->withErrors(['code' => 'That code is incorrect or has expired. Request a new one below.']);
        }

        $user->update(['password' => $data['password']]);
        $otp->forget($user);
        $request->session()->forget('password_reset_user_id');

        return redirect()->route('login')->with('status', 'Password updated. Please sign in.');
    }
}
