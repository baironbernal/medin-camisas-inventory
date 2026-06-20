<?php

namespace App\Models;

use Spatie\Permission\Models\Permission as SpatiePermission;

/**
 * Custom Permission model extending Spatie's, adding human-readable
 * metadata (label, description, module) consumed by the admin panel
 * and the {@see \App\Authorization\PermissionCatalog}.
 */
class Permission extends SpatiePermission
{
    protected $fillable = [
        'name',
        'guard_name',
        'label',
        'description',
        'module',
    ];

    /**
     * Scope permissions to a single module, e.g. ?module=products.
     */
    public function scopeForModule($query, string $module)
    {
        return $query->where('module', $module);
    }

    /**
     * Falls back to a title-cased name when no Spanish label is stored.
     */
    public function getDisplayLabelAttribute(): string
    {
        return $this->label ?: str($this->name)->replace(['.', '_'], ' ')->title();
    }
}
