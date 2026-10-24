<?php

namespace App\Services\Payments;

use App\Events\PaymentStatusUpdated;
use App\Models\ActivityLog;
use App\Models\Order;
use App\Models\Payment;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PayMongoPaymentConfirmer
{
    public function __construct(private NotificationService $notifications) {}

    public function confirmFromCheckoutSession(Payment $payment): ?Order
    {
        $payment->refresh();

        if ($payment->status === 'paid') {
            return $payment->order;
        }

        if ($payment->provider !== 'paymongo'
            || $payment->status !== 'pending'
            || ! is_string($payment->provider_reference)
            || $payment->provider_reference === '') {
            return null;
        }

        $secretKey = config('services.paymongo.secret_key');

        if (! is_string($secretKey) || $secretKey === '') {
            throw new PaymentGatewayException('PayMongo is not configured.');
        }

        $sessionId = $payment->provider_reference;
        $session = Http::acceptJson()
            ->withBasicAuth($secretKey, '')
            ->connectTimeout(5)
            ->timeout(15)
            ->get('https://api.paymongo.com/v1/checkout_sessions/'.rawurlencode($sessionId))
            ->throw()
            ->json('data');

        if (! is_array($session) || data_get($session, 'id') !== $sessionId) {
            Log::warning('PayMongo returned an invalid checkout session during payment confirmation.', [
                'payment_id' => $payment->id,
                'checkout_session_id' => $sessionId,
            ]);

            return null;
        }

        $paymentAttempts = data_get($session, 'attributes.payments', []);

        if (! is_array($paymentAttempts)) {
            Log::warning('PayMongo returned invalid checkout payments during payment confirmation.', [
                'payment_id' => $payment->id,
                'checkout_session_id' => $sessionId,
            ]);

            return null;
        }

        $paidAttempt = collect($paymentAttempts)->first(
            fn ($attempt): bool => is_array($attempt) && data_get($attempt, 'attributes.status') === 'paid',
        );

        if (is_array($paidAttempt)) {
            $paidAttributes = data_get($paidAttempt, 'attributes', []);

            if (! is_array($paidAttributes)) {
                Log::warning('PayMongo returned incomplete payment details during payment confirmation.', [
                    'payment_id' => $payment->id,
                    'checkout_session_id' => $sessionId,
                ]);

                return null;
            }

            return $this->markPaid($payment, $paidAttributes);
        }

        $paymentIntentAttributes = data_get($session, 'attributes.payment_intent.attributes', []);

        if (is_array($paymentIntentAttributes) && data_get($paymentIntentAttributes, 'status') === 'succeeded') {
            return $this->markPaid($payment, $paymentIntentAttributes);
        }

        Log::info('PayMongo checkout session does not yet show a paid payment.', [
            'payment_id' => $payment->id,
            'checkout_session_id' => $sessionId,
            'session_status' => data_get($session, 'attributes.status'),
            'payment_statuses' => collect($paymentAttempts)
                ->filter(fn ($attempt): bool => is_array($attempt))
                ->map(fn (array $attempt) => data_get($attempt, 'attributes.status'))
                ->values()
                ->all(),
            'payment_intent_status' => data_get($paymentIntentAttributes, 'status'),
        ]);

        return null;
    }

    /**
     * @param  array<string, mixed>  $paidAttributes
     */
    public function confirmPaidAttempt(Payment $payment, array $paidAttributes): ?Order
    {
        return $this->markPaid($payment, $paidAttributes);
    }

    /**
     * @param  array<string, mixed>  $paidAttributes
     */
    private function markPaid(Payment $payment, array $paidAttributes): ?Order
    {
        $expectedAmount = (int) str_replace('.', '', number_format((float) $payment->amount, 2, '.', ''));
        $paidAmount = data_get($paidAttributes, 'amount');
        $netAmount = data_get($paidAttributes, 'net_amount');
        $matchesExpectedAmount = collect([$paidAmount, $netAmount])
            ->contains(fn ($amount): bool => is_numeric($amount) && (int) $amount === $expectedAmount);

        if (data_get($paidAttributes, 'currency') !== 'PHP'
            || ! $matchesExpectedAmount) {
            Log::warning('PayMongo paid event did not match the expected payment amount.', [
                'payment_id' => $payment->id,
                'checkout_session_id' => $payment->provider_reference,
                'expected_amount' => $expectedAmount,
                'amount' => $paidAmount,
                'net_amount' => $netAmount,
                'currency' => data_get($paidAttributes, 'currency'),
            ]);

            return null;
        }

        $order = DB::transaction(function () use ($payment): ?Order {
            $lockedPayment = Payment::query()
                ->whereKey($payment->id)
                ->lockForUpdate()
                ->first();

            if (! $lockedPayment || $lockedPayment->provider !== 'paymongo' || $lockedPayment->status !== 'pending') {
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
            $this->notifications->send(
                $order->customer,
                'Payment received',
                'We received your '.$order->payment_method_label.' payment for order '.$order->order_number.'.',
                'ok',
                route('customer.orders.show', $order),
                "Payment received for order {$order->order_number} (₱".number_format((float) $payment->amount, 2).' via '.$order->payment_method_label.'). Thank you!'
            );
        }

        return $order;
    }
}
