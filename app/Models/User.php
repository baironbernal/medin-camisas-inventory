<?php

namespace App\Models;

use BaironBernal\ColombiaLocations\Models\Departamento;
use BaironBernal\ColombiaLocations\Models\Municipio;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

class User extends Authenticatable implements FilamentUser
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'first_name',
        'last_name',
        'identity_number',
        'email',
        'password',
        'phone_number',
        'whatsapp_number',
        'department_id',
        'municipality_id',
        'selling_channel',
        'business_name',
        'clothing_type',
        'selling_location',
        'is_active',
        'assigned_store_id',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
    ];

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active;
    }

    public function assignedStore(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'assigned_store_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Departamento::class, 'department_id');
    }

    public function municipality(): BelongsTo
    {
        return $this->belongsTo(Municipio::class, 'municipality_id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(Movement::class);
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function canAccessStore(Store $store): bool
    {
        if ($this->hasRole(['owner', 'admin', 'inventory_manager'])) {
            return true;
        }

        return $this->assigned_store_id === $store->id;
    }
}
