<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidatePaymentMethod
{
    /**
     * Handle an incoming request.
     *
     * Validates the payment method exists in configuration and
     * temporarily sets it as default if it's not already.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only process if payment_method is present in request
        if ($request->has('payment_method')) {
            $paymentMethod = $request->input('payment_method');

            // Check if gateway exists in configuration
            $gateway = config("payment.gateways.{$paymentMethod}");

            if (!$gateway) {
                return response()->json([
                    'success' => false,
                    'message' => "Payment method '{$paymentMethod}' is not configured or available.",
                ], 422);
            }

            // Temporarily set as default for this request if not already default
            $currentDefault = config('payment.default');
            if ($currentDefault !== $paymentMethod) {
                config(['payment.default' => $paymentMethod]);
            }
        }

        return $next($request);
    }
}
