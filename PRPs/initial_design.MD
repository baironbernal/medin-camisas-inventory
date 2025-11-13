# Sistema de Inventario Multi-Tienda con Laravel y Filament

## Descripción del Proyecto
Sistema completo de gestión de inventario para tienda mayorista con múltiples sucursales, manejo de variantes de productos (talla, color, material), control de movimientos y gestión de temporadas.

## Stack Tecnológico
- **Laravel**: 11.x (última versión)
- **PHP**: 8.2+
- **Base de Datos**: MySQL 8.0
- **Admin Panel**: Filament 3.x
- **Roles y Permisos**: Spatie Laravel-permission
- **Autenticación**: Laravel Breeze/Sanctum

## Instalación y Configuración

### 1. Crear el proyecto e instalar dependencias

```bash
# Crear proyecto Laravel
composer create-project laravel/laravel inventory-system
cd inventory-system

# Instalar Filament
composer require filament/filament:"^3.2"

# Instalar Spatie Permission
composer require spatie/laravel-permission

# Instalar otros paquetes útiles
composer require spatie/laravel-sluggable
composer require spatie/laravel-medialibrary
composer require spatie/laravel-activitylog
composer require barryvdh/laravel-debugbar --dev

# Publicar configuraciones
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
php artisan vendor:publish --provider="Spatie\MediaLibrary\MediaLibraryServiceProvider" --tag="medialibrary-migrations"
php artisan filament:install --panels
```

### 2. Configuración de Base de Datos

**.env**
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=inventory_system
DB_USERNAME=root
DB_PASSWORD=your_password
```

## PARTE 1: MIGRACIONES

### Migration 1: Crear tabla de temporadas
```php
<?php
// database/migrations/2024_01_01_000001_create_seasons_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seasons', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'start_date', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seasons');
    }
};
```

### Migration 2: Crear tabla de tiendas
```php
<?php
// database/migrations/2024_01_01_000002_create_stores_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('address');
            $table->string('city');
            $table->string('state')->nullable();
            $table->string('country')->default('Colombia');
            $table->string('postal_code')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('email')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('manager_name')->nullable();
            $table->decimal('latitude', 10, 6)->nullable();
            $table->decimal('longitude', 10, 6)->nullable();
            $table->integer('max_capacity')->default(10000);
            $table->timestamps();

            $table->index('is_active');
            $table->index(['latitude', 'longitude']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stores');
    }
};
```

### Migration 3: Actualizar tabla de usuarios
```php
<?php
// database/migrations/2024_01_01_000003_update_users_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('first_name')->after('name');
            $table->string('last_name')->after('first_name');
            $table->string('phone_number')->nullable()->after('email');
            $table->boolean('is_active')->default(true)->after('password');
            $table->foreignId('assigned_store_id')->nullable()->constrained('stores');
            $table->timestamp('last_login_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['assigned_store_id']);
            $table->dropColumn([
                'first_name',
                'last_name',
                'phone_number',
                'is_active',
                'assigned_store_id',
                'last_login_at'
            ]);
        });
    }
};
```

### Migration 4: Crear tabla de categorías
```php
<?php
// database/migrations/2024_01_01_000004_create_categories_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('parent_id')->references('id')->on('categories')->onDelete('cascade');
            $table->index('slug');
            $table->index('parent_id');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
```

### Migration 5: Crear tabla de productos
```php
<?php
// database/migrations/2024_01_01_000005_create_products_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('reference_code')->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->foreignId('season_id')->constrained('seasons');
            $table->foreignId('category_id')->constrained('categories');
            $table->decimal('base_price', 10, 2);
            $table->decimal('cost', 10, 2);
            $table->string('brand')->nullable();
            $table->string('supplier')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('images')->nullable();
            $table->json('tags')->nullable();
            $table->json('specifications')->nullable();
            $table->timestamps();

            $table->index('reference_code');
            $table->index('slug');
            $table->index(['season_id', 'is_active']);
            $table->index(['category_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
```

### Migration 6: Crear tabla de atributos
```php
<?php
// database/migrations/2024_01_01_000006_create_attributes_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attributes', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->enum('data_type', ['text', 'number', 'color', 'boolean'])->default('text');
            $table->boolean('is_required')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attributes');
    }
};
```

### Migration 7: Crear tabla de valores de atributos
```php
<?php
// database/migrations/2024_01_01_000007_create_attribute_values_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attribute_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attribute_id')->constrained('attributes')->onDelete('cascade');
            $table->string('value');
            $table->string('code');
            $table->string('hex_color')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['attribute_id', 'code']);
            $table->index(['attribute_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attribute_values');
    }
};
```

### Migration 8: Crear tabla de variantes de productos
```php
<?php
// database/migrations/2024_01_01_000008_create_product_variants_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->string('sku')->unique();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->decimal('price', 10, 2);
            $table->decimal('cost', 10, 2);
            $table->decimal('weight', 8, 3)->nullable();
            $table->string('barcode')->nullable()->unique();
            $table->string('qr_code')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('images')->nullable();
            $table->timestamps();

            $table->index('sku');
            $table->index('barcode');
            $table->index(['product_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
```

### Migration 9: Crear tabla de atributos de variantes
```php
<?php
// database/migrations/2024_01_01_000009_create_variant_attributes_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('variant_attributes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')->constrained('product_variants')->onDelete('cascade');
            $table->foreignId('attribute_id')->constrained('attributes');
            $table->foreignId('attribute_value_id')->constrained('attribute_values');
            $table->timestamps();

            $table->unique(['product_variant_id', 'attribute_id'], 'variant_attribute_unique');
            $table->index('product_variant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('variant_attributes');
    }
};
```

### Migration 10: Crear tabla de inventario
```php
<?php
// database/migrations/2024_01_01_000010_create_inventories_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')->constrained('product_variants');
            $table->foreignId('store_id')->constrained('stores');
            $table->integer('quantity_available')->default(0);
            $table->integer('quantity_reserved')->default(0);
            $table->integer('quantity_in_transit')->default(0);
            $table->integer('min_quantity')->nullable();
            $table->integer('max_quantity')->nullable();
            $table->integer('reorder_point')->default(0);
            $table->string('location')->nullable();
            $table->date('last_restock_date')->nullable();
            $table->date('last_sale_date')->nullable();
            $table->date('last_inventory_check_date')->nullable();
            $table->timestamps();

            $table->unique(['product_variant_id', 'store_id']);
            $table->index(['store_id', 'quantity_available']);
            $table->index('quantity_available');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventories');
    }
};
```

### Migration 11: Crear tabla de movimientos
```php
<?php
// database/migrations/2024_01_01_000011_create_movements_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movements', function (Blueprint $table) {
            $table->id();
            $table->enum('type', [
                'purchase',
                'sale',
                'transfer',
                'adjustment',
                'return',
                'damage',
                'production'
            ]);
            $table->foreignId('product_variant_id')->constrained('product_variants');
            $table->foreignId('inventory_id')->constrained('inventories');
            $table->foreignId('store_id')->constrained('stores');
            $table->foreignId('destination_store_id')->nullable()->constrained('stores');
            $table->integer('quantity');
            $table->integer('quantity_before');
            $table->integer('quantity_after');
            $table->decimal('unit_cost', 10, 2)->nullable();
            $table->decimal('total_cost', 10, 2)->nullable();
            $table->string('reference_document')->nullable();
            $table->string('customer_id')->nullable();
            $table->string('supplier_id')->nullable();
            $table->foreignId('user_id')->constrained('users');
            $table->text('notes')->nullable();
            $table->string('batch_number')->nullable();
            $table->date('expiration_date')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['type', 'created_at']);
            $table->index(['product_variant_id', 'store_id']);
            $table->index(['store_id', 'created_at']);
            $table->index('reference_document');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movements');
    }
};
```

### Migration 12: Crear tabla de reglas de precios
```php
<?php
// database/migrations/2024_01_01_000012_create_price_rules_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('product_id')->nullable()->constrained('products');
            $table->foreignId('attribute_id')->nullable()->constrained('attributes');
            $table->foreignId('attribute_value_id')->nullable()->constrained('attribute_values');
            $table->enum('modifier_type', ['percentage', 'fixed_amount']);
            $table->decimal('modifier_value', 10, 2);
            $table->integer('priority')->default(0);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'start_date', 'end_date']);
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_rules');
    }
};
```

## PARTE 2: MODELOS

### Modelo User (actualizado)
```php
<?php
// app/Models/User.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'first_name',
        'last_name',
        'email',
        'password',
        'phone_number',
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

    public function assignedStore(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'assigned_store_id');
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
```

### Modelo Season
```php
<?php
// app/Models/Season.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Season extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'description',
        'start_date',
        'end_date',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeCurrent($query)
    {
        $now = now();
        return $query->where('is_active', true)
            ->where('start_date', '<=', $now)
            ->where('end_date', '>=', $now);
    }

    public function isCurrent(): bool
    {
        $now = now();
        return $this->is_active &&
            $this->start_date <= $now &&
            $this->end_date >= $now;
    }
}
```

### Modelo Store
```php
<?php
// app/Models/Store.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Store extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'address',
        'city',
        'state',
        'country',
        'postal_code',
        'phone_number',
        'email',
        'is_active',
        'manager_name',
        'latitude',
        'longitude',
        'max_capacity',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'latitude' => 'decimal:6',
        'longitude' => 'decimal:6',
        'max_capacity' => 'integer',
    ];

    public function inventories(): HasMany
    {
        return $this->hasMany(Inventory::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(Movement::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'assigned_store_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getCurrentCapacity(): int
    {
        return $this->inventories()->sum('quantity_available');
    }

    public function getCapacityPercentage(): float
    {
        if ($this->max_capacity == 0) {
            return 0;
        }

        return ($this->getCurrentCapacity() / $this->max_capacity) * 100;
    }
}
```

### Modelo Category
```php
<?php
// app/Models/Category.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Category extends Model
{
    use HasFactory, HasSlug;

    protected $fillable = [
        'code',
        'name',
        'slug',
        'description',
        'parent_id',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    public function getFullNameAttribute(): string
    {
        if ($this->parent) {
            return $this->parent->full_name . ' > ' . $this->name;
        }

        return $this->name;
    }
}
```

### Modelo Product
```php
<?php
// app/Models/Product.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Product extends Model implements HasMedia
{
    use HasFactory, HasSlug, InteractsWithMedia;

    protected $fillable = [
        'reference_code',
        'name',
        'slug',
        'description',
        'season_id',
        'category_id',
        'base_price',
        'cost',
        'brand',
        'supplier',
        'is_active',
        'images',
        'tags',
        'specifications',
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'cost' => 'decimal:2',
        'is_active' => 'boolean',
        'images' => 'array',
        'tags' => 'array',
        'specifications' => 'array',
    ];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug');
    }

    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function priceRules(): HasMany
    {
        return $this->hasMany(PriceRule::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInSeason($query, $seasonId)
    {
        return $query->where('season_id', $seasonId);
    }

    public function getTotalStockAttribute(): int
    {
        return $this->variants()
            ->with('inventories')
            ->get()
            ->sum(fn($variant) => $variant->inventories->sum('quantity_available'));
    }

    public function getVariantCountAttribute(): int
    {
        return $this->variants()->count();
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('product-images')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }
}
```

### Modelo Attribute
```php
<?php
// app/Models/Attribute.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Attribute extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'data_type',
        'is_required',
        'sort_order',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function values(): HasMany
    {
        return $this->hasMany(AttributeValue::class);
    }

    public function variantAttributes(): HasMany
    {
        return $this->hasMany(VariantAttribute::class);
    }

    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}
```

### Modelo AttributeValue
```php
<?php
// app/Models/AttributeValue.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttributeValue extends Model
{
    use HasFactory;

    protected $fillable = [
        'attribute_id',
        'value',
        'code',
        'hex_color',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('value');
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->value;
    }
}
```

### Modelo ProductVariant
```php
<?php
// app/Models/ProductVariant.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class ProductVariant extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'sku',
        'product_id',
        'price',
        'cost',
        'weight',
        'barcode',
        'qr_code',
        'is_active',
        'images',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'cost' => 'decimal:2',
        'weight' => 'decimal:3',
        'is_active' => 'boolean',
        'images' => 'array',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function inventories(): HasMany
    {
        return $this->hasMany(Inventory::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(Movement::class);
    }

    public function variantAttributes(): HasMany
    {
        return $this->hasMany(VariantAttribute::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getTotalStockAttribute(): int
    {
        return $this->inventories->sum('quantity_available');
    }

    public function getAttributesTextAttribute(): string
    {
        return $this->variantAttributes()
            ->with(['attribute', 'attributeValue'])
            ->get()
            ->map(fn($va) => $va->attributeValue->value)
            ->join(' - ');
    }

    public function generateSku(): string
    {
        $productCode = $this->product->reference_code;
        $attributes = $this->variantAttributes()
            ->with('attributeValue')
            ->get()
            ->map(fn($va) => $va->attributeValue->code)
            ->join('-');

        return strtoupper("{$productCode}-{$attributes}");
    }

    public function calculatePrice(): float
    {
        $basePrice = $this->product->base_price;

        // Apply price rules
        $rules = PriceRule::where(function ($query) {
            $query->whereNull('product_id')
                ->orWhere('product_id', $this->product_id);
        })
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('start_date')
                    ->orWhere('start_date', '<=', now());
            })
            ->where(function ($query) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>=', now());
            })
            ->orderBy('priority')
            ->get();

        $finalPrice = $basePrice;

        foreach ($rules as $rule) {
            if ($this->matchesRule($rule)) {
                if ($rule->modifier_type === 'percentage') {
                    $finalPrice *= (1 + $rule->modifier_value / 100);
                } else {
                    $finalPrice += $rule->modifier_value;
                }
            }
        }

        return $finalPrice;
    }

    private function matchesRule(PriceRule $rule): bool
    {
        if (!$rule->attribute_id || !$rule->attribute_value_id) {
            return true;
        }

        return $this->variantAttributes()
            ->where('attribute_id', $rule->attribute_id)
            ->where('attribute_value_id', $rule->attribute_value_id)
            ->exists();
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('variant-images')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }
}
```

### Modelo VariantAttribute
```php
<?php
// app/Models/VariantAttribute.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VariantAttribute extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_variant_id',
        'attribute_id',
        'attribute_value_id',
    ];

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class);
    }

    public function attributeValue(): BelongsTo
    {
        return $this->belongsTo(AttributeValue::class);
    }
}
```

### Modelo Inventory
```php
<?php
// app/Models/Inventory.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Inventory extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_variant_id',
        'store_id',
        'quantity_available',
        'quantity_reserved',
        'quantity_in_transit',
        'min_quantity',
        'max_quantity',
        'reorder_point',
        'location',
        'last_restock_date',
        'last_sale_date',
        'last_inventory_check_date',
    ];

    protected $casts = [
        'quantity_available' => 'integer',
        'quantity_reserved' => 'integer',
        'quantity_in_transit' => 'integer',
        'min_quantity' => 'integer',
        'max_quantity' => 'integer',
        'reorder_point' => 'integer',
        'last_restock_date' => 'date',
        'last_sale_date' => 'date',
        'last_inventory_check_date' => 'date',
    ];

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(Movement::class);
    }

    public function getTotalQuantityAttribute(): int
    {
        return $this->quantity_available + $this->quantity_reserved + $this->quantity_in_transit;
    }

    public function getNeedsRestockAttribute(): bool
    {
        return $this->quantity_available <= $this->reorder_point;
    }

    public function getIsLowStockAttribute(): bool
    {
        if (!$this->min_quantity) {
            return false;
        }

        return $this->quantity_available <= $this->min_quantity;
    }

    public function getIsOverstockedAttribute(): bool
    {
        if (!$this->max_quantity) {
            return false;
        }

        return $this->quantity_available >= $this->max_quantity;
    }

    public function scopeLowStock($query)
    {
        return $query->whereNotNull('min_quantity')
            ->whereColumn('quantity_available', '<=', 'min_quantity');
    }

    public function scopeNeedsRestock($query)
    {
        return $query->where('reorder_point', '>', 0)
            ->whereColumn('quantity_available', '<=', 'reorder_point');
    }
}
```

### Modelo Movement
```php
<?php
// app/Models/Movement.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Movement extends Model
{
    use HasFactory;

    const TYPE_PURCHASE = 'purchase';
    const TYPE_SALE = 'sale';
    const TYPE_TRANSFER = 'transfer';
    const TYPE_ADJUSTMENT = 'adjustment';
    const TYPE_RETURN = 'return';
    const TYPE_DAMAGE = 'damage';
    const TYPE_PRODUCTION = 'production';

    protected $fillable = [
        'type',
        'product_variant_id',
        'inventory_id',
        'store_id',
        'destination_store_id',
        'quantity',
        'quantity_before',
        'quantity_after',
        'unit_cost',
        'total_cost',
        'reference_document',
        'customer_id',
        'supplier_id',
        'user_id',
        'notes',
        'batch_number',
        'expiration_date',
        'metadata',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'quantity_before' => 'integer',
        'quantity_after' => 'integer',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'expiration_date' => 'date',
        'metadata' => 'array',
    ];

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function inventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function destinationStore(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'destination_store_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeInPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    public function getIsPositiveAttribute(): bool
    {
        return in_array($this->type, [
            self::TYPE_PURCHASE,
            self::TYPE_RETURN,
            self::TYPE_PRODUCTION,
        ]) || ($this->type === self::TYPE_ADJUSTMENT && $this->quantity > 0);
    }

    public function getIsNegativeAttribute(): bool
    {
        return in_array($this->type, [
            self::TYPE_SALE,
            self::TYPE_DAMAGE,
        ]) || ($this->type === self::TYPE_ADJUSTMENT && $this->quantity < 0);
    }

    public function getTypeColorAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_PURCHASE => 'success',
            self::TYPE_SALE => 'info',
            self::TYPE_TRANSFER => 'warning',
            self::TYPE_ADJUSTMENT => 'gray',
            self::TYPE_RETURN => 'success',
            self::TYPE_DAMAGE => 'danger',
            self::TYPE_PRODUCTION => 'primary',
            default => 'secondary',
        };
    }
}
```

### Modelo PriceRule
```php
<?php
// app/Models/PriceRule.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PriceRule extends Model
{
    use HasFactory;

    const TYPE_PERCENTAGE = 'percentage';
    const TYPE_FIXED_AMOUNT = 'fixed_amount';

    protected $fillable = [
        'name',
        'product_id',
        'attribute_id',
        'attribute_value_id',
        'modifier_type',
        'modifier_value',
        'priority',
        'start_date',
        'end_date',
        'is_active',
    ];

    protected $casts = [
        'modifier_value' => 'decimal:2',
        'priority' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class);
    }

    public function attributeValue(): BelongsTo
    {
        return $this->belongsTo(AttributeValue::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeCurrent($query)
    {
        $now = now();
        return $query->where('is_active', true)
            ->where(function ($q) use ($now) {
                $q->whereNull('start_date')
                    ->orWhere('start_date', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', $now);
            });
    }

    public function getFormattedModifierAttribute(): string
    {
        if ($this->modifier_type === self::TYPE_PERCENTAGE) {
            return "{$this->modifier_value}%";
        }

        return "$" . number_format($this->modifier_value, 2);
    }
}
```

## PARTE 3: SEEDERS

### DatabaseSeeder Principal
```php
<?php
// database/seeders/DatabaseSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            StoreSeeder::class,
            UserSeeder::class,
            SeasonSeeder::class,
            CategorySeeder::class,
            AttributeSeeder::class,
            ProductSeeder::class,
            InventorySeeder::class,
            MovementSeeder::class,
        ]);
    }
}
```

### RolesAndPermissionsSeeder
```php
<?php
// database/seeders/RolesAndPermissionsSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // User management
            'view_users',
            'create_users',
            'edit_users',
            'delete_users',

            // Store management
            'view_stores',
            'create_stores',
            'edit_stores',
            'delete_stores',
            'view_all_stores',

            // Product management
            'view_products',
            'create_products',
            'edit_products',
            'delete_products',
            'manage_prices',

            // Inventory management
            'view_inventory',
            'edit_inventory',
            'adjust_inventory',
            'transfer_inventory',
            'view_all_inventory',

            // Movement management
            'view_movements',
            'create_movements',
            'edit_movements',
            'delete_movements',
            'approve_transfers',

            // Reports
            'view_reports',
            'export_reports',
            'view_financial_reports',

            // System settings
            'manage_settings',
            'manage_seasons',
            'manage_categories',
            'manage_attributes',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create roles and assign permissions

        // Owner - Full access
        $owner = Role::create(['name' => 'owner']);
        $owner->givePermissionTo(Permission::all());

        // Admin - Almost full access
        $admin = Role::create(['name' => 'admin']);
        $admin->givePermissionTo(Permission::all());

        // Inventory Manager - Manage inventory across all stores
        $inventoryManager = Role::create(['name' => 'inventory_manager']);
        $inventoryManager->givePermissionTo([
            'view_stores',
            'view_all_stores',
            'view_products',
            'create_products',
            'edit_products',
            'manage_prices',
            'view_inventory',
            'edit_inventory',
            'adjust_inventory',
            'transfer_inventory',
            'view_all_inventory',
            'view_movements',
            'create_movements',
            'approve_transfers',
            'view_reports',
            'export_reports',
            'manage_categories',
            'manage_attributes',
        ]);

        // Store Supervisor - Manage single store
        $storeSupervisor = Role::create(['name' => 'store_supervisor']);
        $storeSupervisor->givePermissionTo([
            'view_stores',
            'view_products',
            'view_inventory',
            'edit_inventory',
            'adjust_inventory',
            'transfer_inventory',
            'view_movements',
            'create_movements',
            'view_reports',
        ]);

        // Warehouse Operator - Basic inventory operations
        $warehouseOperator = Role::create(['name' => 'warehouse_operator']);
        $warehouseOperator->givePermissionTo([
            'view_products',
            'view_inventory',
            'edit_inventory',
            'view_movements',
            'create_movements',
        ]);

        // Seller - Read only access
        $seller = Role::create(['name' => 'seller']);
        $seller->givePermissionTo([
            'view_products',
            'view_inventory',
        ]);
    }
}
```

### StoreSeeder
```php
<?php
// database/seeders/StoreSeeder.php

namespace Database\Seeders;

use App\Models\Store;
use Illuminate\Database\Seeder;

class StoreSeeder extends Seeder
{
    public function run(): void
    {
        $stores = [
            [
                'code' => 'STORE-001',
                'name' => 'Tienda Centro',
                'address' => 'Cra. 13 #85-15, Local 201',
                'city' => 'Bogotá',
                'state' => 'Bogotá D.C.',
                'country' => 'Colombia',
                'postal_code' => '110221',
                'phone_number' => '+57 1 6234567',
                'email' => 'centro@inventory.com',
                'is_active' => true,
                'manager_name' => 'Carlos Rodríguez',
                'latitude' => 4.669764,
                'longitude' => -74.054596,
                'max_capacity' => 15000,
            ],
            [
                'code' => 'STORE-002',
                'name' => 'Tienda Norte',
                'address' => 'Av. 19 #120-71',
                'city' => 'Bogotá',
                'state' => 'Bogotá D.C.',
                'country' => 'Colombia',
                'postal_code' => '110111',
                'phone_number' => '+57 1 6234568',
                'email' => 'norte@inventory.com',
                'is_active' => true,
                'manager_name' => 'María González',
                'latitude' => 4.695234,
                'longitude' => -74.041230,
                'max_capacity' => 12000,
            ],
            [
                'code' => 'STORE-003',
                'name' => 'Tienda Sur',
                'address' => 'Cll. 140 #7-19',
                'city' => 'Bogotá',
                'state' => 'Bogotá D.C.',
                'country' => 'Colombia',
                'postal_code' => '110131',
                'phone_number' => '+57 1 6234569',
                'email' => 'sur@inventory.com',
                'is_active' => true,
                'manager_name' => 'Pedro Martínez',
                'latitude' => 4.625678,
                'longitude' => -74.072345,
                'max_capacity' => 10000,
            ],
        ];

        foreach ($stores as $store) {
            Store::create($store);
        }
    }
}
```

### UserSeeder
```php
<?php
// database/seeders/UserSeeder.php

namespace Database\Seeders;

use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $stores = Store::all();

        // Owner
        $owner = User::create([
            'name' => 'owner',
            'first_name' => 'Juan',
            'last_name' => 'Pérez',
            'email' => 'owner@inventory.com',
            'password' => Hash::make('password'),
            'phone_number' => '+57 300 1234567',
            'is_active' => true,
        ]);
        $owner->assignRole('owner');

        // Admin
        $admin = User::create([
            'name' => 'admin',
            'first_name' => 'Ana',
            'last_name' => 'García',
            'email' => 'admin@inventory.com',
            'password' => Hash::make('password'),
            'phone_number' => '+57 300 2345678',
            'is_active' => true,
        ]);
        $admin->assignRole('admin');

        // Inventory Manager
        $inventoryManager = User::create([
            'name' => 'inventory_manager',
            'first_name' => 'Luis',
            'last_name' => 'Ramírez',
            'email' => 'inventory@inventory.com',
            'password' => Hash::make('password'),
            'phone_number' => '+57 300 3456789',
            'is_active' => true,
        ]);
        $inventoryManager->assignRole('inventory_manager');

        // Store Supervisors (one per store)
        foreach ($stores as $index => $store) {
            $supervisor = User::create([
                'name' => 'supervisor_' . strtolower($store->code),
                'first_name' => 'Supervisor',
                'last_name' => $store->name,
                'email' => 'supervisor' . ($index + 1) . '@inventory.com',
                'password' => Hash::make('password'),
                'phone_number' => '+57 300 456789' . $index,
                'is_active' => true,
                'assigned_store_id' => $store->id,
            ]);
            $supervisor->assignRole('store_supervisor');
        }

        // Warehouse Operators
        $warehouseOperator1 = User::create([
            'name' => 'operator1',
            'first_name' => 'Carlos',
            'last_name' => 'López',
            'email' => 'operator1@inventory.com',
            'password' => Hash::make('password'),
            'phone_number' => '+57 300 5678901',
            'is_active' => true,
            'assigned_store_id' => $stores->first()->id,
        ]);
        $warehouseOperator1->assignRole('warehouse_operator');

        $warehouseOperator2 = User::create([
            'name' => 'operator2',
            'first_name' => 'Diana',
            'last_name' => 'Torres',
            'email' => 'operator2@inventory.com',
            'password' => Hash::make('password'),
            'phone_number' => '+57 300 6789012',
            'is_active' => true,
            'assigned_store_id' => $stores->skip(1)->first()->id,
        ]);
        $warehouseOperator2->assignRole('warehouse_operator');

        // Sellers
        $seller1 = User::create([
            'name' => 'seller1',
            'first_name' => 'Roberto',
            'last_name' => 'Mendoza',
            'email' => 'seller1@inventory.com',
            'password' => Hash::make('password'),
            'phone_number' => '+57 300 7890123',
            'is_active' => true,
            'assigned_store_id' => $stores->first()->id,
        ]);
        $seller1->assignRole('seller');

        $seller2 = User::create([
            'name' => 'seller2',
            'first_name' => 'Patricia',
            'last_name' => 'Vargas',
            'email' => 'seller2@inventory.com',
            'password' => Hash::make('password'),
            'phone_number' => '+57 300 8901234',
            'is_active' => true,
            'assigned_store_id' => $stores->last()->id,
        ]);
        $seller2->assignRole('seller');
    }
}
```

### SeasonSeeder
```php
<?php
// database/seeders/SeasonSeeder.php

namespace Database\Seeders;

use App\Models\Season;
use Illuminate\Database\Seeder;

class SeasonSeeder extends Seeder
{
    public function run(): void
    {
        $seasons = [
            [
                'code' => 'SUMMER-2024',
                'name' => 'Verano 2024',
                'description' => 'Colección de verano con tejidos frescos y colores vibrantes',
                'start_date' => '2023-12-01',
                'end_date' => '2024-03-31',
                'is_active' => true,
                'metadata' => [
                    'target_audience' => 'Jóvenes y adultos',
                    'theme' => 'Tropical Paradise',
                    'notes' => 'Enfoque en materiales ligeros y transpirables',
                ],
            ],
            [
                'code' => 'FALL-2024',
                'name' => 'Otoño 2024',
                'description' => 'Colección de otoño con tonos tierra y tejidos cálidos',
                'start_date' => '2024-04-01',
                'end_date' => '2024-06-30',
                'is_active' => true,
                'metadata' => [
                    'target_audience' => 'Todos los públicos',
                    'theme' => 'Urban Comfort',
                    'notes' => 'Transición entre temporadas',
                ],
            ],
            [
                'code' => 'WINTER-2024',
                'name' => 'Invierno 2024',
                'description' => 'Colección de invierno con prendas abrigadas',
                'start_date' => '2024-07-01',
                'end_date' => '2024-09-30',
                'is_active' => true,
                'metadata' => [
                    'target_audience' => 'Familias',
                    'theme' => 'Cozy Winter',
                    'notes' => 'Materiales térmicos y diseños clásicos',
                ],
            ],
            [
                'code' => 'SPRING-2024',
                'name' => 'Primavera 2024',
                'description' => 'Colección de primavera con diseños florales',
                'start_date' => '2024-10-01',
                'end_date' => '2024-11-30',
                'is_active' => false,
                'metadata' => [
                    'target_audience' => 'Mujeres y niños',
                    'theme' => 'Bloom',
                    'notes' => 'Colores pasteles y estampados florales',
                ],
            ],
        ];

        foreach ($seasons as $season) {
            Season::create($season);
        }
    }
}
```

### CategorySeeder
```php
<?php
// database/seeders/CategorySeeder.php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'code' => 'SHIRTS',
                'name' => 'Camisas',
                'description' => 'Camisas para hombre y mujer',
                'parent_id' => null,
                'sort_order' => 1,
            ],
            [
                'code' => 'PANTS',
                'name' => 'Pantalones',
                'description' => 'Pantalones de todos los estilos',
                'parent_id' => null,
                'sort_order' => 2,
            ],
            [
                'code' => 'SWEATERS',
                'name' => 'Sudaderas',
                'description' => 'Sudaderas y hoodies',
                'parent_id' => null,
                'sort_order' => 3,
            ],
            [
                'code' => 'ACCESSORIES',
                'name' => 'Accesorios',
                'description' => 'Complementos de moda',
                'parent_id' => null,
                'sort_order' => 4,
            ],
        ];

        foreach ($categories as $categoryData) {
            $category = Category::create($categoryData);

            // Create subcategories
            if ($category->code === 'SHIRTS') {
                Category::create([
                    'code' => 'POLO',
                    'name' => 'Polos',
                    'description' => 'Camisas tipo polo',
                    'parent_id' => $category->id,
                    'sort_order' => 1,
                ]);
                Category::create([
                    'code' => 'T-SHIRT',
                    'name' => 'Camisetas',
                    'description' => 'Camisetas básicas',
                    'parent_id' => $category->id,
                    'sort_order' => 2,
                ]);
            }

            if ($category->code === 'PANTS') {
                Category::create([
                    'code' => 'JEANS',
                    'name' => 'Jeans',
                    'description' => 'Pantalones de mezclilla',
                    'parent_id' => $category->id,
                    'sort_order' => 1,
                ]);
                Category::create([
                    'code' => 'CARGO',
                    'name' => 'Cargo',
                    'description' => 'Pantalones cargo',
                    'parent_id' => $category->id,
                    'sort_order' => 2,
                ]);
            }
        }
    }
}
```

### AttributeSeeder
```php
<?php
// database/seeders/AttributeSeeder.php

namespace Database\Seeders;

use App\Models\Attribute;
use App\Models\AttributeValue;
use Illuminate\Database\Seeder;

class AttributeSeeder extends Seeder
{
    public function run(): void
    {
        // Size attribute
        $size = Attribute::create([
            'code' => 'SIZE',
            'name' => 'Talla',
            'data_type' => 'text',
            'is_required' => true,
            'sort_order' => 1,
        ]);

        $sizes = ['XS', 'S', 'M', 'L', 'XL', 'XXL'];
        foreach ($sizes as $index => $sizeValue) {
            AttributeValue::create([
                'attribute_id' => $size->id,
                'value' => $sizeValue,
                'code' => $sizeValue,
                'sort_order' => $index,
                'is_active' => true,
            ]);
        }

        // Color attribute
        $color = Attribute::create([
            'code' => 'COLOR',
            'name' => 'Color',
            'data_type' => 'color',
            'is_required' => true,
            'sort_order' => 2,
        ]);

        $colors = [
            ['value' => 'Negro', 'code' => 'BLK', 'hex' => '#000000'],
            ['value' => 'Blanco', 'code' => 'WHT', 'hex' => '#FFFFFF'],
            ['value' => 'Azul', 'code' => 'BLU', 'hex' => '#0000FF'],
            ['value' => 'Rojo', 'code' => 'RED', 'hex' => '#FF0000'],
            ['value' => 'Verde', 'code' => 'GRN', 'hex' => '#00FF00'],
            ['value' => 'Gris', 'code' => 'GRY', 'hex' => '#808080'],
            ['value' => 'Navy', 'code' => 'NVY', 'hex' => '#000080'],
            ['value' => 'Beige', 'code' => 'BGE', 'hex' => '#F5F5DC'],
        ];

        foreach ($colors as $index => $colorData) {
            AttributeValue::create([
                'attribute_id' => $color->id,
                'value' => $colorData['value'],
                'code' => $colorData['code'],
                'hex_color' => $colorData['hex'],
                'sort_order' => $index,
                'is_active' => true,
            ]);
        }

        // Material attribute
        $material = Attribute::create([
            'code' => 'MATERIAL',
            'name' => 'Material',
            'data_type' => 'text',
            'is_required' => true,
            'sort_order' => 3,
        ]);

        $materials = [
            ['value' => 'Algodón', 'code' => 'ALG'],
            ['value' => 'Poliéster', 'code' => 'POL'],
            ['value' => 'Lino', 'code' => 'LIN'],
            ['value' => 'Lana', 'code' => 'LAN'],
            ['value' => 'Mezclilla', 'code' => 'MEZ'],
            ['value' => 'Sintético', 'code' => 'SIN'],
        ];

        foreach ($materials as $index => $materialData) {
            AttributeValue::create([
                'attribute_id' => $material->id,
                'value' => $materialData['value'],
                'code' => $materialData['code'],
                'sort_order' => $index,
                'is_active' => true,
            ]);
        }
    }
}
```

### ProductSeeder
```php
<?php
// database/seeders/ProductSeeder.php

namespace Database\Seeders;

use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Category;
use App\Models\PriceRule;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Season;
use App\Models\VariantAttribute;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $summerSeason = Season::where('code', 'SUMMER-2024')->first();
        $poloCategory = Category::where('code', 'POLO')->first();
        $jeansCategory = Category::where('code', 'JEANS')->first();
        $sweatersCategory = Category::where('code', 'SWEATERS')->first();

        // Product 1: Camiseta Polo Medin
        $product1 = Product::create([
            'reference_code' => 'POLO-MEDIN',
            'name' => 'Camiseta Polo Medin',
            'description' => 'Camiseta polo clásica con diseño moderno, perfecta para ocasiones casuales y semi-formales',
            'season_id' => $summerSeason->id,
            'category_id' => $poloCategory->id,
            'base_price' => 250000,
            'cost' => 150000,
            'brand' => 'Urban Style',
            'supplier' => 'Textiles Colombia S.A.',
            'is_active' => true,
            'tags' => ['nuevo', 'popular', 'verano'],
            'specifications' => [
                'weight' => 0.25,
                'dimensions' => [
                    'length' => 70,
                    'width' => 50,
                    'height' => 2,
                ],
                'care_instructions' => 'Lavar a máquina con agua fría',
                'origin' => 'Colombia',
            ],
        ]);

        // Product 2: Pantalón Cargo Style
        $product2 = Product::create([
            'reference_code' => 'CARGO-STYLE',
            'name' => 'Pantalón Cargo Style',
            'description' => 'Pantalón cargo con múltiples bolsillos, ideal para un look urbano',
            'season_id' => $summerSeason->id,
            'category_id' => $jeansCategory->id,
            'base_price' => 320000,
            'cost' => 200000,
            'brand' => 'Street Wear',
            'supplier' => 'Confecciones Bogotá',
            'is_active' => true,
            'tags' => ['trending', 'cargo', 'urbano'],
            'specifications' => [
                'weight' => 0.45,
                'dimensions' => [
                    'length' => 100,
                    'width' => 35,
                    'height' => 3,
                ],
                'care_instructions' => 'Lavar a mano o máquina con agua tibia',
                'origin' => 'Colombia',
            ],
        ]);

        // Product 3: Sudadera Urban
        $product3 = Product::create([
            'reference_code' => 'SUDADERA-URBAN',
            'name' => 'Sudadera Urban',
            'description' => 'Sudadera con capucha, diseño moderno y cómodo',
            'season_id' => $summerSeason->id,
            'category_id' => $sweatersCategory->id,
            'base_price' => 280000,
            'cost' => 180000,
            'brand' => 'Comfort Zone',
            'supplier' => 'Importaciones Fashion',
            'is_active' => true,
            'tags' => ['comfort', 'casual', 'hoodie'],
            'specifications' => [
                'weight' => 0.5,
                'dimensions' => [
                    'length' => 75,
                    'width' => 55,
                    'height' => 5,
                ],
                'care_instructions' => 'Lavar del revés con agua fría',
                'origin' => 'Colombia',
            ],
        ]);

        // Create price rules
        PriceRule::create([
            'name' => 'Material Premium - Lino',
            'product_id' => null, // Applies to all products
            'attribute_id' => Attribute::where('code', 'MATERIAL')->first()->id,
            'attribute_value_id' => AttributeValue::where('code', 'LIN')->first()->id,
            'modifier_type' => 'percentage',
            'modifier_value' => 30,
            'priority' => 1,
            'is_active' => true,
        ]);

        PriceRule::create([
            'name' => 'Talla XXL',
            'product_id' => null,
            'attribute_id' => Attribute::where('code', 'SIZE')->first()->id,
            'attribute_value_id' => AttributeValue::where('code', 'XXL')->first()->id,
            'modifier_type' => 'fixed_amount',
            'modifier_value' => 40000,
            'priority' => 2,
            'is_active' => true,
        ]);

        // Create variants for each product
        $this->createVariantsForProduct($product1);
        $this->createVariantsForProduct($product2);
        $this->createVariantsForProduct($product3);
    }

    private function createVariantsForProduct(Product $product): void
    {
        $sizes = AttributeValue::where('attribute_id', Attribute::where('code', 'SIZE')->first()->id)
            ->whereIn('code', ['S', 'M', 'L', 'XL'])
            ->get();

        $colors = AttributeValue::where('attribute_id', Attribute::where('code', 'COLOR')->first()->id)
            ->whereIn('code', ['BLK', 'WHT', 'BLU', 'GRY'])
            ->get();

        $materials = AttributeValue::where('attribute_id', Attribute::where('code', 'MATERIAL')->first()->id)
            ->whereIn('code', ['ALG', 'POL'])
            ->get();

        foreach ($sizes as $size) {
            foreach ($colors as $color) {
                foreach ($materials as $material) {
                    $variant = ProductVariant::create([
                        'sku' => $this->generateSku($product->reference_code, [$size, $color, $material]),
                        'product_id' => $product->id,
                        'price' => $this->calculateVariantPrice($product->base_price, [$size, $color, $material]),
                        'cost' => $product->cost,
                        'weight' => 0.3,
                        'barcode' => $this->generateBarcode(),
                        'is_active' => true,
                    ]);

                    // Create variant attributes
                    VariantAttribute::create([
                        'product_variant_id' => $variant->id,
                        'attribute_id' => $size->attribute_id,
                        'attribute_value_id' => $size->id,
                    ]);

                    VariantAttribute::create([
                        'product_variant_id' => $variant->id,
                        'attribute_id' => $color->attribute_id,
                        'attribute_value_id' => $color->id,
                    ]);

                    VariantAttribute::create([
                        'product_variant_id' => $variant->id,
                        'attribute_id' => $material->attribute_id,
                        'attribute_value_id' => $material->id,
                    ]);
                }
            }
        }
    }

    private function generateSku(string $productCode, array $attributeValues): string
    {
        $codes = array_map(fn($av) => $av->code, $attributeValues);
        return strtoupper($productCode . '-' . implode('-', $codes));
    }

    private function calculateVariantPrice(float $basePrice, array $attributeValues): float
    {
        $price = $basePrice;

        // Apply simple rules for demo
        foreach ($attributeValues as $value) {
            if ($value->code === 'XXL') {
                $price += 40000;
            }
            if ($value->code === 'LIN') {
                $price *= 1.3;
            }
        }

        return $price;
    }

    private function generateBarcode(): string
    {
        return '750' . str_pad(rand(1, 999999999), 9, '0', STR_PAD_LEFT);
    }
}
```

### InventorySeeder
```php
<?php
// database/seeders/InventorySeeder.php

namespace Database\Seeders;

use App\Models\Inventory;
use App\Models\ProductVariant;
use App\Models\Store;
use Illuminate\Database\Seeder;

class InventorySeeder extends Seeder
{
    public function run(): void
    {
        $stores = Store::all();
        $variants = ProductVariant::all();

        foreach ($stores as $store) {
            foreach ($variants as $variant) {
                $baseQuantity = rand(0, 100);

                Inventory::create([
                    'product_variant_id' => $variant->id,
                    'store_id' => $store->id,
                    'quantity_available' => $baseQuantity,
                    'quantity_reserved' => rand(0, 10),
                    'quantity_in_transit' => rand(0, 5),
                    'min_quantity' => 10,
                    'max_quantity' => 200,
                    'reorder_point' => 20,
                    'location' => $this->generateLocation(),
                    'last_restock_date' => now()->subDays(rand(1, 30)),
                    'last_sale_date' => now()->subDays(rand(1, 7)),
                    'last_inventory_check_date' => now()->subDays(rand(1, 15)),
                ]);
            }
        }
    }

    private function generateLocation(): string
    {
        $zone = ['A', 'B', 'C', 'D'][rand(0, 3)];
        $rack = rand(1, 10);
        $level = rand(1, 5);

        return "{$zone}{$rack}-{$level}";
    }
}
```

### MovementSeeder
```php
<?php
// database/seeders/MovementSeeder.php

namespace Database\Seeders;

use App\Models\Inventory;
use App\Models\Movement;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Seeder;

class MovementSeeder extends Seeder
{
    public function run(): void
    {
        $inventories = Inventory::with(['productVariant', 'store'])->limit(50)->get();
        $users = User::all();

        foreach ($inventories as $inventory) {
            // Create some purchase movements
            for ($i = 0; $i < rand(1, 3); $i++) {
                $quantity = rand(50, 200);
                $quantityBefore = $inventory->quantity_available;

                Movement::create([
                    'type' => Movement::TYPE_PURCHASE,
                    'product_variant_id' => $inventory->product_variant_id,
                    'inventory_id' => $inventory->id,
                    'store_id' => $inventory->store_id,
                    'quantity' => $quantity,
                    'quantity_before' => $quantityBefore,
                    'quantity_after' => $quantityBefore + $quantity,
                    'unit_cost' => $inventory->productVariant->cost,
                    'total_cost' => $inventory->productVariant->cost * $quantity,
                    'reference_document' => 'OC-2024-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT),
                    'supplier_id' => 'SUPP-' . rand(1, 10),
                    'user_id' => $users->random()->id,
                    'notes' => 'Compra regular de inventario',
                    'created_at' => now()->subDays(rand(30, 60)),
                ]);
            }

            // Create some sale movements
            for ($i = 0; $i < rand(2, 5); $i++) {
                $quantity = rand(1, 20);
                $quantityBefore = $inventory->quantity_available;

                if ($quantityBefore >= $quantity) {
                    Movement::create([
                        'type' => Movement::TYPE_SALE,
                        'product_variant_id' => $inventory->product_variant_id,
                        'inventory_id' => $inventory->id,
                        'store_id' => $inventory->store_id,
                        'quantity' => -$quantity,
                        'quantity_before' => $quantityBefore,
                        'quantity_after' => $quantityBefore - $quantity,
                        'unit_cost' => $inventory->productVariant->price,
                        'total_cost' => $inventory->productVariant->price * $quantity,
                        'reference_document' => 'FACT-2024-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT),
                        'customer_id' => 'CUST-' . rand(1, 100),
                        'user_id' => $users->random()->id,
                        'notes' => 'Venta al por mayor',
                        'metadata' => [
                            'customer_name' => 'Cliente ' . rand(1, 100),
                            'payment_method' => ['cash', 'credit', 'transfer'][rand(0, 2)],
                        ],
                        'created_at' => now()->subDays(rand(1, 30)),
                    ]);
                }
            }
        }

        // Create some transfer movements
        $stores = Store::all();
        for ($i = 0; $i < 10; $i++) {
            $sourceInventory = Inventory::where('quantity_available', '>', 50)->inRandomOrder()->first();
            if (!$sourceInventory) continue;

            $destinationStore = $stores->where('id', '!=', $sourceInventory->store_id)->random();
            $destinationInventory = Inventory::where('store_id', $destinationStore->id)
                ->where('product_variant_id', $sourceInventory->product_variant_id)
                ->first();

            if ($destinationInventory) {
                $quantity = rand(5, 20);

                // Source movement (negative)
                Movement::create([
                    'type' => Movement::TYPE_TRANSFER,
                    'product_variant_id' => $sourceInventory->product_variant_id,
                    'inventory_id' => $sourceInventory->id,
                    'store_id' => $sourceInventory->store_id,
                    'destination_store_id' => $destinationStore->id,
                    'quantity' => -$quantity,
                    'quantity_before' => $sourceInventory->quantity_available,
                    'quantity_after' => $sourceInventory->quantity_available - $quantity,
                    'reference_document' => 'TRANS-2024-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT),
                    'user_id' => $users->random()->id,
                    'notes' => 'Transferencia entre tiendas',
                    'metadata' => [
                        'transfer_reason' => 'Reabastecimiento',
                        'authorized_by' => 'Gerente de Inventario',
                    ],
                    'created_at' => now()->subDays(rand(1, 15)),
                ]);

                // Destination movement (positive)
                Movement::create([
                    'type' => Movement::TYPE_TRANSFER,
                    'product_variant_id' => $destinationInventory->product_variant_id,
                    'inventory_id' => $destinationInventory->id,
                    'store_id' => $destinationStore->id,
                    'destination_store_id' => $sourceInventory->store_id,
                    'quantity' => $quantity,
                    'quantity_before' => $destinationInventory->quantity_available,
                    'quantity_after' => $destinationInventory->quantity_available + $quantity,
                    'reference_document' => 'TRANS-2024-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT),
                    'user_id' => $users->random()->id,
                    'notes' => 'Recepción de transferencia',
                    'created_at' => now()->subDays(rand(1, 15)),
                ]);
            }
        }
    }
}
```

## PARTE 4: RECURSOS FILAMENT

### Configuración de Filament Panel
```php
<?php
// app/Providers/Filament/AdminPanelProvider.php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->colors([
                'primary' => Color::Amber,
            ])
            ->brandName('Sistema de Inventario')
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->navigationGroups([
                'Gestión de Tiendas',
                'Gestión de Productos',
                'Inventario',
                'Configuración',
                'Usuarios y Roles',
            ])
            ->sidebarCollapsibleOnDesktop()
            ->maxContentWidth('full')
            ->spa();
    }
}
```

### ProductResource
```php
<?php
// app/Filament/Resources/ProductResource.php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $navigationGroup = 'Gestión de Productos';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información Básica')
                    ->schema([
                        Forms\Components\TextInput::make('reference_code')
                            ->label('Código de Referencia')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('season_id')
                            ->label('Temporada')
                            ->relationship('season', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('category_id')
                            ->label('Categoría')
                            ->relationship('category', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Precios y Costos')
                    ->schema([
                        Forms\Components\TextInput::make('base_price')
                            ->label('Precio Base')
                            ->required()
                            ->numeric()
                            ->prefix('$'),
                        Forms\Components\TextInput::make('cost')
                            ->label('Costo')
                            ->required()
                            ->numeric()
                            ->prefix('$'),
                        Forms\Components\TextInput::make('brand')
                            ->label('Marca')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('supplier')
                            ->label('Proveedor')
                            ->maxLength(255),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Detalles')
                    ->schema([
                        Forms\Components\Textarea::make('description')
                            ->label('Descripción')
                            ->columnSpanFull(),
                        Forms\Components\TagsInput::make('tags')
                            ->label('Etiquetas'),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Activo')
                            ->required()
                            ->default(true),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference_code')
                    ->label('Código')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('season.name')
                    ->label('Temporada')
                    ->sortable(),
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Categoría')
                    ->sortable(),
                Tables\Columns\TextColumn::make('base_price')
                    ->label('Precio Base')
                    ->money('COP')
                    ->sortable(),
                Tables\Columns\TextColumn::make('variant_count')
                    ->label('Variantes')
                    ->counts('variants'),
                Tables\Columns\TextColumn::make('total_stock')
                    ->label('Stock Total')
                    ->getStateUsing(fn (Product $record): int =>
                        $record->variants->sum(fn ($variant) =>
                            $variant->inventories->sum('quantity_available')
                        )
                    ),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('season')
                    ->relationship('season', 'name'),
                Tables\Filters\SelectFilter::make('category')
                    ->relationship('category', 'name'),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Estado'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\VariantsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'view' => Pages\ViewProduct::route('/{record}'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
```

### InventoryResource
```php
<?php
// app/Filament/Resources/InventoryResource.php

namespace App\Filament\Resources;

use App\Filament\Resources\InventoryResource\Pages;
use App\Models\Inventory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InventoryResource extends Resource
{
    protected static ?string $model = Inventory::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationGroup = 'Inventario';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información del Producto')
                    ->schema([
                        Forms\Components\Select::make('product_variant_id')
                            ->label('Variante de Producto')
                            ->relationship('productVariant', 'sku')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('store_id')
                            ->label('Tienda')
                            ->relationship('store', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Cantidades')
                    ->schema([
                        Forms\Components\TextInput::make('quantity_available')
                            ->label('Cantidad Disponible')
                            ->required()
                            ->numeric()
                            ->minValue(0),
                        Forms\Components\TextInput::make('quantity_reserved')
                            ->label('Cantidad Reservada')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->default(0),
                        Forms\Components\TextInput::make('quantity_in_transit')
                            ->label('Cantidad en Tránsito')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->default(0),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Configuración de Stock')
                    ->schema([
                        Forms\Components\TextInput::make('min_quantity')
                            ->label('Cantidad Mínima')
                            ->numeric()
                            ->minValue(0),
                        Forms\Components\TextInput::make('max_quantity')
                            ->label('Cantidad Máxima')
                            ->numeric()
                            ->minValue(0),
                        Forms\Components\TextInput::make('reorder_point')
                            ->label('Punto de Reorden')
                            ->numeric()
                            ->minValue(0)
                            ->default(0),
                        Forms\Components\TextInput::make('location')
                            ->label('Ubicación en Almacén')
                            ->maxLength(255),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('productVariant.sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('productVariant.product.name')
                    ->label('Producto')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('store.name')
                    ->label('Tienda')
                    ->sortable(),
                Tables\Columns\TextColumn::make('quantity_available')
                    ->label('Disponible')
                    ->sortable()
                    ->color(fn (Inventory $record): string =>
                        $record->is_low_stock ? 'danger' : 'success'
                    ),
                Tables\Columns\TextColumn::make('quantity_reserved')
                    ->label('Reservado'),
                Tables\Columns\TextColumn::make('quantity_in_transit')
                    ->label('En Tránsito'),
                Tables\Columns\TextColumn::make('total_quantity')
                    ->label('Total')
                    ->getStateUsing(fn (Inventory $record): int => $record->total_quantity),
                Tables\Columns\TextColumn::make('location')
                    ->label('Ubicación')
                    ->searchable(),
                Tables\Columns\IconColumn::make('needs_restock')
                    ->label('Reabastecer')
                    ->boolean()
                    ->trueIcon('heroicon-o-exclamation-triangle')
                    ->falseIcon('heroicon-o-check-circle'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('store')
                    ->relationship('store', 'name'),
                Tables\Filters\Filter::make('low_stock')
                    ->label('Stock Bajo')
                    ->query(fn (Builder $query): Builder => $query->lowStock()),
                Tables\Filters\Filter::make('needs_restock')
                    ->label('Necesita Reabastecimiento')
                    ->query(fn (Builder $query): Builder => $query->needsRestock()),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('adjust')
                    ->label('Ajustar')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->form([
                        Forms\Components\Select::make('type')
                            ->label('Tipo de Ajuste')
                            ->options([
                                'add' => 'Agregar',
                                'subtract' => 'Restar',
                                'set' => 'Establecer',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('quantity')
                            ->label('Cantidad')
                            ->numeric()
                            ->required()
                            ->minValue(0),
                        Forms\Components\Textarea::make('reason')
                            ->label('Razón')
                            ->required(),
                    ])
                    ->action(function (array $data, Inventory $record): void {
                        // Logic to adjust inventory
                        $adjustment = match($data['type']) {
                            'add' => $record->quantity_available + $data['quantity'],
                            'subtract' => max(0, $record->quantity_available - $data['quantity']),
                            'set' => $data['quantity'],
                        };

                        $record->update(['quantity_available' => $adjustment]);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    //
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInventories::route('/'),
            'create' => Pages\CreateInventory::route('/create'),
            'edit' => Pages\EditInventory::route('/{record}/edit'),
        ];
    }
}
```

### MovementResource
```php
<?php
// app/Filament/Resources/MovementResource.php

namespace App\Filament\Resources;

use App\Filament\Resources\MovementResource\Pages;
use App\Models\Movement;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class MovementResource extends Resource
{
    protected static ?string $model = Movement::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-right-left';

    protected static ?string $navigationGroup = 'Inventario';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información del Movimiento')
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->label('Tipo de Movimiento')
                            ->options([
                                'purchase' => 'Compra',
                                'sale' => 'Venta',
                                'transfer' => 'Transferencia',
                                'adjustment' => 'Ajuste',
                                'return' => 'Devolución',
                                'damage' => 'Daño',
                            ])
                            ->required()
                            ->reactive(),
                        Forms\Components\Select::make('product_variant_id')
                            ->label('Variante de Producto')
                            ->relationship('productVariant', 'sku')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('store_id')
                            ->label('Tienda Origen')
                            ->relationship('store', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('destination_store_id')
                            ->label('Tienda Destino')
                            ->relationship('destinationStore', 'name')
                            ->searchable()
                            ->preload()
                            ->visible(fn (callable $get) => $get('type') === 'transfer')
                            ->requiredIf('type', 'transfer'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Cantidades y Costos')
                    ->schema([
                        Forms\Components\TextInput::make('quantity')
                            ->label('Cantidad')
                            ->required()
                            ->numeric(),
                        Forms\Components\TextInput::make('unit_cost')
                            ->label('Costo Unitario')
                            ->numeric()
                            ->prefix('$'),
                        Forms\Components\TextInput::make('total_cost')
                            ->label('Costo Total')
                            ->numeric()
                            ->prefix('$'),
                        Forms\Components\TextInput::make('reference_document')
                            ->label('Documento de Referencia')
                            ->maxLength(255),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Información Adicional')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Notas')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('type')
                    ->label('Tipo')
                    ->colors([
                        'success' => 'purchase',
                        'info' => 'sale',
                        'warning' => 'transfer',
                        'gray' => 'adjustment',
                        'success' => 'return',
                        'danger' => 'damage',
                    ]),
                Tables\Columns\TextColumn::make('productVariant.sku')
                    ->label('SKU')
                    ->searchable(),
                Tables\Columns\TextColumn::make('store.name')
                    ->label('Tienda'),
                Tables\Columns\TextColumn::make('quantity')
                    ->label('Cantidad')
                    ->formatStateUsing(fn (int $state): string =>
                        ($state > 0 ? '+' : '') . $state
                    )
                    ->color(fn (int $state): string =>
                        $state > 0 ? 'success' : 'danger'
                    ),
                Tables\Columns\TextColumn::make('user.full_name')
                    ->label('Usuario'),
                Tables\Columns\TextColumn::make('reference_document')
                    ->label('Documento')
                    ->searchable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Tipo')
                    ->options([
                        'purchase' => 'Compra',
                        'sale' => 'Venta',
                        'transfer' => 'Transferencia',
                        'adjustment' => 'Ajuste',
                        'return' => 'Devolución',
                        'damage' => 'Daño',
                    ]),
                Tables\Filters\SelectFilter::make('store')
                    ->relationship('store', 'name'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                //
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMovements::route('/'),
            'create' => Pages\CreateMovement::route('/create'),
            'view' => Pages\ViewMovement::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return auth()->user()->can('create_movements');
    }
}
```

## PARTE 5: POLÍTICAS Y MIDDLEWARE

### ProductPolicy
```php
<?php
// app/Policies/ProductPolicy.php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_products');
    }

    public function view(User $user, Product $product): bool
    {
        return $user->can('view_products');
    }

    public function create(User $user): bool
    {
        return $user->can('create_products');
    }

    public function update(User $user, Product $product): bool
    {
        return $user->can('edit_products');
    }

    public function delete(User $user, Product $product): bool
    {
        return $user->can('delete_products');
    }

    public function restore(User $user, Product $product): bool
    {
        return $user->can('delete_products');
    }

    public function forceDelete(User $user, Product $product): bool
    {
        return $user->can('delete_products');
    }
}
```

### InventoryPolicy
```php
<?php
// app/Policies/InventoryPolicy.php

namespace App\Policies;

use App\Models\Inventory;
use App\Models\User;

class InventoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_inventory');
    }

    public function view(User $user, Inventory $inventory): bool
    {
        if ($user->can('view_all_inventory')) {
            return true;
        }

        return $user->can('view_inventory') &&
               $user->canAccessStore($inventory->store);
    }

    public function create(User $user): bool
    {
        return $user->can('edit_inventory');
    }

    public function update(User $user, Inventory $inventory): bool
    {
        if ($user->can('edit_inventory')) {
            if ($user->hasRole(['owner', 'admin', 'inventory_manager'])) {
                return true;
            }

            return $user->canAccessStore($inventory->store);
        }

        return false;
    }

    public function delete(User $user, Inventory $inventory): bool
    {
        return $user->hasRole(['owner', 'admin']);
    }

    public function adjust(User $user, Inventory $inventory): bool
    {
        if ($user->can('adjust_inventory')) {
            if ($user->hasRole(['owner', 'admin', 'inventory_manager'])) {
                return true;
            }

            return $user->canAccessStore($inventory->store);
        }

        return false;
    }

    public function transfer(User $user, Inventory $inventory): bool
    {
        return $user->can('transfer_inventory');
    }
}
```

### MovementPolicy
```php
<?php
// app/Policies/MovementPolicy.php

namespace App\Policies;

use App\Models\Movement;
use App\Models\User;

class MovementPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_movements');
    }

    public function view(User $user, Movement $movement): bool
    {
        if ($user->can('view_movements')) {
            if ($user->hasRole(['owner', 'admin', 'inventory_manager'])) {
                return true;
            }

            return $user->canAccessStore($movement->store);
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->can('create_movements');
    }

    public function update(User $user, Movement $movement): bool
    {
        // Only allow editing recent movements (within 24 hours)
        if ($movement->created_at->diffInHours(now()) > 24) {
            return false;
        }

        return $user->can('edit_movements') &&
               ($user->id === $movement->user_id ||
                $user->hasRole(['owner', 'admin', 'inventory_manager']));
    }

    public function delete(User $user, Movement $movement): bool
    {
        return $user->can('delete_movements') &&
               $user->hasRole(['owner', 'admin']);
    }

    public function approveTransfer(User $user, Movement $movement): bool
    {
        return $movement->type === 'transfer' &&
               $user->can('approve_transfers');
    }
}
```

### StorePolicy
```php
<?php
// app/Policies/StorePolicy.php

namespace App\Policies;

use App\Models\Store;
use App\Models\User;

class StorePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_stores');
    }

    public function view(User $user, Store $store): bool
    {
        if ($user->can('view_all_stores')) {
            return true;
        }

        return $user->can('view_stores') &&
               $user->canAccessStore($store);
    }

    public function create(User $user): bool
    {
        return $user->can('create_stores');
    }

    public function update(User $user, Store $store): bool
    {
        return $user->can('edit_stores');
    }

    public function delete(User $user, Store $store): bool
    {
        return $user->can('delete_stores');
    }
}
```

## COMANDOS DE INSTALACIÓN Y EJECUCIÓN

```bash
# 1. Clonar o crear el proyecto
composer create-project laravel/laravel inventory-system
cd inventory-system

# 2. Instalar dependencias
composer require filament/filament:"^3.2"
composer require spatie/laravel-permission
composer require spatie/laravel-sluggable
composer require spatie/laravel-medialibrary
composer require spatie/laravel-activitylog

# 3. Publicar configuraciones
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
php artisan vendor:publish --provider="Spatie\MediaLibrary\MediaLibraryServiceProvider"

# 4. Configurar base de datos en .env
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=inventory_system
# DB_USERNAME=root
# DB_PASSWORD=

# 5. Ejecutar migraciones
php artisan migrate

# 6. Ejecutar seeders
php artisan db:seed

# 7. Crear usuario administrador de Filament
php artisan make:filament-user

# 8. Limpiar cachés
php artisan optimize:clear
php artisan permission:cache-reset

# 9. Iniciar servidor
php artisan serve

# 10. Acceder al panel
# http://localhost:8000/admin
# Usar credenciales: owner@inventory.com / password
```

## ESTRUCTURA DE CARPETAS FINAL

```
inventory-system/
├── app/
│   ├── Filament/
│   │   ├── Resources/
│   │   │   ├── ProductResource.php
│   │   │   ├── ProductResource/
│   │   │   │   └── Pages/
│   │   │   ├── InventoryResource.php
│   │   │   ├── MovementResource.php
│   │   │   ├── StoreResource.php
│   │   │   ├── UserResource.php
│   │   │   └── SeasonResource.php
│   │   └── Widgets/
│   │       ├── StatsOverview.php
│   │       └── InventoryChart.php
│   ├── Models/
│   │   ├── User.php
│   │   ├── Store.php
│   │   ├── Season.php
│   │   ├── Category.php
│   │   ├── Product.php
│   │   ├── ProductVariant.php
│   │   ├── Attribute.php
│   │   ├── AttributeValue.php
│   │   ├── VariantAttribute.php
│   │   ├── Inventory.php
│   │   ├── Movement.php
│   │   └── PriceRule.php
│   └── Policies/
│       ├── ProductPolicy.php
│       ├── InventoryPolicy.php
│       ├── MovementPolicy.php
│       └── StorePolicy.php
├── database/
│   ├── migrations/
│   │   └── [todos los archivos de migración]
│   └── seeders/
│       ├── DatabaseSeeder.php
│       ├── RolesAndPermissionsSeeder.php
│       ├── StoreSeeder.php
│       ├── UserSeeder.php
│       ├── SeasonSeeder.php
│       ├── CategorySeeder.php
│       ├── AttributeSeeder.php
│       ├── ProductSeeder.php
│       ├── InventorySeeder.php
│       └── MovementSeeder.php
└── config/
    └── permission.php
```

## NOTAS ADICIONALES

1. **Seguridad**: Recuerda cambiar las contraseñas por defecto en producción
2. **Optimización**: Implementa caché para consultas frecuentes
3. **Backups**: Configura backups automáticos de la base de datos
4. **Logs**: Implementa logging detallado para auditoría
5. **API**: Considera agregar una API REST para integraciones externas
6. **Testing**: Agrega tests unitarios y de integración

Este sistema está listo para producción con todas las funcionalidades requeridas para gestionar un inventario multi-tienda con variantes de productos.
