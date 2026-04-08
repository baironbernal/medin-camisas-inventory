<?php

namespace App\Filament\Resources\OrderResource\Steps;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Wizard\Step;
use Filament\Forms\Get;
use Illuminate\Support\HtmlString;

class PaymentStep
{
    public static function make(): Step
    {
        return Step::make('Pago')
            ->label('Pago')
            ->icon('heroicon-o-credit-card')
            ->description('Suba el comprobante de pago')
            ->schema([
                self::uploadSection(),
                self::summarySection(),
            ]);
    }

    // -------------------------------------------------------------------------

    private static function uploadSection(): Section
    {
        return Section::make('Comprobante de Pago')
            ->description('Suba la imagen o PDF del comprobante de pago verificado')
            ->icon('heroicon-o-document-arrow-up')
            ->schema([
                FileUpload::make('payment_proof_path')
                    ->label('Comprobante de Pago')
                    ->disk('public')
                    ->directory('payment-proofs')
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'application/pdf'])
                    ->maxSize(5120)
                    ->required()
                    ->image()
                    ->imagePreviewHeight('200'),
            ]);
    }

    private static function summarySection(): Section
    {
        return Section::make('Resumen del Pedido')
            ->description('Verifique los datos antes de crear el pedido')
            ->icon('heroicon-o-clipboard-document-check')
            ->schema([
                Placeholder::make('order_summary')
                    ->label('')
                    ->content(fn (Get $get): HtmlString => self::buildSummaryHtml($get)),
            ]);
    }

    // -------------------------------------------------------------------------

    private static function buildSummaryHtml(Get $get): HtmlString
    {
        $isExisting = filled($get('customer_id'));

        $customerName = $isExisting
            ? ($get('customer_name') ?? '—')
            : trim(($get('new_first_name') ?? '') . ' ' . ($get('new_last_name') ?? ''));

        $identity = $get('identity_number') ?? '—';
        $items = $get('items') ?? [];
        $totalUnits = array_sum(array_map(fn ($item) => (int) ($item['quantity'] ?? 0), $items));

        $badge = $isExisting
            ? '<span style="background:#dcfce7;color:#166534;padding:2px 8px;border-radius:4px;font-size:0.75rem;margin-left:6px;">Registrado</span>'
            : '<span style="background:#fef9c3;color:#854d0e;padding:2px 8px;border-radius:4px;font-size:0.75rem;margin-left:6px;">Nuevo mayorista</span>';

        $totalsHtml = ProductsStep::buildTotalHtml($items)->toHtml();

        $html = '<div style="padding:16px;background:#f9fafb;border-radius:8px;border:1px solid #e5e7eb;">'
            . "<div style='margin-bottom:12px;'>"
            . "<div style='font-weight:600;font-size:1rem;'>{$customerName}{$badge}</div>"
            . "<div style='color:#6b7280;font-size:0.875rem;margin-top:2px;'>CC / NIT: {$identity}</div>"
            . "<div style='color:#6b7280;font-size:0.875rem;'>Unidades: {$totalUnits}</div>"
            . '</div>'
            . $totalsHtml
            . '</div>';

        return new HtmlString($html);
    }
}
