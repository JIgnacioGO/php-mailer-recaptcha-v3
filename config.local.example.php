<?php
declare(strict_types=1);

/**
 * Configuracion local — COPIA este archivo como config.local.php
 *
 * IMPORTANTE:
 * - config.local.php ya esta en .gitignore; nunca lo subas al repositorio.
 * - APP_ENV = 'development' activa errores en pantalla y expone datos
 *   extra en score.php. Cambialo a 'production' antes de desplegar.
 */

define('APP_ENV', 'development');
define('SITE_NAME', 'Secure Contact Demo');

/* Claves de Google reCAPTCHA v3 — https://www.google.com/recaptcha/admin */
define('RECAPTCHA_SITE_KEY', 'tu-site-key');
define('RECAPTCHA_SECRET_KEY', 'tu-secret-key');

/* Umbral de score: float entre 0.0 y 1.0 (por defecto 0.5) */
define('RECAPTCHA_SCORE_THRESHOLD', 0.5);

define('CONTACT_EMAIL', 'destino@ejemplo.com');

define('SMTP_HOST', 'smtp.ejemplo.com');
/* Puerto: 587 (STARTTLS) o 465 (SMTPS) */
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'usuario@ejemplo.com');
define('SMTP_PASSWORD', 'tu-password');
define('SMTP_FROM_EMAIL', 'usuario@ejemplo.com');
define('SMTP_FROM_NAME', 'Secure Contact Demo');
/* Cifrado: 'tls' (STARTTLS), 'ssl' (SMTPS) o '' (sin cifrado, no recomendado) */
define('SMTP_ENCRYPTION', 'tls');

define('MAIL_SUBJECT_PREFIX', '[Secure Contact Demo]');
