<?php

namespace App\Events;

use App\Models\Product;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProductCachChanged
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     */
    public $product_id;
    public function __construct(int $product_id)
    {
        $this->product_id = $product_id;
    }

}
