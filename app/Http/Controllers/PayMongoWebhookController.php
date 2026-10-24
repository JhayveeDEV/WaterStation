<?php

namespace App\Http\Controllers;

use App\Events\PaymentStatusUpdated;
use App\Models\ActivityLog;
use App\Models\Order;
use App\Models\Payment;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class PayMongoWebhookController extends Controller
{
    public function __invoke(Request $request, NotificationService $notifications): Response
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
        $expectedAmount = (int) str_replace('.', '', number_format((float) $payment->amount, 2, '.', ''));

        if (data_get($paidAttributes, 'currency') !== 'PHP'
            || ! is_numeric(data_get($paidAttributes, 'amount'))
            || (int) data_get($paidAttributes, 'amount') !== $expectedAmount) {
            Log::warning('PayMongo paid event did not match the expected payment amount.', [
                'payment_id' => $payment->id,
                'checkout_session_id' => $sessionId,
            ]);

            return response()->noContent();
        }

        $order = DB::transaction(function () use ($payment): ?Order {
            $lockedPayment = Payment::query()
                ->whereKey($payment->id)
                ->lockForUpdate()
                ->first();

            if (! $lockedPayment || $lockedPayment->status !== 'pending') {
                return null;
            }

            $order = Order::query()
                ->whereKey($lockedPayment->order_id)
                ->lockForUpdate()
                ->firstOrFail();

            $paidAt = now();
            $lockedPayment->update(['status' => 'paid', 'paid_at' => $paidAt]);
            $order->update(['payment_status' => 'paid', 'paid_at' => $paidAt]);

            if (in_array($order->status->value, ['cancelled', 'rejected'], true)) {
                $lockedPayment->update(['refund_status' => 'pending']);
            }

            ActivityLog::record(
                'payments',
                'paid',
                $order->id,
                $order->order_number.' via '.$order->payment_method_label.' (PayMongo)',
                $order->customer_id,
            );

            return $order->fresh();
        });

        if ($order) {
            event(new PaymentStatusUpdated($order));
            $notifications->send(
                $order->customer,
                'Payment received',
                'We received your '.$order->payment_method_label.' payment for order '.$order->order_number.'.',
                'ok',
                route('customer.orders.show', $order),
                "Payment received for order {$order->order_number} (₱".number_format((float) $payment->amount, 2).' via '.$order->payment_method_label.'). Thank you!'
            );
        }

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
