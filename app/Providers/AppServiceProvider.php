<?php

namespace App\Providers;

use App\Models\DiscountRule;
use App\Models\Inventory;
use App\Models\Movement;
use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use App\Observers\InventoryObserver;
use App\Observers\OrderObserver;
use App\Policies\DiscountRulePolicy;
use App\Policies\InventoryPolicy;
use App\Policies\MovementPolicy;
use App\Policies\ProductPolicy;
use App\Policies\StorePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Order::observe(OrderObserver::class);
        Inventory::observe(InventoryObserver::class);

        Gate::policy(Product::class, ProductPolicy::class);
        Gate::policy(Inventory::class, InventoryPolicy::class);
        Gate::policy(Movement::class, MovementPolicy::class);
        Gate::policy(Store::class, StorePolicy::class);
        Gate::policy(DiscountRule::class, DiscountRulePolicy::class);
    }
}
