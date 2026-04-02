<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DiscountRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'min_quantity',
        'max_quantity',
        'discount_type',
        'discount_value',
        'priority',
        'is_active',
    ];
}
