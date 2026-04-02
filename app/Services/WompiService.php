<?php

namespace App\Services;

class WompiService
{
    /**
     * Generate the integrity signature for the Wompi checkout widget.
     * Formula: SHA256(reference + amount_in_cents + currency + integrity_key)
     */
    public function generateSignature(string $reference, int $amountInCents, string $currency = 'COP'): string
    {
        return hash('sha256', $reference . $amountInCents . $currency . config('services.wompi.integrity_key'));
    }

    /**
     * Build the full config array needed to render the Wompi checkout widget.
     */
    public function widgetConfig(string $reference, int $amountInCents, string $currency = 'COP'): array
    {
        return [
            'public_key'      => config('services.wompi.public_key'),
            'currency'        => $currency,
            'amount_in_cents' => $amountInCents,
            'reference'       => $reference,
            'signature'       => $this->generateSignature($reference, $amountInCents, $currency),
        ];
    }

    /**
     * Verify the checksum of a Wompi webhook event.
     *
     * Wompi sends:
     *   signature.properties  → list of dotted paths to values in event.data
     *   signature.checksum    → SHA256(values_concatenated + timestamp + events_secret)
     *   timestamp             → unix epoch sent in the event
     */
    public function verifyWebhookEvent(array $payload): bool
    {
        $properties = $payload['signature']['properties'] ?? [];
        $checksum   = $payload['signature']['checksum']   ?? '';
        $timestamp  = $payload['timestamp']               ?? '';

        $concatenated = '';
        foreach ($properties as $property) {
            $concatenated .= data_get($payload['data'], $property);
        }
        $concatenated .= $timestamp;
        $concatenated .= config('services.wompi.events_key');

        return hash('sha256', $concatenated) === $checksum;
    }
}
