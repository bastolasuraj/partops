<?php
declare(strict_types=1);

namespace PartOps\Services;

class QRService
{
    private static function getSigningKey(): string
    {
        return $_ENV['QR_SIGNING_KEY'] ?? $_ENV['SESSION_SECRET'] ?? 'default-signing-key';
    }

    public static function generatePayload(int $partId, ?int $expiresAt = null): string
    {
        $data = [
            'part_id' => $partId,
            'issued_at' => time(),
            'expires_at' => $expiresAt
        ];

        $json = json_encode($data);
        $signature = hash_hmac('sha256', $json, self::getSigningKey());
        
        return base64_encode($json . '.' . $signature);
    }

    public static function validatePayload(string $payload): ?array
    {
        $decoded = base64_decode($payload, true);
        if (!$decoded) {
            return null;
        }

        $parts = explode('.', $decoded);
        if (count($parts) !== 2) {
            return null;
        }

        [$json, $signature] = $parts;
        $expectedSignature = hash_hmac('sha256', $json, self::getSigningKey());

        if (!hash_equals($expectedSignature, $signature)) {
            return null;
        }

        $data = json_decode($json, true);
        if (!$data) {
            return null;
        }

        if (isset($data['expires_at']) && $data['expires_at'] && time() > $data['expires_at']) {
            return null;
        }

        return $data;
    }

    public static function getPartIdFromPayload(string $payload): ?int
    {
        $data = self::validatePayload($payload);
        return $data['part_id'] ?? null;
    }
}
