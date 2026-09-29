<?php

namespace App\Services;

use App\Models\User;
use App\Services\Sms\SmsGatewayContract;
use Illuminate\Support\Facades\Cache;

/**
 * Password reset via a 6-digit SMS code instead of an emailed link — this app
 * has no email flow anywhere else, so a code texted to the phone already on
 * file keeps things consistent. The code itself lives in cache (10 min TTL),
 * not the database — there's nothing to clean up and it can't outlive its
 * usefulness. Delivery reuses SmsGatewayContract::send(), so it's just as
 * sandboxed (logged, not actually texted) until SMS_ENABLED is turned on.
 */
class PasswordResetOtpService
{
    private const TTL_MINUTES = 10;

    public function __construct(private readonly SmsGatewayContract $sms) {}

    public function issue(User $user): void
    {
        $code = (string) random_int(100000, 999999);

        Cache::put($this->key($user), $code, now()->addMinutes(self::TTL_MINUTES));

        $this->sms->send(
            $user->phone,
            "Your ".config('app.name')." password reset code is {$code}. It expires in ".self::TTL_MINUTES." minutes."
        );
    }

    public function verify(User $user, string $code): bool
    {
        $stored = Cache::get($this->key($user));

        return $stored !== null && hash_equals($stored, $code);
    }

    public function forget(User $user): void
    {
        Cache::forget($this->key($user));
    }

    private function key(User $user): string
    {
        return "password-reset-otp:{$user->id}";
    }
}
