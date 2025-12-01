<?php

namespace App\Http\Controllers;

use App\Events\ProductCachChanged;
use App\Http\Requests\CreatHoldRequest;
use App\Models\Hold;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class HoldController extends Controller
{

    public function store(CreatHoldRequest $request)
    {
        try {
            $hold = DB::transaction(function () use ($request) {
                $product = Product::lockForUpdate()->findOrFail($request->product_id);

                if ($request->qty > $product->available_stock) {
                    abort(
                        409,
                        'Not enough stock available , available stock is ' . "'$product->available_stock'",
                    );
                }

                $hold = Hold::create([
                    'product_id' => $product->id,
                    'qty' => $request->qty,
                    'expires_at' => now()->addMinutes(2),
                ]);

                event(new ProductCachChanged($product->id));

                return $hold;
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
            'hold_id' => $hold->id,
            'expires_date' => $hold->expires_at->format('Y-m-d') . ' at ' . $hold->expires_at->format('H:i'),

        ], 201);
    }

}
