<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Sends SMS through IPROG SMS (https://sms.iprogtech.com), a pay-per-message
 * (~₱1/SMS) Philippine SMS API. Docs: https://sms.iprogtech.com/api/v1/documentation
 *
 * Endpoint used: POST /api/v1/sms_messages — the single-message send endpoint.
 * (IPROG also offers bulk send, OTP, and network-detection endpoints; this app
 * only needs single transactional messages, so only that one is wired up.)
 *
 * Sandbox behaviour: this environment has no real IPROG API token, and every
 * SMS costs real money once one is configured, so sending is off by default
 * (config('sms.enabled')). While off, messages are written to the log instead
 * of sent — this is deliberate, not a bug, so the whole app can be built,
 * demoed, and tested without a token or without accidentally spending credits.
 * Set SMS_ENABLED=true and SMS_IPROG_API_TOKEN in .env to actually send.
 */
class IprogSmsGateway implements SmsGatewayContract
{
    private const ENDPOINT = 'https://sms.iprogtech.com/api/v1/sms_messages';

    public function send(string $phoneNumber, string $message): bool
    {
        $phoneNumber = $this->normalize($phoneNumber);

        if (! config('sms.enabled') || ! config('sms.token')) {
            Log::info('[SMS sandbox — not actually sent]', ['to' => $phoneNumber, 'message' => $message]);

            return false;
        }

        try {
            $response = Http::asForm()->timeout(10)->post(self::ENDPOINT, [
                'api_token' => config('sms.token'),
                'phone_number' => $phoneNumber,
                'message' => $message,
            ]);

            if (! $response->successful() || (int) $response->json('status') !== 200) {
                Log::warning('IPROG SMS send failed', ['to' => $phoneNumber, 'response' => $response->json()]);

                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::warning('IPROG SMS request error', ['to' => $phoneNumber, 'error' => $e->getMessage()]);

            return false;
        }
    }

    /** IPROG accepts Philippine numbers as 09XXXXXXXXX or 639XXXXXXXXX; normalize to the latter. */
    private function normalize(string $phoneNumber): string
    {
        $digits = preg_replace('/\D/', '', $phoneNumber) ?? '';

        if (str_starts_with($digits, '0')) {
            $digits = '63'.substr($digits, 1);
        } elseif (strlen($digits) === 10) {
            $digits = '63'.$digits;
        }

        return $digits;
    }
}
