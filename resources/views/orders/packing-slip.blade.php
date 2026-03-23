<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Packing Slip - {{ $order->order_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Helvetica Neue', Arial, sans-serif;
            font-size: 12px;
            line-height: 1.3;
        }
        
        .page {
            width: 100mm;
            min-height: 180mm;
            padding: 10mm;
            margin: 0 auto;
            background: white;
        }
        
        .header {
            text-align: center;
            margin-bottom: 15px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }
        
        .company-name {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 3px;
        }
        
        .order-title {
            font-size: 16px;
            font-weight: bold;
        }
        
        .order-number {
            font-size: 14px;
            margin-top: 5px;
        }
        
        .info-grid {
            display: table;
            width: 100%;
            margin-bottom: 15px;
        }
        
        .info-row {
            display: table-row;
        }
        
        .info-cell {
            display: table-cell;
            width: 50%;
            padding: 4px;
            vertical-align: top;
        }
        
        .info-label {
            font-weight: bold;
            color: #333;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        
        .items-table th {
            background: #f5f5f5;
            border: 1px solid #333;
            padding: 6px;
            text-align: left;
            font-weight: bold;
        }
        
        .items-table td {
            border: 1px solid #333;
            padding: 6px;
        }
        
        .items-table .qty {
            text-align: center;
            width: 50px;
        }
        
        .items-table .sku {
            font-size: 10px;
        }
        
        .totals {
            text-align: right;
            margin-bottom: 15px;
        }
        
        .totals-row {
            margin: 3px 0;
        }
        
        .totals-label {
            display: inline-block;
            width: 100px;
        }
        
        .totals-value {
            display: inline-block;
            width: 80px;
            text-align: right;
        }
        
        .footer {
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #ccc;
            text-align: center;
            font-size: 10px;
            color: #666;
        }
        
        @media print {
            body {
                margin: 0;
            }
            .page {
                margin: 0;
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="page">
        <div class="header">
            <div class="company-name">MEDINCAMISAS</div>
            <div class="order-title">ORDEN DE PEDIDO / PACKING SLIP</div>
            <div class="order-number">No. {{ $order->order_number }}</div>
        </div>
        
        <div class="info-grid">
            <div class="info-row">
                <div class="info-cell">
                    <div class="info-label">Cliente:</div>
                    <div>{{ $order->customer_name }}</div>
                </div>
                <div class="info-cell">
                    <div class="info-label">Teléfono:</div>
                    <div>{{ $order->customer_phone }}</div>
                </div>
            </div>
            <div class="info-row">
                <div class="info-cell">
                    <div class="info-label">Email:</div>
                    <div>{{ $order->customer_email }}</div>
                </div>
                <div class="info-cell">
                    <div class="info-label">Fecha:</div>
                    <div>{{ $order->created_at->format('d/m/Y H:i') }}</div>
                </div>
            </div>
            <div class="info-row">
                <div class="info-cell" style="width: 100%;">
                    <div class="info-label">Dirección de Envío:</div>
                    @if(is_array($order->shipping_address))
                        <div>
                            {{ $order->shipping_address['address'] ?? '' }}<br>
                            {{ $order->shipping_address['city'] ?? '' }}, {{ $order->shipping_address['state'] ?? '' }}<br>
                            {{ $order->shipping_address['country'] ?? '' }} - {{ $order->shipping_address['postal_code'] ?? '' }}
                        </div>
                    @else
                        <div>{{ $order->shipping_address }}</div>
                    @endif
                </div>
            </div>
        </div>
        
        <table class="items-table">
            <thead>
                <tr>
                    <th class="qty">Qty</th>
                    <th>Producto</th>
                    <th>SKU</th>
                    <th>Talla/Color</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $item)
                <tr>
                    <td class="qty">{{ $item->quantity }}</td>
                    <td>{{ $item->product_name }}</td>
                    <td class="sku">{{ $item->variant_sku }}</td>
                    <td>
                        @if($item->productVariant && $item->productVariant->variantAttributes)
                            @foreach($item->productVariant->variantAttributes as $attr)
                                {{ $attr->attributeValue->value ?? '' }}
                                @if(!$loop->last) / @endif
                            @endforeach
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        
        <div class="totals">
            <div class="totals-row">
                <span class="totals-label">Subtotal:</span>
                <span class="totals-value">${{ number_format($order->subtotal_original, 0, ',', '.') }}</span>
            </div>
            @if($order->subtotal_discounted < $order->subtotal_original)
            <div class="totals-row">
                <span class="totals-label">Descuento:</span>
                <span class="totals-value">-${{ number_format($order->subtotal_original - $order->subtotal_discounted, 0, ',', '.') }}</span>
            </div>
            @endif
            @if($order->shipping_cost > 0)
            <div class="totals-row">
                <span class="totals-label">Envío:</span>
                <span class="totals-value">${{ number_format($order->shipping_cost, 0, ',', '.') }}</span>
            </div>
            @endif
            @if($order->tax > 0)
            <div class="totals-row">
                <span class="totals-label">Impuesto:</span>
                <span class="totals-value">${{ number_format($order->tax, 0, ',', '.') }}</span>
            </div>
            @endif
            <div class="totals-row" style="font-weight: bold; font-size: 14px;">
                <span class="totals-label">TOTAL:</span>
                <span class="totals-value">${{ number_format($order->total, 0, ',', '.') }}</span>
            </div>
        </div>
        
    @if($order->notes)
        <div style="margin-bottom: 15px;">
            <div class="info-label">Notas:</div>
            <div>{{ $order->notes }}</div>
        </div>
    @endif
    
    <div class="footer">
        <p>Gracias por su compra - MEDINCAMISAS</p>
        <p>Este documento es para uso interno del paquete</p>
    </div>
    </div>
    
    <div style="text-align: center; margin-top: 20px; padding: 20px;">
        <button onclick="window.print()" style="padding: 12px 30px; font-size: 16px; cursor: pointer; background: #000; color: #fff; border: none; border-radius: 5px;">
            🖨️ IMPRIMIR
        </button>
    </div>
    
    <script>
        // No auto-print, user clicks button
    </script>
</body>
</html>
