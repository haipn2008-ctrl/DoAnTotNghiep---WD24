<?php

namespace App\Http\Controllers;

use App\Services\PaymentWebhookProcessor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentWebhookController extends Controller
{
    public function __invoke(Request $request, PaymentWebhookProcessor $processor): JsonResponse
    {
        $configuredSecret = (string) config('services.payment_webhook.secret');
        $providedSecret = (string) $request->header('X-Webhook-Secret');

        if ($configuredSecret === '' || ! hash_equals($configuredSecret, $providedSecret)) {
            return response()->json(['success' => false, 'message' => 'Webhook không được xác thực.'], 401);
        }

        $data = $request->validate([
            'transaction_id' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:1', 'max:9999999999.99'],
            'content' => ['required', 'string', 'max:500'],
            'transaction_date' => ['required', 'date'],
        ]);

        $result = $processor->process($data);
        $event = $result['event'];

        return response()->json([
            'success' => true,
            'duplicate' => $result['duplicate'],
            'status' => $event->status,
            'message' => $result['duplicate'] ? 'Giao dịch đã được tiếp nhận trước đó.' : $event->message,
        ]);
    }
}
