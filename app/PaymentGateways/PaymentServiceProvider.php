<?php


namespace App\PaymentGateways;

use App\PaymentGateways\PaymentManager;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class PaymentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('payment', function (Application $app) {
            return new PaymentManager($app);
        });
    }

    public function provides()
    {
        return ['payment'];
    }
}
