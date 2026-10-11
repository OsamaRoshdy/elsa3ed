<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\LoginOtp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Second login step for customers: enter the 6-digit code.
 */
class LoginOtpController extends Controller
{
    public function create(LoginOtp $otp): View|RedirectResponse
    {
        $user = $otp->pendingUser();

        if (! $user) {
            return redirect()->route('login')->with('error', __('Your login code has expired. Please sign in again.'));
        }

        return view('auth.otp', ['email' => $user->email]);
    }

    public function store(Request $request, LoginOtp $otp): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        $user = $otp->pendingUser();

        if (! $user) {
            return redirect()->route('login')->with('error', __('Your login code has expired. Please sign in again.'));
        }

        if (! $otp->verify($data['code'])) {
            if (! $otp->pendingUser()) {
                return redirect()->route('login')->with('error', __('Too many wrong codes. Please sign in again.'));
            }

            throw ValidationException::withMessages([
                'code' => trans_choice('The code is not correct. :count attempt left.|The code is not correct. :count attempts left.', $otp->attemptsLeft(), ['count' => $otp->attemptsLeft()]),
            ]);
        }

        $remember = $otp->remember();
        $otp->clear();

        Auth::login($user, $remember);
        $request->session()->regenerate();

        return redirect()->intended(LoginController::homeFor($user));
    }

    public function resend(LoginOtp $otp): RedirectResponse
    {
        $user = $otp->pendingUser();

        if (! $user) {
            return redirect()->route('login')->with('error', __('Your login code has expired. Please sign in again.'));
        }

        $otp->issue($user, $otp->remember());

        return back()->with('success', __('We sent you a new code.'));
    }
}
