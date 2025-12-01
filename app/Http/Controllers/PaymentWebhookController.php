<?php

namespace App\Http\Controllers;

use App\Events\ProductCachChanged;
use App\Http\Requests\StorePaymentWebhookReques;
use App\Models\Order;
use App\Models\PaymentWebhook;
use Illuminate\Support\Facades\DB;

class PaymentWebhookController extends Controller
{
    public function handle(StorePaymentWebhookReques $request)
    {
        $idempotencyKey = $request['idempotency_key'];
        $order_number = $request['order_number'];
        $status = $request['status'];
        
        $order = Order::with('hold.product')->where('order_number', $order_number)->first();

        if (!$order) {
            return response()->json(['message' => 'Order not found yet, will retry later'], 202);
        }
        
        $webhook = PaymentWebhook::firstOrCreate(
            ['idempotency_key' => $idempotencyKey],
            [
                'order_number' => $order_number,
                'payload' => json_encode($request->all()),
                'status' => $status,
                'processed_at' => now(),
            ]
        );

        if ($webhook->wasRecentlyCreated === false) {
            return response()->json(['message' => 'Already processed'], 200);
        }


        try {
            DB::transaction(function () use ($order, $status, $webhook) {

                $webhook->order_id = $order->id;
                $webhook->save();

                if ($status === 'success') {
                    $order->status = 'paid';
                    $order->save();

                } else {
                    $order->status = 'cancelled';
                    $order->save();

                    if ($order->hold && $order->hold->product) {
                        $order->hold->product->increment('stock', $order->hold->qty);

                        event(new ProductCachChanged($order->hold->product_id));
                    }
                }

                $webhook->update([
                    'order_id' => $order->id,
                    'processed_at' => now(),
                    'status' => $status,
                ]);
            });

            return response()->json(['message' => 'Webhook processed'], 200);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Processing failed', 'error' => $e->getMessage()], 500);
        }
    }
}
