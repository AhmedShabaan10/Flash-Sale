<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'stock', 'price'];   
    public function holds()
    {
        return $this->hasMany(Hold::class);
    }

    public function getAvailableStockAttribute()
    {
        $reserved = $this->holds()->active()->sum('qty');

        return $this->stock - $reserved;
    }
}
