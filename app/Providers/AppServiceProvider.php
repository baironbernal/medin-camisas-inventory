<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Discount;
use App\Models\DiscountRule;
use App\Models\HomeAd;
use App\Models\Inventory;
use App\Models\Movement;
use App\Models\Order;
use App\Models\Product;
use App\Models\Season;
use App\Models\Store;
use App\Models\User;
use App\Observers\InventoryObserver;
use App\Observers\OrderObserver;
use App\Policies\CategoryPolicy;
use App\Policies\DiscountPolicy;
use App\Policies\DiscountRulePolicy;
use App\Policies\HomeAdPolicy;
use App\Policies\InventoryPolicy;
use App\Policies\MovementPolicy;
use App\Policies\OrderPolicy;
use App\Policies\ProductPolicy;
use App\Policies\SeasonPolicy;
use App\Policies\StorePolicy;
use App\Policies\UserPolicy;
use App\Contracts\CartPricingEngineInterface;
use App\Services\CartService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Model => Policy registrations. Adding a new model? Register it here
     * (or rely on Laravel's auto-discovery for App\Models\X => App\Policies\XPolicy).
     */
    protected array $policies = [
        Product::class      => ProductPolicy::class,
        Order::class        => OrderPolicy::class,
        Category::class     => CategoryPolicy::class,
        Inventory::class    => InventoryPolicy::class,
        Movement::class     => MovementPolicy::class,
        Store::class        => StorePolicy::class,
        Season::class       => SeasonPolicy::class,
        Discount::class     => DiscountPolicy::class,
        DiscountRule::class => DiscountRulePolicy::class,
        HomeAd::class       => HomeAdPolicy::class,
        User::class         => UserPolicy::class,
    ];

    public function register(): void
    {
        $this->app->bind(CartPricingEngineInterface::class, CartService::class);
    }

    public function boot(): void
    {
        Order::observe(OrderObserver::class);
        Inventory::observe(InventoryObserver::class);

        // Register every model policy.
        foreach ($this->policies as $model => $policy) {
            Gate::policy($model, $policy);
        }

        /**
         * Super-admin short-circuit. `owner` and `admin` bypass every check.
         * Centralised here so NO policy hardcodes a role name. Returning null
         * lets the normal policy run for everyone else.
         */
        Gate::before(function (User $user, string $ability) {
            return $user->hasRole(['owner', 'admin']) ? true : null;
        });
    }
}
