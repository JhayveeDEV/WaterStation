<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Events\PaymentStatusUpdated;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class PayMongoCheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_creates_a_qrph_session_and_reuses_the_hosted_url(): void
    {
        $customer = $this->seedCustomer();
        $order = $this->createOnlineOrder($customer);
        $this->usePayMongoTestCredentials();
        Http::preventStrayRequests();
        Http::fake([
            'api.paymongo.com/v2/checkout_sessions' => Http::response([
                'data' => [
                    'id' => 'cs_test_123',
                    'attributes' => [
                        'checkout_url' => 'https://checkout.paymongo.com/test-session',
                    ],
                ],
            ], 201),
        ]);

        $firstResponse = $this->actingAs($customer)
            ->get(route('customer.payments.initiate', $order));
        $secondResponse = $this->get(route('customer.payments.initiate', $order));

        $firstResponse->assertRedirect('https://checkout.paymongo.com/test-session');
        $secondResponse->assertRedirect('https://checkout.paymongo.com/test-session');
        Http::assertSentCount(1);
        Http::assertSent(function (Request $request) use ($order): bool {
            $attributes = $request->data()['data']['attributes'];

            return $request->url() === 'https://api.paymongo.com/v2/checkout_sessions'
                && $request->method() === 'POST'
                && $request->hasHeader('Authorization', 'Basic '.base64_encode('sk_test_waterstation:'))
                && $request->hasHeader('Idempotency-Key')
                && $attributes['payment_method_types'] === ['qrph']
                && $attributes['line_items'][0]['amount'] === 12550
                && $attributes['line_items'][0]['currency'] === 'PHP'
                && $attributes['reference_number'] === $order->order_number;
        });

        $payment = Payment::where('order_id', $order->id)->firstOrFail();
        $this->assertSame('paymongo', $payment->provider);
        $this->assertSame('qrph', $payment->provider_method_type);
        $this->assertSame('cs_test_123', $payment->provider_reference);
        $this->assertSame('https://checkout.paymongo.com/test-session', $payment->checkout_url);
        $this->assertSame('pending', $payment->status);
        $this->assertSame('unpaid', $order->fresh()->payment_status);
    }

    public function test_pending_qrph_order_explains_how_to_pay_with_the_qr_code(): void
    {
        $customer = $this->seedCustomer();
        $order = $this->createOnlineOrder($customer);

        $this->actingAs($customer)
            ->get(route('customer.orders.show', $order))
            ->assertOk()
            ->assertSee('Continue to QR Ph payment')
            ->assertSee('Scan the QR Ph code on the secure checkout with a supported banking or e-wallet app.')
            ->assertDontSee('Open in GCash');
    }

    public function test_checkout_offers_qrph_as_the_online_payment_method(): void
    {
        $customer = $this->seedCustomer();
        $product = Product::factory()->create();

        $this->actingAs($customer)
            ->withSession(['cart' => [$product->id => 1]])
            ->get(route('customer.checkout'))
            ->assertOk()
            ->assertSee('value="qrph"', false)
            ->assertSee('QR Ph')
            ->assertDontSee('value="gcash"', false);
    }

    public function test_legacy_pending_gcash_checkout_is_recreated_as_qrph(): void
    {
        $customer = $this->seedCustomer();
        $order = $this->createOnlineOrder($customer);
        $order->update(['payment_method' => 'gcash']);
        $payment = $this->createPayMongoPayment($order);
        $this->usePayMongoTestCredentials();
        Http::preventStrayRequests();
        Http::fake([
            'api.paymongo.com/v2/checkout_sessions' => Http::response([
                'data' => [
                    'id' => 'cs_qrph_456',
                    'attributes' => [
                        'checkout_url' => 'https://checkout.paymongo.com/qrph-session',
                    ],
                ],
            ], 201),
        ]);

        $this->actingAs($customer)
            ->get(route('customer.payments.initiate', $order))
            ->assertRedirect('https://checkout.paymongo.com/qrph-session');

        Http::assertSent(fn (Request $request): bool => $request->data()['data']['attributes']['payment_method_types'] === ['qrph']);
        $this->assertSame('qrph', $payment->fresh()->provider_method_type);
        $this->assertSame('cs_qrph_456', $payment->fresh()->provider_reference);
        $this->assertSame('qrph', $order->fresh()->payment_method);
    }

    public function test_paymongo_api_failure_returns_customer_to_the_order_without_marking_it_paid(): void
    {
        $customer = $this->seedCustomer();
        $order = $this->createOnlineOrder($customer);
        $this->usePayMongoTestCredentials();
        Http::preventStrayRequests();
        Http::fake([
            'api.paymongo.com/v2/checkout_sessions' => Http::response([
                'errors' => [['detail' => 'The request could not be processed.']],
            ], 422),
        ]);

        $this->actingAs($customer)
            ->get(route('customer.payments.initiate', $order))
            ->assertRedirect(route('customer.orders.show', $order))
            ->assertSessionHas('error', 'We could not start your QR Ph payment. Please try again shortly.');

        $payment = Payment::where('order_id', $order->id)->firstOrFail();
        $this->assertSame('pending', $payment->status);
        $this->assertNull($payment->provider_reference);
        $this->assertSame('unpaid', $order->fresh()->payment_status);
    }

    public function test_checkout_is_blocked_until_webhook_verification_is_configured(): void
    {
        $customer = $this->seedCustomer();
        $order = $this->createOnlineOrder($customer);
        $this->usePayMongoTestCredentials();
        config(['services.paymongo.webhook_secret' => null]);
        Http::preventStrayRequests();

        $this->actingAs($customer)
            ->get(route('customer.payments.initiate', $order))
            ->assertRedirect(route('customer.orders.show', $order))
            ->assertSessionHas('error', 'We could not start your QR Ph payment. Please try again shortly.');

        Http::assertSentCount(0);
        $this->assertDatabaseMissing('payments', ['order_id' => $order->id]);
    }

    public function test_checkout_rejects_a_hosted_url_outside_paymongo(): void
    {
        $customer = $this->seedCustomer();
        $order = $this->createOnlineOrder($customer);
        $this->usePayMongoTestCredentials();
        Http::preventStrayRequests();
        Http::fake([
            'api.paymongo.com/v2/checkout_sessions' => Http::response([
                'data' => [
                    'id' => 'cs_test_123',
                    'attributes' => [
                        'checkout_url' => 'https://attacker.example/collect',
                    ],
                ],
            ], 201),
        ]);

        $this->actingAs($customer)
            ->get(route('customer.payments.initiate', $order))
            ->assertRedirect(route('customer.orders.show', $order))
            ->assertSessionHas('error', 'We could not start your QR Ph payment. Please try again shortly.');

        $this->assertNull(Payment::where('order_id', $order->id)->value('checkout_url'));
        $this->assertSame('unpaid', $order->fresh()->payment_status);
    }

    public function test_signed_paid_webhook_marks_matching_order_paid_once(): void
    {
        $customer = $this->seedCustomer();
        $order = $this->createOnlineOrder($customer);
        $payment = $this->createPayMongoPayment($order);
        $this->usePayMongoTestCredentials();
        Event::fake([PaymentStatusUpdated::class]);

        $payload = $this->paidWebhookPayload('cs_test_123');
        $response = $this->postPayMongoWebhook($payload);
        $this->postPayMongoWebhook($payload)->assertNoContent();

        $response->assertNoContent();
        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertSame('paid', $order->fresh()->payment_status);
        $this->assertNotNull($order->fresh()->paid_at);
        $this->assertDatabaseHas('activity_logs', [
            'module' => 'payments',
            'action' => 'paid',
            'reference_id' => $order->id,
        ]);
        $this->assertDatabaseCount('notifications', 1);
        Event::assertDispatchedTimes(PaymentStatusUpdated::class, 1);
    }

    public function test_success_redirect_verifies_checkout_with_paymongo_and_marks_order_paid(): void
    {
        $customer = $this->seedCustomer();
        $order = $this->createOnlineOrder($customer);
        $payment = $this->createPayMongoPayment($order);
        $this->usePayMongoTestCredentials();
        Http::preventStrayRequests();
        Http::fake([
            'api.paymongo.com/v2/checkout_sessions/cs_test_123' => Http::response([
                'data' => [
                    'id' => 'cs_test_123',
                    'attributes' => [
                        'payments' => [[
                            'id' => 'pay_test_123',
                            'attributes' => [
                                'status' => 'paid',
                                'currency' => 'PHP',
                                'amount' => 12800,
                                'net_amount' => 12550,
                            ],
                        ]],
                    ],
                ],
            ]),
        ]);
        Event::fake([PaymentStatusUpdated::class]);

        $this->actingAs($customer)
            ->get(route('customer.orders.show', ['order' => $order, 'payment' => 'success']))
            ->assertOk()
            ->assertSee('Payment: QR Ph · Paid');

        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertSame('paid', $order->fresh()->payment_status);
        $this->assertNotNull($order->fresh()->paid_at);
        $this->assertDatabaseHas('activity_logs', [
            'module' => 'payments',
            'action' => 'paid',
            'reference_id' => $order->id,
        ]);
        $this->assertDatabaseCount('notifications', 1);
        Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
            && $request->url() === 'https://api.paymongo.com/v2/checkout_sessions/cs_test_123'
            && $request->hasHeader('Authorization', 'Basic '.base64_encode('sk_test_waterstation:')));
        Event::assertDispatchedTimes(PaymentStatusUpdated::class, 1);
    }

    public function test_order_page_rechecks_paymongo_without_a_success_return_parameter(): void
    {
        $customer = $this->seedCustomer();
        $order = $this->createOnlineOrder($customer);
        $payment = $this->createPayMongoPayment($order);
        $this->usePayMongoTestCredentials();
        Http::preventStrayRequests();
        Http::fake([
            'api.paymongo.com/v2/checkout_sessions/cs_test_123' => Http::response([
                'data' => [
                    'id' => 'cs_test_123',
                    'attributes' => [
                        'payments' => [[
                            'id' => 'pay_test_123',
                            'attributes' => [
                                'status' => 'paid',
                                'currency' => 'PHP',
                                'amount' => 12800,
                                'net_amount' => 12550,
                            ],
                        ]],
                    ],
                ],
            ]),
        ]);
        Event::fake([PaymentStatusUpdated::class]);

        $this->actingAs($customer)
            ->get(route('customer.orders.show', $order))
            ->assertOk()
            ->assertSee('Payment: QR Ph · Paid');

        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertSame('paid', $order->fresh()->payment_status);
        Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
            && $request->url() === 'https://api.paymongo.com/v2/checkout_sessions/cs_test_123');
        Event::assertDispatchedTimes(PaymentStatusUpdated::class, 1);
    }

    public function test_successful_payment_intent_confirms_checkout_when_payment_list_is_empty(): void
    {
        $customer = $this->seedCustomer();
        $order = $this->createOnlineOrder($customer);
        $payment = $this->createPayMongoPayment($order);
        $this->usePayMongoTestCredentials();
        Http::preventStrayRequests();
        Http::fake([
            'api.paymongo.com/v2/checkout_sessions/cs_test_123' => Http::response([
                'data' => [
                    'id' => 'cs_test_123',
                    'attributes' => [
                        'payments' => [],
                        'payment_intent' => [
                            'id' => 'pi_test_123',
                            'attributes' => [
                                'status' => 'succeeded',
                                'currency' => 'PHP',
                                'amount' => 12550,
                            ],
                        ],
                    ],
                ],
            ]),
        ]);
        Event::fake([PaymentStatusUpdated::class]);

        $this->actingAs($customer)
            ->get(route('customer.orders.show', $order))
            ->assertOk()
            ->assertSee('Payment: QR Ph · Paid');

        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertSame('paid', $order->fresh()->payment_status);
        Event::assertDispatchedTimes(PaymentStatusUpdated::class, 1);
    }

    public function test_success_redirect_does_not_mark_order_paid_when_paymongo_reports_unpaid(): void
    {
        $customer = $this->seedCustomer();
        $order = $this->createOnlineOrder($customer);
        $payment = $this->createPayMongoPayment($order);
        $this->usePayMongoTestCredentials();
        Http::preventStrayRequests();
        Http::fake([
            'api.paymongo.com/v2/checkout_sessions/cs_test_123' => Http::response([
                'data' => [
                    'id' => 'cs_test_123',
                    'attributes' => [
                        'payments' => [[
                            'id' => 'pay_test_123',
                            'attributes' => [
                                'status' => 'pending',
                                'currency' => 'PHP',
                                'amount' => 12550,
                                'net_amount' => 12550,
                            ],
                        ]],
                    ],
                ],
            ]),
        ]);
        Event::fake([PaymentStatusUpdated::class]);

        $this->actingAs($customer)
            ->get(route('customer.orders.show', ['order' => $order, 'payment' => 'success']))
            ->assertOk()
            ->assertSee('Payment: QR Ph · Unpaid')
            ->assertSee('We check your payment with PayMongo whenever you open this order.')
            ->assertSee('Please do not pay again.');

        $this->assertSame('pending', $payment->fresh()->status);
        $this->assertSame('unpaid', $order->fresh()->payment_status);
        $this->assertDatabaseCount('activity_logs', 0);
        $this->assertDatabaseCount('notifications', 0);
        Event::assertNotDispatched(PaymentStatusUpdated::class);
    }

    public function test_invalid_signature_does_not_mark_order_paid(): void
    {
        $customer = $this->seedCustomer();
        $order = $this->createOnlineOrder($customer);
        $payment = $this->createPayMongoPayment($order);
        $this->usePayMongoTestCredentials();

        $this->postPayMongoWebhook($this->paidWebhookPayload('cs_test_123'), validSignature: false)
            ->assertUnauthorized();

        $this->assertSame('pending', $payment->fresh()->status);
        $this->assertSame('unpaid', $order->fresh()->payment_status);
        $this->assertDatabaseCount('activity_logs', 0);
    }

    public function test_paid_webhook_with_a_different_amount_does_not_mark_order_paid(): void
    {
        $customer = $this->seedCustomer();
        $order = $this->createOnlineOrder($customer);
        $payment = $this->createPayMongoPayment($order);
        $this->usePayMongoTestCredentials();

        $payload = $this->paidWebhookPayload('cs_test_123');
        $payload['data']['data']['attributes']['payments'][0]['attributes']['amount'] = 12800;
        $payload['data']['data']['attributes']['payments'][0]['attributes']['net_amount'] = 12549;

        $this->postPayMongoWebhook($payload)->assertNoContent();

        $this->assertSame('pending', $payment->fresh()->status);
        $this->assertSame('unpaid', $order->fresh()->payment_status);
        $this->assertDatabaseCount('activity_logs', 0);
    }

    public function test_mock_checkout_cannot_mark_a_paymongo_payment_paid(): void
    {
        $customer = $this->seedCustomer();
        $order = $this->createOnlineOrder($customer);
        $payment = $this->createPayMongoPayment($order);

        $this->actingAs($customer)
            ->post(route('payments.mock.complete', $payment))
            ->assertNotFound();

        $this->assertSame('pending', $payment->fresh()->status);
        $this->assertSame('unpaid', $order->fresh()->payment_status);
    }

    private function seedCustomer(): User
    {
        $this->seed([RolePermissionSeeder::class, UserSeeder::class]);

        return User::where('email', 'customer@waterstation.test')->firstOrFail();
    }

    private function createOnlineOrder(User $customer): Order
    {
        return Order::create([
            'order_number' => 'WS-'.Str::upper(Str::random(10)),
            'customer_id' => $customer->id,
            'delivery_method' => 'pickup',
            'contact_phone' => '09171234567',
            'schedule_date' => today()->addDay()->toDateString(),
            'subtotal' => '125.50',
            'delivery_fee' => '0.00',
            'total_amount' => '125.50',
            'payment_method' => 'qrph',
            'payment_status' => 'unpaid',
            'status' => OrderStatus::Pending,
        ]);
    }

    private function createPayMongoPayment(Order $order): Payment
    {
        return Payment::create([
            'order_id' => $order->id,
            'provider' => 'paymongo',
            'reference' => (string) Str::uuid(),
            'provider_reference' => 'cs_test_123',
            'checkout_url' => 'https://checkout.paymongo.com/test-session',
            'amount' => '125.50',
            'status' => 'pending',
        ]);
    }

    private function usePayMongoTestCredentials(): void
    {
        config([
            'services.payments.gateway' => 'paymongo',
            'services.paymongo.secret_key' => 'sk_test_waterstation',
            'services.paymongo.webhook_secret' => 'whsk_test_waterstation',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function paidWebhookPayload(string $sessionId): array
    {
        return [
            'data' => [
                'type' => 'checkout_session.payment.paid',
                'livemode' => false,
                'data' => [
                    'id' => $sessionId,
                    'attributes' => [
                        'payments' => [[
                            'id' => 'pay_test_123',
                            'attributes' => [
                                'status' => 'paid',
                                'currency' => 'PHP',
                                'amount' => 12800,
                                'net_amount' => 12550,
                            ],
                        ]],
                    ],
                ],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function postPayMongoWebhook(array $payload, bool $validSignature = true): TestResponse
    {
        $body = json_encode($payload, JSON_THROW_ON_ERROR);
        $timestamp = '1771515600';
        $signature = $validSignature
            ? hash_hmac('sha256', $timestamp.'.'.$body, 'whsk_test_waterstation')
            : str_repeat('0', 64);

        return $this->call(
            'POST',
            route('webhooks.paymongo'),
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_PAYMONGO_SIGNATURE' => "t={$timestamp},te={$signature}",
            ],
            $body,
        );
    }
}
