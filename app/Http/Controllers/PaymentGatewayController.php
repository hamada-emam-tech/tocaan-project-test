<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

class PaymentGatewayController extends Controller
{
    public function index(): JsonResponse
    {
        $gatewaysConfig = config('payment.gateways', []);
        $defaultGateway = config('payment.default');

        $availableGateways = [];

        foreach ($gatewaysConfig as $code => $config) {
            if ($this->hasCredentials($config)) {
                $availableGateways[] = [
                    'code' => $code,
                    'name' => ucwords(str_replace('_', ' ', $code)),
                    'is_default' => $code === $defaultGateway,
                ];
            }
        }

        return response()->json([
            'success' => true,
            'data' => $availableGateways,
        ]);
    }

    protected function hasCredentials(array $config): bool
    {
        return !empty(array_filter($config, fn($value) => !empty($value)));
    }
}
