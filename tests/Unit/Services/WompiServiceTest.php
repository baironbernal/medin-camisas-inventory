<?php

namespace Tests\Unit\Services;

use App\Services\WompiService;
use Tests\TestCase;

/**
 * Pure unit tests for WompiService.
 * These tests validate the cryptographic logic without touching the database.
 */
class WompiServiceTest extends TestCase
{
    private WompiService $service;
    private string $integrityKey = 'test_integrity_key_abc123';
    private string $eventsKey    = 'test_events_key_xyz789';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.wompi.integrity_key' => $this->integrityKey,
            'services.wompi.events_key'    => $this->eventsKey,
            'services.wompi.public_key'    => 'pub_test_ABC123',
        ]);

        $this->service = new WompiService();
    }

    // ── generateSignature ────────────────────────────────────────────────────

    public function test_it_generates_correct_sha256_signature(): void
    {
        $reference     = 'ORD-20260401-ABC123';
        $amountInCents = 5000000;
        $currency      = 'COP';

        $expected = hash('sha256', $reference . $amountInCents . $currency . $this->integrityKey);

        $result = $this->service->generateSignature($reference, $amountInCents, $currency);

        $this->assertSame($expected, $result);
    }

    public function test_signature_changes_when_reference_changes(): void
    {
        $sig1 = $this->service->generateSignature('ORD-001', 100000, 'COP');
        $sig2 = $this->service->generateSignature('ORD-002', 100000, 'COP');

        $this->assertNotSame($sig1, $sig2);
    }

    public function test_signature_changes_when_amount_changes(): void
    {
        $sig1 = $this->service->generateSignature('ORD-001', 100000, 'COP');
        $sig2 = $this->service->generateSignature('ORD-001', 200000, 'COP');

        $this->assertNotSame($sig1, $sig2);
    }

    public function test_signature_defaults_to_cop_currency(): void
    {
        $explicit = $this->service->generateSignature('ORD-001', 100000, 'COP');
        $default  = $this->service->generateSignature('ORD-001', 100000);

        $this->assertSame($explicit, $default);
    }

    // ── widgetConfig ─────────────────────────────────────────────────────────

    public function test_widget_config_returns_all_required_keys(): void
    {
        $config = $this->service->widgetConfig('ORD-20260401-ABC123', 5000000);

        $this->assertArrayHasKey('public_key', $config);
        $this->assertArrayHasKey('currency', $config);
        $this->assertArrayHasKey('amount_in_cents', $config);
        $this->assertArrayHasKey('reference', $config);
        $this->assertArrayHasKey('signature', $config);
    }

    public function test_widget_config_includes_correct_values(): void
    {
        $reference     = 'ORD-20260401-XYZ999';
        $amountInCents = 8750000;

        $config = $this->service->widgetConfig($reference, $amountInCents);

        $this->assertSame('pub_test_ABC123', $config['public_key']);
        $this->assertSame('COP', $config['currency']);
        $this->assertSame($amountInCents, $config['amount_in_cents']);
        $this->assertSame($reference, $config['reference']);
    }

    public function test_widget_config_signature_is_valid(): void
    {
        $reference     = 'ORD-20260401-XYZ999';
        $amountInCents = 8750000;

        $config = $this->service->widgetConfig($reference, $amountInCents);

        $expectedSig = $this->service->generateSignature($reference, $amountInCents, 'COP');

        $this->assertSame($expectedSig, $config['signature']);
    }

    // ── verifyWebhookEvent ───────────────────────────────────────────────────

    public function test_it_verifies_a_valid_webhook_event(): void
    {
        $payload = $this->buildValidWebhookPayload([
            'id'              => 'TXN_XYZ',
            'status'          => 'APPROVED',
            'amount_in_cents' => 5000000,
            'reference'       => 'ORD-20260401-ABC123',
        ]);

        $this->assertTrue($this->service->verifyWebhookEvent($payload));
    }

    public function test_it_rejects_payload_with_tampered_checksum(): void
    {
        $payload = $this->buildValidWebhookPayload([
            'id'              => 'TXN_XYZ',
            'status'          => 'APPROVED',
            'amount_in_cents' => 5000000,
            'reference'       => 'ORD-20260401-ABC123',
        ]);

        $payload['signature']['checksum'] = 'invalid_checksum_value';

        $this->assertFalse($this->service->verifyWebhookEvent($payload));
    }

    public function test_it_rejects_payload_with_wrong_events_key(): void
    {
        // Payload built with wrong key
        $payload = $this->buildValidWebhookPayload(
            [
                'id'              => 'TXN_XYZ',
                'status'          => 'APPROVED',
                'amount_in_cents' => 5000000,
                'reference'       => 'ORD-20260401-ABC123',
            ],
            eventsKey: 'wrong_key'
        );

        $this->assertFalse($this->service->verifyWebhookEvent($payload));
    }

    public function test_it_rejects_payload_with_missing_signature(): void
    {
        $payload = [
            'event'     => 'transaction.updated',
            'data'      => ['transaction' => ['id' => 'TXN_XYZ']],
            'timestamp' => (string) time(),
        ];

        // No 'signature' key at all — checksum will be empty string,
        // which won't match the expected hash.
        $this->assertFalse($this->service->verifyWebhookEvent($payload));
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Build a Wompi webhook payload with a valid computed checksum.
     */
    private function buildValidWebhookPayload(
        array $transaction,
        string $timestamp = null,
        string $eventsKey = null
    ): array {
        $timestamp = $timestamp ?? (string) time();
        $eventsKey = $eventsKey ?? $this->eventsKey;

        $properties = ['transaction.id', 'transaction.status', 'transaction.amount_in_cents'];

        $data = ['transaction' => $transaction];

        $concatenated = '';
        foreach ($properties as $property) {
            $concatenated .= data_get($data, $property);
        }
        $concatenated .= $timestamp;
        $concatenated .= $eventsKey;

        return [
            'event'     => 'transaction.updated',
            'data'      => $data,
            'signature' => [
                'properties' => $properties,
                'checksum'   => hash('sha256', $concatenated),
            ],
            'timestamp' => $timestamp,
        ];
    }
}
