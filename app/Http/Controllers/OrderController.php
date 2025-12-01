<?php

namespace App\Http\Controllers;

use App\Events\ProductCachChanged;
use App\Http\Requests\StoreOrderRequest;
use App\Models\Hold;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function store(StoreOrderRequest $request)
    {
        try {
            $order = DB::transaction(function () use ($request) {

                $hold = Hold::active()->lockForUpdate()->find($request->hold_id);
                if (!$hold) {
                    abort(404, 'Hold is invalid or expired');
                }

                if ($hold->order) {
                    abort(409, 'Hold already used for an order');
                }

                $order_number = uniqid('order_');

                $order = Order::create([
                    'hold_id' => $hold->id,
                    'order_number' => $order_number,
                    'status' => 'pending'
                ]);

                
                $hold->product->decrement('stock', $hold->qty);

                event(new ProductCachChanged($hold->product_id));

                return $order;
            });

        } catch (\Throwable $e) {
            $status = ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface)
                ? $e->getStatusCode()
                : 500;

            return response()->json([
                'message' => $e->getMessage()
            ], $status);
        }

        return response()->json([
            'order_number' => $order->order_number,
        ], 201);
    }
}
