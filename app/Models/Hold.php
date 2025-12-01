<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Hold extends Model
{
    use HasFactory;
    protected $fillable = [
        'product_id',
        'qty',
        'expires_at',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function order()
    {
        return $this->hasOne(Order::class);
    }

    public function scopeActive($query)
    {
        return $query->where('expires_at', '>', now())->whereDoesntHave('order');
    }
}
