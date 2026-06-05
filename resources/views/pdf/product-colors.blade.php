<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Colores - {{ $product->name }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #292944; margin: 0; padding: 0; }
        .header { padding: 20px 24px; border-bottom: 2px solid #e7d0c3; margin-bottom: 20px; }
        .header h1 { font-size: 20px; font-weight: bold; margin: 0; }
        .header p { font-size: 11px; color: #707070; margin-top: 4px; }
        table { width: 100%; border-collapse: collapse; padding: 0 16px; }
        td { width: 33%; padding: 8px; vertical-align: top; text-align: center; }
        td img { width: 100%; max-height: 200px; }
        .footer { margin-top: 24px; padding: 12px 24px; border-top: 1px solid #e5e7eb; font-size: 10px; color: #9ca3af; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $product->name }}</h1>
        <p>Catálogo de imágenes — {{ count($imageUrls) }} imagen(es)</p>
    </div>

    <table>
        @foreach(array_chunk($imageUrls, 3) as $row)
        <tr>
            @foreach($row as $url)
            <td><img src="{{ $url }}" alt="Variante"></td>
            @endforeach
            @for($i = count($row); $i < 3; $i++)
            <td></td>
            @endfor
        </tr>
        @endforeach
    </table>

    <div class="footer">Medin Camisas — Generado el {{ now()->format('d/m/Y') }}</div>
</body>
</html>
