<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidatePaymentMethod
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->has('payment_method')) {
            $paymentMethod = $request->input('payment_method');

            $gateway = config("payment.gateways.{$paymentMethod}");

            if (!$gateway) {
                return response()->json([
                    'success' => false,
                    'message' => "Payment method '{$paymentMethod}' is not configured or available.",
                ], 422);
            }

            $currentDefault = config('payment.default');
            if ($currentDefault !== $paymentMethod) {
                config(['payment.default' => $paymentMethod]);
            }
        }

        return $next($request);
    }
}
