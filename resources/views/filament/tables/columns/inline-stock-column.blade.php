{{--
    filament/tables/columns/inline-stock-column.blade.php
    Bridge view used by ViewColumn::make('inline_stock').
    $getRecord() gives us the current ProductVariant row.
--}}
@livewire('inline-stock-editor', ['variantId' => $getRecord()->id], key('ise-' . $getRecord()->id))
