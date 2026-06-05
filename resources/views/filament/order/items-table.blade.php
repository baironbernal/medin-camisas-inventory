@php
    $isPending = $record->status === \App\Models\Order::STATUS_PENDING;
@endphp

<div style="overflow-x:auto;">
    <table style="width:100%;border-collapse:collapse;font-size:13px;">
        <thead>
            <tr style="background:#f9fafb;border-bottom:2px solid #e5e7eb;">
                <th style="padding:8px 12px;text-align:left;font-weight:600;color:#374151;">Foto</th>
                <th style="padding:8px 12px;text-align:left;font-weight:600;color:#374151;">Producto</th>
                <th style="padding:8px 12px;text-align:left;font-weight:600;color:#374151;">SKU</th>
                <th style="padding:8px 12px;text-align:center;font-weight:600;color:#374151;">Color</th>
                <th style="padding:8px 12px;text-align:left;font-weight:600;color:#374151;">Nombre color</th>
                <th style="padding:8px 12px;text-align:center;font-weight:600;color:#374151;">Cant.</th>
                <th style="padding:8px 12px;text-align:right;font-weight:600;color:#374151;">Precio Unit.</th>
                <th style="padding:8px 12px;text-align:left;font-weight:600;color:#374151;">Descuento</th>
                <th style="padding:8px 12px;text-align:right;font-weight:600;color:#374151;">Subtotal</th>
                @if($isPending)<th style="padding:8px 12px;width:48px;"></th>@endif
            </tr>
        </thead>
        <tbody>
            @foreach($record->items as $item)
                @php
                    $variant  = $item->productVariant;
                    $image    = $variant?->images[0] ?? null;
                    $imageUrl = $image ? asset('storage/' . $image) : null;

                    $colorAttr = $variant?->variantAttributes
                        ->first(fn ($va) => optional($va->attribute)->code === 'COLOR');
                    $colorName = $colorAttr?->attributeValue?->value ?? '—';
                    $colorHex  = $colorAttr?->attributeValue?->hex_color ?? null;

                    $unitPrice = number_format((float) $item->discounted_unit_price, 0, ',', '.');
                    $subtotal  = number_format((float) $item->discounted_total_price, 0, ',', '.');
                @endphp
                <tr style="border-bottom:1px solid #f3f4f6;">

                    {{-- Image --}}
                    <td style="padding:8px 12px;">
                        @if($imageUrl)
                            <img src="{{ $imageUrl }}"
                                 style="width:48px;height:48px;object-fit:cover;border-radius:8px;display:block;"
                                 alt="{{ $item->product_name }}">
                        @else
                            <div style="width:48px;height:48px;background:#f3f4f6;border-radius:8px;"></div>
                        @endif
                    </td>

                    {{-- Product --}}
                    <td style="padding:8px 12px;font-weight:500;">{{ $item->product_name }}</td>

                    {{-- SKU --}}
                    <td style="padding:8px 12px;color:#6b7280;font-size:12px;">{{ $item->variant_sku }}</td>

                    {{-- Color swatch --}}
                    <td style="padding:8px 12px;text-align:center;">
                        @if($colorHex)
                            <span style="display:inline-block;width:22px;height:22px;border-radius:50%;background:{{ $colorHex }};border:1px solid #d1d5db;box-shadow:0 1px 2px rgba(0,0,0,.15);"
                                  title="{{ $colorName }}"></span>
                        @else
                            <span style="color:#9ca3af;">—</span>
                        @endif
                    </td>

                    {{-- Color name --}}
                    <td style="padding:8px 12px;color:#374151;">{{ $colorName }}</td>

                    {{-- Qty --}}
                    <td style="padding:8px 12px;text-align:center;">{{ $item->quantity }}</td>

                    {{-- Unit price --}}
                    <td style="padding:8px 12px;text-align:right;">${{ $unitPrice }}</td>

                    {{-- Discount label --}}
                    <td style="padding:8px 12px;">
                        @if($item->discount)
                            <span style="background:#fef3c7;color:#92400e;padding:2px 7px;border-radius:4px;font-size:11px;white-space:nowrap;">
                                {{ $item->discount->name }} ({{ $item->discount->formatted_value }})
                            </span>
                        @elseif((float)$item->discount_percentage > 0)
                            <span style="color:#f59e0b;">{{ $item->discount_percentage }}%</span>
                        @else
                            <span style="color:#9ca3af;">—</span>
                        @endif
                    </td>

                    {{-- Subtotal --}}
                    <td style="padding:8px 12px;text-align:right;font-weight:600;">${{ $subtotal }}</td>

                    {{-- Remove button (native onclick — no wire: dependency) --}}
                    @if($isPending)
                        <td style="padding:8px 12px;text-align:center;">
                            <button
                                onclick="window.Livewire.dispatch('open-remove-modal', { itemId: {{ $item->id }} })"
                                style="width:32px;height:32px;background:#fee2e2;color:#dc2626;border:none;border-radius:6px;cursor:pointer;font-size:15px;display:inline-flex;align-items:center;justify-content:center;"
                                title="Quitar item"
                            >✕</button>
                        </td>
                    @endif
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
