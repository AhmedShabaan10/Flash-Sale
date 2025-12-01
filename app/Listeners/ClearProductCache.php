<?php

namespace App\Listeners;

use App\Events\ProductCachChanged;
use Illuminate\Support\Facades\Cache;

class ClearProductCache
{
    public function handle(ProductCachChanged $event): void
    {
        Cache::forget("product_{$event->product_id}");
    }
}
