<?php

namespace App\Providers;

use App\Models\Order;
use App\Policies\OrderPolicy;
use App\Services\Payments\MockGcashGateway;
use App\Services\Payments\PaymentGatewayContract;
use App\Services\Payments\PayMongoGcashGateway;
use App\Services\Sms\IprogSmsGateway;
use App\Services\Sms\SmsGatewayContract;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PaymentGatewayContract::class, function () {
            return match (config('services.payments.gateway')) {
                'mock' => $this->app->make(MockGcashGateway::class),
                'paymongo' => $this->app->make(PayMongoGcashGateway::class),
                default => throw new InvalidArgumentException('Unsupported payment gateway configured.'),
            };
        });

        $this->app->bind(SmsGatewayContract::class, IprogSmsGateway::class);
    }

    public function boot(): void
    {
        Gate::policy(Order::class, OrderPolicy::class);

        // Keep asset URLs consistent when the app runs behind a reverse proxy in production.
        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }
    }
}
