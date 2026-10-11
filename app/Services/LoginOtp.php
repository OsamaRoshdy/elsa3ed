<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

/**
 * Second login step for customers: a 6-digit code checked before the user is logged in.
 *
 * The pending login (user, "remember me", hashed code, expiry, attempts) lives in the session,
 * so nothing is logged in until the code is correct.
 */
class LoginOtp
{
    private const SESSION_KEY = 'login_otp';

    public function __construct(private Session $session) {}

    public static function requiredFor(User $user): bool
    {
        return config('auth.login_otp.enabled') && $user->isClient();
    }

    /**
     * Creates a new code for the user and sends it.
     */
    public function issue(User $user, bool $remember): void
    {
        $code = $this->newCode();

        $this->session->put(self::SESSION_KEY, [
            'user_id' => $user->id,
            'remember' => $remember,
            'code' => Hash::make($code),
            'expires_at' => now()->addMinutes((int) config('auth.login_otp.expires_minutes'))->getTimestamp(),
            'attempts' => 0,
        ]);

        $this->send($user, $code);
    }

    /**
     * The user waiting for a code, or null when there is no (unexpired) pending login.
     */
    public function pendingUser(): ?User
    {
        $pending = $this->session->get(self::SESSION_KEY);

        if (! is_array($pending) || $pending['expires_at'] < now()->getTimestamp()) {
            return null;
        }

        return User::find($pending['user_id']);
    }

    public function remember(): bool
    {
        return (bool) ($this->session->get(self::SESSION_KEY)['remember'] ?? false);
    }

    /**
     * Checks the code and counts the attempt. Too many wrong attempts end the pending login.
     */
    public function verify(string $code): bool
    {
        $pending = $this->session->get(self::SESSION_KEY);

        if (! is_array($pending) || $pending['expires_at'] < now()->getTimestamp()) {
            return false;
        }

        if (Hash::check($code, $pending['code'])) {
            return true;
        }

        $pending['attempts']++;

        if ($pending['attempts'] >= (int) config('auth.login_otp.max_attempts')) {
            $this->clear();
        } else {
            $this->session->put(self::SESSION_KEY, $pending);
        }

        return false;
    }

    public function attemptsLeft(): int
    {
        $attempts = (int) ($this->session->get(self::SESSION_KEY)['attempts'] ?? 0);

        return max(0, (int) config('auth.login_otp.max_attempts') - $attempts);
    }

    public function clear(): void
    {
        $this->session->forget(self::SESSION_KEY);
    }

    private function newCode(): string
    {
        $fixed = config('auth.login_otp.fixed_code');

        return filled($fixed) ? (string) $fixed : str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Delivers the code to the customer.
     *
     * TODO: send by SMS (client phone) or email once a provider is chosen. Until then the
     * fixed code from LOGIN_OTP_FIXED_CODE is used, so nothing has to be delivered.
     */
    private function send(User $user, string $code): void
    {
        Log::info('Login code issued.', ['user_id' => $user->id, 'fixed' => filled(config('auth.login_otp.fixed_code'))]);
    }
}
