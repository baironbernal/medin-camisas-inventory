<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductReview extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'name',
        'comment',
        'ip_hash',
        'is_visible',
    ];

    protected $hidden = [
        'ip_hash',
    ];

    protected $casts = [
        'is_visible' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public static function hashIp(string $ip): string
    {
        return hash_hmac('sha256', $ip, config('app.key'));
    }

    public function scopeVisible($query)
    {
        return $query->where('is_visible', true);
    }
}
