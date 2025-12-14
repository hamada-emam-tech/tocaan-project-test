<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class SettingServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        if (Schema::hasTable('settings')) {
            try {
                $gateways = Setting::get('payment_gateways');
                if ($gateways && is_array($gateways)) {
                    foreach ($gateways as $gateway) {
                        if (isset($gateway['code'])) {
                            config(["payment.gateways.{$gateway['code']}" => $gateway['credentials'] ?? []]);
                            if (!empty($gateway['is_default'])) {
                                config(['payment.default' => $gateway['code']]);
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                // Fail silently if DB connection issue
            }
        }
    }
}
