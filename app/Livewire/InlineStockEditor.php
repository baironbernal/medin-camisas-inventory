<?php

namespace App\Livewire;

use App\Models\Inventory;
use Livewire\Attributes\Locked;
use Livewire\Component;

class InlineStockEditor extends Component
{
    #[Locked]
    public int $variantId;

    /** Total quantity shown in the single editable input */
    public int $quantity = 0;

    /** Reactive total used to color the badge (stays in sync with $quantity) */
    public int $totalStock = 0;

    /** Number of inventory rows that exist for this variant */
    #[Locked]
    public int $storeCount = 0;

    public function mount(int $variantId): void
    {
        $this->variantId = $variantId;
        $this->loadInventories();
    }

    public function loadInventories(): void
    {
        $inventories = Inventory::where('product_variant_id', $this->variantId)->get();

        $this->storeCount  = $inventories->count();
        $this->quantity    = (int) $inventories->sum('quantity_available');
        $this->totalStock  = $this->quantity;
    }

    /**
     * Called on blur.
     * Distributes the new total evenly across all stores.
     * Any remainder (from integer division) is added to the first store.
     *
     * Example: 10 units across 3 stores → [4, 3, 3]
     */
    public function saveRow(): void
    {
        $newTotal = max(0, (int) $this->quantity);

        // Keep quantity clean (no negative, no floats)
        $this->quantity   = $newTotal;
        $this->totalStock = $newTotal;

        if ($this->storeCount === 0) {
            return;
        }

        $base      = intdiv($newTotal, $this->storeCount);   // floor division
        $remainder = $newTotal % $this->storeCount;           // leftover units

        $inventories = Inventory::where('product_variant_id', $this->variantId)
            ->orderBy('id')
            ->get();

        foreach ($inventories as $i => $inventory) {
            // First store absorbs the remainder
            $inventory->quantity_available = $base + ($i === 0 ? $remainder : 0);
            $inventory->save();
        }
    }

    public function render()
    {
        return view('livewire.inline-stock-editor');
    }
}
