<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\Payments\PayMongoPaymentConfirmer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class PayMongoWebhookController extends Controller
{
    public function __invoke(Request $request, PayMongoPaymentConfirmer $payments): Response
    {
        $payload = json_decode($request->getContent(), true);

        if (! is_array($payload) || ! $this->hasValidSignature($request, $payload)) {
            return response('Invalid PayMongo webhook signature.', Response::HTTP_UNAUTHORIZED);
        }

        $event = data_get($payload, 'data');

        if (data_get($event, 'type') !== 'checkout_session.payment.paid') {
            return response()->noContent();
        }

        $session = data_get($event, 'data');
        $sessionId = data_get($session, 'id');
        $paymentAttempts = data_get($session, 'attributes.payments', []);

        if (! is_array($paymentAttempts)) {
            Log::warning('PayMongo sent an invalid paid checkout event.');

            return response()->noContent();
        }

        $paidAttempt = collect($paymentAttempts)->first(
            fn ($payment): bool => is_array($payment) && data_get($payment, 'attributes.status') === 'paid',
        );

        if (! is_string($sessionId) || $sessionId === '' || ! is_array($paidAttempt)) {
            Log::warning('PayMongo sent an incomplete paid checkout event.');

            return response()->noContent();
        }

        $payment = Payment::query()
            ->where('provider', 'paymongo')
            ->where('provider_reference', $sessionId)
            ->first();

        if (! $payment) {
            Log::warning('PayMongo sent a paid event for an unknown checkout session.', [
                'checkout_session_id' => $sessionId,
            ]);

            return response()->noContent();
        }

        $paidAttributes = data_get($paidAttempt, 'attributes', []);

        if (! is_array($paidAttributes)) {
            Log::warning('PayMongo sent incomplete payment details in a paid checkout event.', [
                'payment_id' => $payment->id,
                'checkout_session_id' => $sessionId,
            ]);

            return response()->noContent();
        }

        $payments->confirmPaidAttempt($payment, $paidAttributes);

        return response()->noContent();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function hasValidSignature(Request $request, array $payload): bool
    {
        $secretKey = config('services.paymongo.secret_key');
        $webhookSecret = config('services.paymongo.webhook_secret');

        if (! is_string($secretKey) || ! is_string($webhookSecret)
            || $secretKey === '' || $webhookSecret === '') {
            return false;
        }

        $isLive = str_starts_with($secretKey, 'sk_live_');

        if (data_get($payload, 'data.livemode') !== $isLive) {
            return false;
        }

        $signatureParts = [];
        foreach (explode(',', $request->header('PayMongo-Signature', '')) as $part) {
            [$name, $value] = array_pad(explode('=', trim($part), 2), 2, '');
            $signatureParts[$name] = $value;
        }

        $timestamp = $signatureParts['t'] ?? '';
        $signature = $signatureParts[$isLive ? 'li' : 'te'] ?? '';

        if ($timestamp === '' || ! ctype_digit($timestamp) || $signature === '') {
            return false;
        }

        $expectedSignature = hash_hmac(
            'sha256',
            $timestamp.'.'.$request->getContent(),
            $webhookSecret,
        );

        return hash_equals($expectedSignature, $signature);
    }
}
