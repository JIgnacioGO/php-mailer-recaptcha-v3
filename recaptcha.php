<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

/**
 * Verifies a reCAPTCHA token against Google's siteverify API.
 *
 * @return array|null Parsed response from Google, or null on network/parse failure.
 */
function verify_recaptcha_token(string $token, string $remoteIp = ''): ?array
{
    $ch = curl_init('https://www.google.com/recaptcha/api/siteverify');

    if ($ch === false) {
        error_log('[recaptcha] curl_init failed');
        return null;
    }

    curl_setopt_array($ch, array(
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query(array(
            'secret'   => RECAPTCHA_SECRET_KEY,
            'response' => $token,
            /* NOTE: Behind a reverse proxy, REMOTE_ADDR is the proxy IP.
               If needed, read X-Forwarded-For from a trusted proxy instead. */
            'remoteip' => $remoteIp,
        )),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_TIMEOUT        => 10,
    ));

    $response = curl_exec($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($response === false || $curlError !== '' || $httpCode < 200 || $httpCode >= 300) {
        error_log('[recaptcha] Verification failed: HTTP ' . $httpCode . ' — ' . $curlError);
        return null;
    }

    $result = json_decode($response, true);

    return is_array($result) ? $result : null;
}
