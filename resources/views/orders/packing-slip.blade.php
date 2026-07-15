<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Factura - {{ $order->order_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #f2f2f2;
            color: #1a1a2e;
        }

        /* ── Receipt ─────────────────────────────────────────── */
        .receipt {
            width: 360px;
            margin: 24px auto;
            padding: 26px 22px 30px;
            background: #fff;
            box-shadow: 0 2px 14px rgba(0, 0, 0, 0.12);
            color: #1a1a2e;
            font-size: 12px;
            line-height: 1.5;
        }

        .center { text-align: center; }
        .bold   { font-weight: bold; }

        /* ── Logo ────────────────────────────────────────────── */
        .logo-block {
            text-align: center;
            margin-bottom: 14px;
        }
        .logo-block img {
            height: 78px;
        }

        /* ── Company info ────────────────────────────────────── */
        .company-info {
            text-align: center;
            font-size: 12px;
            line-height: 1.55;
        }
        .company-info .company-name {
            font-weight: bold;
            font-size: 13px;
            letter-spacing: 0.4px;
        }

        /* ── Dashed divider ──────────────────────────────────── */
        .divider {
            border: none;
            border-top: 1.5px dashed #9a9aae;
            margin: 12px 0;
        }

        /* ── Invoice title block ─────────────────────────────── */
        .invoice-title {
            text-align: center;
        }
        .invoice-title .heading {
            font-size: 16px;
            font-weight: bold;
            letter-spacing: 0.6px;
            margin-bottom: 4px;
        }
        .invoice-title .meta {
            font-size: 12px;
            line-height: 1.6;
        }

        /* ── Items table ─────────────────────────────────────── */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }
        .items-table thead th {
            font-weight: bold;
            padding: 0 0 6px;
            border-bottom: 1.5px solid #1a1a2e;
            text-align: left;
        }
        .items-table thead th.col-cant   { width: 34px; }
        .items-table thead th.col-unit,
        .items-table thead th.col-total   { text-align: right; white-space: nowrap; }

        .items-table tbody td {
            padding: 8px 0 2px;
            vertical-align: top;
        }
        .items-table tbody td.col-cant  { text-align: left; }
        .items-table tbody td.col-unit,
        .items-table tbody td.col-total { text-align: right; white-space: nowrap; }
        .items-table tbody tr td { padding-top: 9px; }
        .items-table tbody tr:first-child td { padding-top: 10px; }

        /* ── Totals ──────────────────────────────────────────── */
        .totals {
            width: 100%;
            font-size: 12px;
        }
        .totals td {
            padding: 3px 0;
        }
        .totals td.label { text-align: right; padding-right: 14px; }
        .totals td.value { text-align: right; white-space: nowrap; width: 100px; }
        .totals tr.grand td {
            font-weight: bold;
            font-size: 14px;
            padding-top: 6px;
        }

        /* ── Footer ──────────────────────────────────────────── */
        .footer {
            text-align: center;
        }
        .footer .thanks {
            font-size: 15px;
            font-weight: bold;
            letter-spacing: 0.4px;
        }
        .footer .tagline {
            font-size: 12px;
            margin-top: 3px;
        }
        .social {
            margin-top: 12px;
            font-size: 12px;
            line-height: 1.9;
        }
        .social .row {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .social .row svg {
            width: 15px;
            height: 15px;
            flex: 0 0 15px;
        }
        .website {
            margin-top: 12px;
            font-size: 12px;
        }

        /* ── Print button ────────────────────────────────────── */
        .print-btn-wrap {
            text-align: center;
            margin: 20px auto 30px;
        }
        .print-btn {
            padding: 10px 32px;
            font-size: 14px;
            background: #1a1a2e;
            color: #fff;
            border: none;
            cursor: pointer;
            border-radius: 4px;
        }

        @media print {
            .print-btn-wrap { display: none; }
            body { background: #fff; margin: 0; }
            .receipt { box-shadow: none; margin: 0 auto; }
        }
    </style>
</head>
<body>

<div class="receipt">

    {{-- ── LOGO ─────────────────────────────────────────────── --}}
    <div class="logo-block">
        <img src="{{ asset('images/logo-dark.png') }}" alt="Medin Fashion Feels">
    </div>

    {{-- ── COMPANY INFO ─────────────────────────────────────── --}}
    <div class="company-info">
        <div class="company-name">MEDIN FASHION FEELS</div>
        <div>NIT: 901544814-0</div>
        <div>CC Punto Once Local 6017</div>
        <div>Bogotá, D.C. - Colombia</div>
        <div>Tel: 310 123 4567</div>
    </div>

    <hr class="divider">

    {{-- ── INVOICE TITLE ────────────────────────────────────── --}}
    <div class="invoice-title">
        <div class="heading">FACTURA DE VENTA</div>
        <div class="meta">
            <div>No. {{ $order->order_number }}</div>
            <div>Fecha: {{ $order->created_at->format('d/m/Y  h:i A') }}</div>
            <div>Vendedor: {{ str_pad((string) ($preparedBy?->id ?? 1), 2, '0', STR_PAD_LEFT) }}</div>
        </div>
    </div>

    <hr class="divider">

    {{-- ── ITEMS TABLE ──────────────────────────────────────── --}}
    <table class="items-table">
        <thead>
            <tr>
                <th class="col-cant">CANT</th>
                <th class="col-desc">DESCRIPCIÓN</th>
                <th class="col-unit">V. UNIT</th>
                <th class="col-total">V. TOTAL</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $item)
            <tr>
                <td class="col-cant">{{ $item->quantity }}</td>
                <td class="col-desc">{{ $item->product_name }}</td>
                <td class="col-unit">$ {{ number_format((float) $item->discounted_unit_price, 0, ',', '.') }}</td>
                <td class="col-total">$ {{ number_format((float) $item->discounted_total_price, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <hr class="divider">

    {{-- ── TOTALS ───────────────────────────────────────────── --}}
    @php
        $subtotal = (float) ($order->subtotal_original ?? $order->subtotal ?? 0);
        $descuento = max(0, $subtotal - (float) ($order->subtotal_discounted ?? $order->subtotal ?? $subtotal));
    @endphp
    <table class="totals">
        <tr>
            <td class="label">SUBTOTAL</td>
            <td class="value">$ {{ number_format($subtotal, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td class="label">DESCUENTO</td>
            <td class="value">$ {{ number_format($descuento, 0, ',', '.') }}</td>
        </tr>
        <tr class="grand">
            <td class="label">TOTAL</td>
            <td class="value">$ {{ number_format((float) $order->total, 0, ',', '.') }}</td>
        </tr>
    </table>

    <hr class="divider">

    {{-- ── FOOTER ───────────────────────────────────────────── --}}
    <div class="footer">
        <div class="thanks">¡GRACIAS POR TU COMPRA!</div>
        <div class="tagline">Tu estilo, nuestra pasión.</div>

        <div class="social">
            <div class="row">
                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.16c3.2 0 3.58.01 4.85.07 1.17.05 1.8.25 2.23.41.56.22.96.48 1.38.9.42.42.68.82.9 1.38.16.42.36 1.06.41 2.23.06 1.27.07 1.65.07 4.85s-.01 3.58-.07 4.85c-.05 1.17-.25 1.8-.41 2.23-.22.56-.48.96-.9 1.38-.42.42-.82.68-1.38.9-.42.16-1.06.36-2.23.41-1.27.06-1.65.07-4.85.07s-3.58-.01-4.85-.07c-1.17-.05-1.8-.25-2.23-.41a3.7 3.7 0 0 1-1.38-.9 3.7 3.7 0 0 1-.9-1.38c-.16-.42-.36-1.06-.41-2.23C2.17 15.58 2.16 15.2 2.16 12s.01-3.58.07-4.85c.05-1.17.25-1.8.41-2.23.22-.56.48-.96.9-1.38.42-.42.82-.68 1.38-.9.42-.16 1.06-.36 2.23-.41C8.42 2.17 8.8 2.16 12 2.16zm0 1.62c-3.15 0-3.52.01-4.76.07-.9.04-1.39.19-1.71.32-.43.17-.74.37-1.06.69-.32.32-.52.63-.69 1.06-.13.32-.28.81-.32 1.71-.06 1.24-.07 1.61-.07 4.76s.01 3.52.07 4.76c.04.9.19 1.39.32 1.71.17.43.37.74.69 1.06.32.32.63.52 1.06.69.32.13.81.28 1.71.32 1.24.06 1.61.07 4.76.07s3.52-.01 4.76-.07c.9-.04 1.39-.19 1.71-.32.43-.17.74-.37 1.06-.69.32-.32.52-.63.69-1.06.13-.32.28-.81.32-1.71.06-1.24.07-1.61.07-4.76s-.01-3.52-.07-4.76c-.04-.9-.19-1.39-.32-1.71a2.85 2.85 0 0 0-.69-1.06 2.85 2.85 0 0 0-1.06-.69c-.32-.13-.81-.28-1.71-.32-1.24-.06-1.61-.07-4.76-.07zm0 2.76a5.3 5.3 0 1 1 0 10.6 5.3 5.3 0 0 1 0-10.6zm0 8.74a3.44 3.44 0 1 0 0-6.88 3.44 3.44 0 0 0 0 6.88zm6.74-8.94a1.24 1.24 0 1 1-2.48 0 1.24 1.24 0 0 1 2.48 0z"/></svg>
                <span>&#64;medinfashionfeels</span>
            </div>
            <div class="row">
                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M22 12a10 10 0 1 0-11.56 9.88v-6.99H7.9V12h2.54V9.8c0-2.5 1.49-3.89 3.78-3.89 1.09 0 2.24.2 2.24.2v2.46h-1.26c-1.24 0-1.63.77-1.63 1.56V12h2.78l-.44 2.89h-2.34v6.99A10 10 0 0 0 22 12z"/></svg>
                <span>Medin Fashion Feels</span>
            </div>
            <div class="row">
                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M16.6 5.82a4.28 4.28 0 0 1-1.05-2.82h-3.2v12.86a2.34 2.34 0 0 1-2.33 2.2 2.34 2.34 0 1 1 .66-4.58v-3.26a5.63 5.63 0 0 0-.66-.04 5.6 5.6 0 1 0 5.6 5.6V9.01a7.44 7.44 0 0 0 4.35 1.4V7.2a4.28 4.28 0 0 1-3.37-1.38z"/></svg>
                <span>&#64;camisasmedin</span>
            </div>
        </div>

        <div class="website">www.camisasmedin.com</div>
    </div>

</div>

<div class="print-btn-wrap">
    <button class="print-btn" onclick="window.print()">🖨️ Imprimir</button>
</div>

</body>
</html>
