<?php

/**
 * Forward callback Lazismu ke API Markaz:
 * GET|POST /api/finance/qris/push-notif?token=…
 *
 * Variabel dari pushNotif.php: $token, $transactionQrId, $vano, $amount,
 * $transactionId, $responseTimestamp, $markazQrisConfig (dari connectMarkaz.php).
 */

if (! function_exists('markaz_push_notif_response')) {
    function markaz_push_notif_response(int $httpCode, array $payload): void
    {
        http_response_code($httpCode);
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }
}

if (! function_exists('markaz_forward_push_notif')) {
    /**
     * @return array{http: int, payload: array<string, mixed>}
     */
    function markaz_forward_push_notif(string $url, string $token, int $timeout = 20): array
    {
        $endpoint = rtrim($url, "?&\r\n\t ");
        $separator = strpos($endpoint, '?') === false ? '?' : '&';
        $fullUrl = $endpoint.$separator.'token='.rawurlencode($token);

        $ch = curl_init($fullUrl);
        if ($ch === false) {
            return [
                'http' => 500,
                'payload' => [
                    'responseCode' => '01',
                    'responseMessage' => 'Gagal inisialisasi koneksi ke Markaz',
                    'responseTimestamp' => date('Y-m-d H:i:s'),
                ],
            ];
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
            ],
            CURLOPT_POSTFIELDS => json_encode(['token' => $token], JSON_UNESCAPED_UNICODE),
            CURLOPT_TIMEOUT => max(5, $timeout),
            CURLOPT_CONNECTTIMEOUT => 5,
        ]);

        $body = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errno) {
            return [
                'http' => 500,
                'payload' => [
                    'responseCode' => '01',
                    'responseMessage' => 'Gagal menghubungi Markaz: '.$error,
                    'responseTimestamp' => date('Y-m-d H:i:s'),
                ],
            ];
        }

        $decoded = json_decode((string) $body, true);
        if (! is_array($decoded)) {
            $snippet = substr(preg_replace('/\s+/', ' ', (string) $body) ?? (string) $body, 0, 180);

            return [
                'http' => $status >= 100 ? $status : 500,
                'payload' => [
                    'responseCode' => '01',
                    'responseMessage' => 'Respons Markaz tidak valid'.($snippet !== '' ? ': '.$snippet : ''),
                    'responseTimestamp' => date('Y-m-d H:i:s'),
                ],
            ];
        }

        return [
            'http' => $status >= 100 ? $status : 200,
            'payload' => $decoded,
        ];
    }
}

if (! isset($markazQrisConfig) || ! is_array($markazQrisConfig)) {
    markaz_push_notif_response(500, [
        'responseCode' => '01',
        'responseMessage' => 'Konfigurasi Markaz belum dimuat',
        'responseTimestamp' => date('Y-m-d H:i:s'),
    ]);
}

$pushUrl = trim((string) ($markazQrisConfig['push_notif_url'] ?? ''));
if ($pushUrl === '') {
    markaz_push_notif_response(500, [
        'responseCode' => '01',
        'responseMessage' => 'URL push-notif Markaz belum dikonfigurasi',
        'responseTimestamp' => date('Y-m-d H:i:s'),
    ]);
}

if (empty($token) || ! is_string($token)) {
    markaz_push_notif_response(400, [
        'responseCode' => '01',
        'responseMessage' => 'Token tidak ditemukan',
        'responseTimestamp' => date('Y-m-d H:i:s'),
        'transactionQrId' => $transactionQrId ?? null,
        'vano' => $vano ?? null,
    ]);
}

$result = markaz_forward_push_notif(
    $pushUrl,
    $token,
    (int) ($markazQrisConfig['timeout'] ?? 20)
);

markaz_push_notif_response($result['http'], $result['payload']);
