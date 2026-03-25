<div class="inline-stock-editor" wire:key="variant-stock-{{ $variantId }}">

    {{-- ── Badge + single input side by side ─────────────────────────── --}}
    <div class="flex items-center justify-center gap-2">

        {{-- Editable total input --}}
        <input
            type="number"
            min="0"
            step="1"
            wire:model="quantity"
            wire:blur="saveRow()"
            tabindex="0"
            title="{{ $storeCount > 1 ? 'Se distribuirá entre ' . $storeCount . ' tiendas' : 'Stock total' }}"
            class="
                w-16 rounded border border-gray-300 bg-white px-1.5 py-0.5
                text-center text-xs font-semibold text-gray-800
                shadow-sm transition
                focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/30
                dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200
                dark:focus:border-primary-400 dark:focus:ring-primary-400/30
                [appearance:textfield]
                [&::-webkit-inner-spin-button]:appearance-none
                [&::-webkit-outer-spin-button]:appearance-none
            "
            aria-label="Stock total (se divide entre tiendas)"
        />

        {{-- Color badge --}}
        @php
            $color = $totalStock > 50
                ? 'success'
                : ($totalStock > 0 ? 'warning' : 'danger');

            $colorMap = [
                'success' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200 ring-green-600/20',
                'warning' => 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200 ring-amber-600/20',
                'danger'  => 'bg-red-100  text-red-800  dark:bg-red-900  dark:text-red-200  ring-red-600/20',
            ];
        @endphp

        <span class="inline-flex items-center rounded-md px-1.5 py-0.5 text-xs font-semibold
                     ring-1 ring-inset transition-all duration-300 {{ $colorMap[$color] }}"
              title="Total en stock">
            {{ $totalStock }}
        </span>
    </div>

    {{-- Store count hint --}}
    @if ($storeCount > 1)
        <p class="mt-0.5 text-center text-[10px] text-gray-400 dark:text-gray-500">
            ÷ {{ $storeCount }} tiendas
        </p>
    @elseif ($storeCount === 0)
        <p class="mt-0.5 text-center text-[10px] italic text-gray-400">Sin inventario</p>
    @endif

</div>
