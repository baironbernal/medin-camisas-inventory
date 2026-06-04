<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Colores - {{ $product->name }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; color: #292944; background: #fff; }
        .header { padding: 24px 32px; border-bottom: 2px solid #e7d0c3; margin-bottom: 24px; }
        .header h1 { font-size: 22px; font-weight: bold; color: #292944; }
        .header p { font-size: 12px; color: #707070; margin-top: 4px; }
        .grid { display: table; width: 100%; padding: 0 24px; }
        .row { display: table-row; }
        .cell { display: table-cell; width: 33%; padding: 12px; vertical-align: top; text-align: center; }
        .variant-card { border: 1px solid #e5e7eb; border-radius: 8px; padding: 12px; background: #fafafa; }
        .variant-img { width: 100%; max-height: 180px; object-fit: cover; border-radius: 4px; margin-bottom: 8px; }
        .variant-no-img { width: 100%; height: 120px; background: #f3f4f6; border-radius: 4px; margin-bottom: 8px; display: table-cell; vertical-align: middle; text-align: center; color: #9ca3af; font-size: 11px; }
        .color-name { font-size: 12px; font-weight: 600; color: #292944; }
        .sku { font-size: 10px; color: #707070; margin-top: 2px; }
        .footer { margin-top: 32px; padding: 16px 32px; border-top: 1px solid #e5e7eb; font-size: 10px; color: #9ca3af; text-align: center; }
        .page-break { page-break-after: always; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $product->name }}</h1>
        <p>Catálogo de colores y variantes — {{ $variantCount }} variante(s) disponible(s)</p>
    </div>

    <div class="grid">
        @php $col = 0; $started = false; @endphp
        @foreach($variantGroups as $colorName => $variants)
            @if($col % 3 === 0)
                @if($started) </div> @endif
                <div class="row">
                @php $started = true; @endphp
            @endif
            <div class="cell">
                <div class="variant-card">
                    @if($variants['image'])
                        <img src="{{ $variants['image'] }}" class="variant-img" alt="{{ $colorName }}">
                    @else
                        <div class="variant-no-img">Sin imagen</div>
                    @endif
                    <div class="color-name">{{ $colorName }}</div>
                    @if(count($variants['skus']) <= 3)
                        <div class="sku">{{ implode(' · ', $variants['skus']) }}</div>
                    @endif
                </div>
            </div>
            @php $col++; @endphp
        @endforeach
        @if($started) </div> @endif
    </div>

    <div class="footer">
        Medin Camisas — Generado el {{ now()->format('d/m/Y') }}
    </div>
</body>
</html>
