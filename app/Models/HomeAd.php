<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HomeAd extends Model
{
    use HasFactory;

    protected $fillable = [
        'message',
        'expiration_date',
        'is_active',
    ];

    protected $casts = [
        'expiration_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
