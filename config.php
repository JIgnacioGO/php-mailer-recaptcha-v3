<?php
declare(strict_types=1);

/**
 * Configuracion base del demo.
 *
 * Para trabajar localmente sin exponer secretos en GitHub:
 * 1. Copia config.local.example.php a config.local.php
 * 2. Completa tus claves reales de reCAPTCHA y SMTP
 * 3. Nunca subas config.local.php al repositorio
 */

$localConfig = __DIR__ . '/config.local.php';

if (is_file($localConfig)) {
    require $localConfig;
}

function config_env($key, $default = '')
{
    $value = getenv($key);

    if ($value === false && isset($_ENV[$key])) {
        $value = $_ENV[$key];
    }

    if ($value === false || $value === null || $value === '') {
        return $default;
    }

    return $value;
}

function define_config($name, $value)
{
    if (!defined($name)) {
        define($name, $value);
    }
}

define_config('APP_ENV', config_env('APP_ENV', 'production'));
define_config('SITE_NAME', config_env('SITE_NAME', 'Secure Contact Demo'));

define_config('RECAPTCHA_SITE_KEY', config_env('RECAPTCHA_SITE_KEY', ''));
define_config('RECAPTCHA_SECRET_KEY', config_env('RECAPTCHA_SECRET_KEY', ''));

$rawThreshold = (float) config_env('RECAPTCHA_SCORE_THRESHOLD', '0.5');
if ($rawThreshold < 0.0 || $rawThreshold > 1.0) {
    $rawThreshold = 0.5;
}
define_config('RECAPTCHA_SCORE_THRESHOLD', $rawThreshold);
unset($rawThreshold);

define_config('CONTACT_EMAIL', config_env('CONTACT_EMAIL', ''));

define_config('SMTP_HOST', config_env('SMTP_HOST', ''));
define_config('SMTP_PORT', (int) config_env('SMTP_PORT', '587'));
define_config('SMTP_USERNAME', config_env('SMTP_USERNAME', ''));
define_config('SMTP_PASSWORD', config_env('SMTP_PASSWORD', ''));
define_config('SMTP_FROM_EMAIL', config_env('SMTP_FROM_EMAIL', SMTP_USERNAME));
define_config('SMTP_FROM_NAME', config_env('SMTP_FROM_NAME', SITE_NAME));

$rawEncryption = strtolower((string) config_env('SMTP_ENCRYPTION', 'tls'));
if (!in_array($rawEncryption, array('tls', 'ssl', ''), true)) {
    $rawEncryption = 'tls';
}
define_config('SMTP_ENCRYPTION', $rawEncryption);
unset($rawEncryption);

define_config('MAIL_SUBJECT_PREFIX', config_env('MAIL_SUBJECT_PREFIX', '[' . SITE_NAME . ']'));

/* ── Fail-fast: warn if critical secrets are missing ── */
if (php_sapi_name() !== 'cli') {
    $missingSecrets = array();
    foreach (array('RECAPTCHA_SITE_KEY', 'RECAPTCHA_SECRET_KEY', 'CONTACT_EMAIL', 'SMTP_HOST', 'SMTP_USERNAME', 'SMTP_PASSWORD') as $_key) {
        if (defined($_key) && constant($_key) === '') {
            $missingSecrets[] = $_key;
        }
    }
    if ($missingSecrets) {
        error_log('[config.php] Missing required configuration: ' . implode(', ', $missingSecrets));
    }
    unset($missingSecrets, $_key);
}

if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
    ini_set('display_errors', '0');
}
