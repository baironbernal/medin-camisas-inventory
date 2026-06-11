<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Factura - {{ $order->order_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            background: #fff;
            color: #000;
        }

        .page {
            width: 860px;
            margin: 0 auto;
            padding: 18px;
            background: #fff;
        }

        /* ── Header ─────────────────────────────────────────── */
        .header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            margin-bottom: 6px;
        }

        .logo-block img {
            height: 80px;
        }

        .company-info {
            text-align: right;
            font-size: 10.5px;
            line-height: 1.6;
        }

        .company-info .company-name {
            font-weight: bold;
            font-size: 12px;
        }

        .company-info .divider {
            border: none;
            border-top: 1px solid #ccc;
            margin: 4px 0;
        }

        /* ── Red bar ─────────────────────────────────────────── */
        .red-bar {
            height: 4px;
            background: #8B1A1A;
            margin-bottom: 0;
        }

        /* ── Main table wrapper ──────────────────────────────── */
        .invoice-wrap {
            border: 1.5px solid #000;
            border-top: none;
        }

        /* ── Title row ───────────────────────────────────────── */
        .title-row {
            display: flex;
            border-bottom: 1.5px solid #000;
        }

        .title-main {
            flex: 1;
            text-align: center;
            padding: 8px 4px;
            font-size: 18px;
            font-weight: bold;
            letter-spacing: 1px;
            border-right: 1.5px solid #000;
        }

        .title-label {
            width: 120px;
            text-align: center;
            padding: 8px 4px;
            font-size: 11px;
            font-weight: bold;
            border-right: 1.5px solid #000;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .title-number {
            width: 130px;
            text-align: center;
            padding: 8px 4px;
            font-size: 24px;
            font-weight: bold;
            color: #8B1A1A;
            background: #fdf8e1;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* ── Info rows ───────────────────────────────────────── */
        .info-row {
            display: flex;
            border-bottom: 1.5px solid #000;
        }

        .info-cell-label {
            width: 80px;
            font-weight: bold;
            padding: 5px 8px;
            border-right: 1.5px solid #000;
            background: #f9f9f9;
        }

        .info-cell-value {
            flex: 1;
            padding: 5px 8px;
            font-weight: bold;
            border-right: 1.5px solid #000;
        }

        .info-cell-label-right {
            width: 80px;
            font-weight: bold;
            padding: 5px 8px;
            border-right: 1.5px solid #000;
            background: #f9f9f9;
            text-align: right;
        }

        .info-cell-value-right {
            width: 180px;
            padding: 5px 8px;
            font-weight: bold;
            text-align: center;
            color: #8B1A1A;
        }

        /* ── Empty spacer row ────────────────────────────────── */
        .spacer-row {
            height: 10px;
            border-bottom: 1.5px solid #000;
        }

        /* ── Items table ─────────────────────────────────────── */
        .items-table {
            width: 100%;
            border-collapse: collapse;
        }

        .items-table thead tr th {
            background: #8B1A1A;
            color: #fff;
            padding: 5px 8px;
            font-weight: bold;
            font-size: 11px;
            border-right: 1px solid #6b1111;
            text-align: center;
        }

        .items-table thead tr th:first-child  { width: 80px; }
        .items-table thead tr th:nth-child(2) { text-align: left; }
        .items-table thead tr th:nth-child(3) { width: 130px; }
        .items-table thead tr th:last-child   { width: 130px; border-right: none; }

        .items-table tbody tr td {
            border-bottom: 1px solid #ccc;
            border-right: 1px solid #ccc;
            padding: 4px 8px;
            height: 22px;
            vertical-align: middle;
        }

        .items-table tbody tr td:first-child  { text-align: center; font-weight: bold; }
        .items-table tbody tr td:nth-child(3),
        .items-table tbody tr td:last-child   { text-align: right; }
        .items-table tbody tr td:last-child   { border-right: none; }

        /* ── Payment / notes row ─────────────────────────────── */
        .payment-row {
            border-top: 1.5px solid #000;
            border-bottom: 1.5px solid #000;
        }

        .payment-row td {
            padding: 5px 8px;
        }

        /* ── Footer row ──────────────────────────────────────── */
        .footer-row {
            display: flex;
        }

        .footer-label {
            width: 100px;
            padding: 6px 8px;
            font-weight: bold;
            border-right: 1.5px solid #000;
            background: #f9f9f9;
        }

        .footer-value {
            flex: 1;
            padding: 6px 8px;
            font-weight: bold;
            border-right: 1.5px solid #000;
            text-align: center;
        }

        .footer-total-label {
            width: 80px;
            padding: 6px 8px;
            font-weight: bold;
            border-right: 1.5px solid #000;
            text-align: right;
        }

        .footer-total-value {
            width: 130px;
            padding: 6px 8px;
            font-weight: bold;
            font-size: 13px;
            text-align: right;
            color: #8B1A1A;
        }

        /* ── Print button ────────────────────────────────────── */
        .print-btn-wrap {
            text-align: center;
            margin-top: 24px;
        }

        .print-btn {
            padding: 10px 32px;
            font-size: 14px;
            background: #8B1A1A;
            color: #fff;
            border: none;
            cursor: pointer;
            border-radius: 4px;
        }

        @media print {
            .print-btn-wrap { display: none; }
            body { margin: 0; }
            .page { width: 100%; padding: 8px; }
        }
    </style>
</head>
<body>
<div class="page">

    {{-- ── HEADER ───────────────────────────────────────────── --}}
    <div class="header">
        <div class="logo-block">
            <img src="{{ asset('images/logo-dark.png') }}" alt="MEDIN">
        </div>
        <div class="company-info">
            <div class="company-name">Johann Camilo Medina Méndez</div>
            <div>Nit. 1022425421-3 Régimen simplificado</div>
            <hr class="divider">
            <div>CC San Jose Plaza, San Andresito, Local 215 – 216</div>
            <hr class="divider">
            <div>✉ camisasmedin@gmail.com &nbsp;&nbsp; 𝕗 @medincamisas</div>
            <div>📱 321 456 9004 &nbsp;&nbsp; 302 419 7103</div>
        </div>
    </div>

    {{-- ── RED BAR ───────────────────────────────────────────── --}}
    <div class="red-bar"></div>

    {{-- ── INVOICE WRAPPER ──────────────────────────────────── --}}
    <div class="invoice-wrap">

        {{-- Title row --}}
        <div class="title-row">
            <div class="title-main">FACTURA DE VENTA</div>
            <div class="title-label">No. Factura</div>
            <div class="title-number">{{ $order->id }}</div>
        </div>

        {{-- Customer name / date --}}
        <div class="info-row">
            <div class="info-cell-label">Nombre</div>
            <div class="info-cell-value">{{ strtoupper($order->customer_name ?? '—') }}</div>
            <div class="info-cell-label-right">Fecha</div>
            <div class="info-cell-value-right">{{ $order->created_at->format('d/m/Y') }}</div>
        </div>

        {{-- Contact / city --}}
        @php
            $addr   = $order->shipping_address ?? [];
            $state  = $addr['state'] ?? '';
            $city   = trim(($addr['city'] ?? '') . ($state ? ' - ' . strtoupper($state) : ''));
            $city   = $city ?: '—';
        @endphp
        <div class="info-row">
            <div class="info-cell-label">Contacto</div>
            <div class="info-cell-value">{{ $order->customer_phone ?? '—' }}</div>
            <div class="info-cell-label-right">Ciudad</div>
            <div class="info-cell-value-right" style="color:#000;font-size:11px;">{{ strtoupper($city) }}</div>
        </div>

        {{-- Spacer --}}
        <div class="spacer-row"></div>

        {{-- Items table --}}
        <table class="items-table">
            <thead>
                <tr>
                    <th>Cantidad</th>
                    <th>Descripción</th>
                    <th>Valor Unidad</th>
                    <th>Valor total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $item)
                <tr>
                    <td>{{ $item->quantity }}</td>
                    <td>{{ strtoupper($item->product_name) }}</td>
                    <td>$ &nbsp;{{ number_format((float)$item->discounted_unit_price, 0, ',', '.') }}</td>
                    <td>$ &nbsp;{{ number_format((float)$item->discounted_total_price, 0, ',', '.') }}</td>
                </tr>
                @endforeach

                {{-- Fill empty rows up to 10 minimum lines --}}
                @for($i = $order->items->count(); $i < 10; $i++)
                <tr>
                    <td></td>
                    <td></td>
                    <td>$</td>
                    <td>-</td>
                </tr>
                @endfor

                {{-- Payment / notes row --}}
                <tr class="payment-row">
                    <td colspan="2" style="text-align:center;font-style:italic;">
                        {{ $order->notes ?? '' }}
                    </td>
                    <td></td>
                    <td>$ &nbsp;-</td>
                </tr>
            </tbody>
        </table>

        {{-- Footer --}}
        <div class="footer-row">
            <div class="footer-label">Elaborado por</div>
            <div class="footer-value">{{ strtoupper($preparedBy?->full_name ?? $preparedBy?->name ?? '—') }}</div>
            <div class="footer-total-label">Total</div>
            <div class="footer-total-value">$ {{ number_format((float)$order->total, 0, ',', '.') }}</div>
        </div>

    </div>{{-- /invoice-wrap --}}

    <div class="print-btn-wrap">
        <button class="print-btn" onclick="window.print()">🖨️ Imprimir</button>
    </div>

</div>
</body>
</html>
