<?php

namespace App\Services\Payments;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class PayMongoGcashGateway implements PaymentGatewayContract
{
    public function createCheckout(Order $order): Payment
    {
        $secretKey = config('services.paymongo.secret_key');
        $webhookSecret = config('services.paymongo.webhook_secret');

        if (! is_string($secretKey) || $secretKey === '') {
            throw new PaymentGatewayException('PayMongo is not configured.');
        }

        if (! is_string($webhookSecret) || $webhookSecret === '') {
            throw new PaymentGatewayException('PayMongo webhook verification is not configured.');
        }

        $payment = Payment::firstOrCreate(
            ['order_id' => $order->id],
            [
                'provider' => 'paymongo',
                'provider_method_type' => 'qrph',
                'reference' => (string) Str::uuid(),
                'amount' => $order->total_amount,
                'status' => 'pending',
            ],
        );

        if ($payment->status === 'paid') {
            throw new PaymentGatewayException('This order has already been paid.');
        }

        if ($payment->provider !== 'paymongo' || $payment->status !== 'pending') {
            $payment->update([
                'provider' => 'paymongo',
                'provider_method_type' => 'qrph',
                'reference' => (string) Str::uuid(),
                'provider_reference' => null,
                'checkout_url' => null,
                'amount' => $order->total_amount,
                'status' => 'pending',
                'paid_at' => null,
                'refund_status' => null,
                'refunded_at' => null,
            ]);
        }

        if ($payment->provider_method_type !== 'qrph') {
            $payment->update([
                'provider_method_type' => 'qrph',
                'reference' => (string) Str::uuid(),
                'provider_reference' => null,
                'checkout_url' => null,
            ]);
        }

        if ($order->payment_method !== 'qrph') {
            $order->update(['payment_method' => 'qrph']);
        }

        if ($payment->checkout_url) {
            return $payment;
        }

        $amountInCents = (int) str_replace('.', '', number_format((float) $order->total_amount, 2, '.', ''));
        $response = Http::acceptJson()
            ->asJson()
            ->withBasicAuth($secretKey, '')
            ->withHeaders(['Idempotency-Key' => $payment->reference])
            ->connectTimeout(5)
            ->timeout(15)
            ->post('https://api.paymongo.com/v2/checkout_sessions', [
                'data' => [
                    'attributes' => [
                        'line_items' => [[
                            'name' => 'WaterStation order '.$order->order_number,
                            'amount' => $amountInCents,
                            'currency' => 'PHP',
                            'quantity' => 1,
                        ]],
                        'payment_method_types' => ['qrph'],
                        'success_url' => route('customer.orders.show', [
                            'order' => $order,
                            'payment' => 'success',
                        ]),
                        'cancel_url' => route('customer.orders.show', [
                            'order' => $order,
                            'payment' => 'cancelled',
                        ]),
                        'reference_number' => $order->order_number,
                        'metadata' => [
                            'order_id' => (string) $order->id,
                        ],
                    ],
                ],
            ])
            ->throw();

        $sessionId = $response->json('data.id');
        $checkoutUrl = $response->json('data.attributes.checkout_url');

        if (! is_string($sessionId) || $sessionId === ''
            || ! is_string($checkoutUrl)
            || parse_url($checkoutUrl, PHP_URL_SCHEME) !== 'https'
            || parse_url($checkoutUrl, PHP_URL_HOST) !== 'checkout.paymongo.com') {
            throw new PaymentGatewayException('PayMongo returned an invalid checkout session.');
        }

        $payment->update([
            'provider_reference' => $sessionId,
            'checkout_url' => $checkoutUrl,
        ]);

        return $payment->refresh();
    }
}
