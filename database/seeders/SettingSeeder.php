<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Remove old keys if they exist (cleanup)
        Setting::whereIn('key', ['default_payment_gateway', 'payment_gateways_config'])->delete();

        $config = [
            [
                'code' => 'credit_card',
                'credentials' => [
                    'api_key' => 'db_stripe_key',
                    'secret' => 'db_stripe_secret'
                ],
                'is_default' => true
            ],
            [
                'code' => 'paypal',
                'credentials' => [
                    'client_id' => 'db_paypal_client',
                    'secret' => 'db_paypal_secret'
                ],
                'is_default' => false
            ]
        ];

        Setting::updateOrCreate(
            ['key' => 'payment_gateways'],
            [
                'value' => json_encode($config),
                'description' => 'Configuration for all payment gateways including credentials and default status.',
            ]
        );
    }
}
