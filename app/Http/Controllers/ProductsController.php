<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProductsResource;
use App\Models\Product;
use Illuminate\Support\Facades\Cache;

class ProductsController extends Controller
{
    public function show(Product $id)
    {
        $cachedProduct = Cache::remember("product_{$id->id}", 30, function () use ($id) {
            return (new ProductsResource($id))->toArray(request());
        });

        return response()->json(["data" => $cachedProduct]);
    }

}
