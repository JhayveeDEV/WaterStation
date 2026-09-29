<?php

namespace App\Providers;

use App\Models\Order;
use App\Policies\OrderPolicy;
use App\Services\Payments\MockGcashGateway;
use App\Services\Payments\PaymentGatewayContract;
use App\Services\Sms\IprogSmsGateway;
use App\Services\Sms\SmsGatewayContract;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Swap this binding for a real gateway (PayMongo, Xendit, etc.) to go
        // live — see the doc comment on MockGcashGateway for what changes.
        $this->app->bind(PaymentGatewayContract::class, MockGcashGateway::class);

        $this->app->bind(SmsGatewayContract::class, IprogSmsGateway::class);
    }

    public function boot(): void
    {
        Gate::policy(Order::class, OrderPolicy::class);

        // Keep asset URLs consistent when the app runs behind a reverse proxy in production.
        if (config('app.env') === 'production') {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }
    }
}
