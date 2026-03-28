<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

$cspNonce = base64_encode(random_bytes(16));

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=()');
header("Content-Security-Policy: default-src 'self'; "
    . "script-src 'self' 'nonce-" . $cspNonce . "' 'strict-dynamic' 'unsafe-inline' https://www.google.com https://www.gstatic.com; "
    . "style-src 'self' https://fonts.googleapis.com 'unsafe-inline'; "
    . "font-src https://fonts.gstatic.com; "
    . "frame-src https://www.google.com https://recaptcha.google.com; "
    . "connect-src 'self' https://www.google.com; "
    . "img-src 'self' data:");

session_start();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['csrf_token'];

$status = isset($_GET['status']) ? (string) $_GET['status'] : '';
$statusMessages = array(
    'success' => array(
        'tone' => 'success',
        'es' => 'Mensaje enviado correctamente. Si el SMTP está bien configurado, debería llegar en unos minutos.',
        'en' => 'Message sent successfully. If SMTP is properly configured, it should arrive shortly.',
    ),
    'validation_error' => array(
        'tone' => 'error',
        'es' => 'Revisa los campos obligatorios del formulario antes de enviarlo.',
        'en' => 'Please check the required form fields before submitting.',
    ),
    'security_error' => array(
        'tone' => 'error',
        'es' => 'La verificación de seguridad falló o no pudo completarse. Intenta nuevamente.',
        'en' => 'Security verification failed or could not be completed. Please try again.',
    ),
    'mail_error' => array(
        'tone' => 'error',
        'es' => 'No fue posible enviar el correo. Revisa la configuración SMTP e intenta otra vez.',
        'en' => 'Could not send the email. Please check SMTP settings and try again.',
    ),
    'config_error' => array(
        'tone' => 'error',
        'es' => 'Falta configurar reCAPTCHA o SMTP. El formulario queda visible, pero el envío se mantiene deshabilitado.',
        'en' => 'reCAPTCHA or SMTP not configured. The form is visible but submission remains disabled.',
    ),
    'dependencies_error' => array(
        'tone' => 'error',
        'es' => 'Faltan dependencias PHP. Ejecuta composer install antes de probar el envío del formulario.',
        'en' => 'Missing PHP dependencies. Run composer install before testing the form.',
    ),
    'invalid_request' => array(
        'tone' => 'error',
        'es' => 'La petición no es válida. Accede al formulario desde esta página.',
        'en' => 'Invalid request. Please access the form from this page.',
    ),
);

$flash = isset($statusMessages[$status]) ? $statusMessages[$status] : null;
$isRecaptchaConfigured = RECAPTCHA_SITE_KEY !== '';
$isMailConfigured =
    CONTACT_EMAIL !== '' &&
    SMTP_HOST !== '' &&
    SMTP_USERNAME !== '' &&
    SMTP_PASSWORD !== '';
$canSubmit = $isRecaptchaConfigured && $isMailConfigured;
$siteName = htmlspecialchars(SITE_NAME, ENT_QUOTES, 'UTF-8');
$threshold = (float) RECAPTCHA_SCORE_THRESHOLD;
$formPage = basename(isset($_SERVER['SCRIPT_NAME']) ? (string) $_SERVER['SCRIPT_NAME'] : 'web.php');
if ($formPage === '') {
    $formPage = 'web.php';
}
?>
<!DOCTYPE html>
<html lang="es" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $siteName; ?> | reCAPTCHA v3</title>
    <meta name="description" content="Demo de formulario PHP protegido con Google reCAPTCHA v3. Score en vivo y envío con PHPMailer.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<?php if ($isRecaptchaConfigured): ?>
    <script nonce="<?php echo htmlspecialchars($cspNonce, ENT_QUOTES, 'UTF-8'); ?>" src="https://www.google.com/recaptcha/api.js?render=<?php echo htmlspecialchars(RECAPTCHA_SITE_KEY, ENT_QUOTES, 'UTF-8'); ?>"></script>
<?php endif; ?>
    <style>
        /* ── Theme: Light (default) — Sapphire Blue ── */
        :root,
        [data-theme="light"] {
            --bg: #f0f4ff;
            --bg-alt: #e8eeff;
            --bg-end: #edf1ff;
            --surface: rgba(255, 255, 255, 0.80);
            --surface-strong: rgba(255, 255, 255, 0.94);
            --surface-form: linear-gradient(180deg, rgba(255, 255, 255, 0.96) 0%, rgba(240, 244, 255, 0.93) 100%);
            --ink: #0f172a;
            --muted: #64748b;
            --line: rgba(15, 23, 42, 0.10);
            --accent: #2563eb;
            --accent-deep: #1d4ed8;
            --accent-soft: rgba(37, 99, 235, 0.10);
            --accent-glow-a: rgba(37, 99, 235, 0.16);
            --accent-glow-b: rgba(96, 165, 250, 0.12);
            --error: #dc2626;
            --error-soft: rgba(220, 38, 38, 0.08);
            --success: #2563eb;
            --success-soft: rgba(37, 99, 235, 0.08);
            --amber: #d97706;
            --amber-soft: rgba(217, 119, 6, 0.08);
            --shadow-panel: 0 24px 70px rgba(15, 23, 42, 0.08);
            --shadow-sm: 0 1px 3px rgba(15, 23, 42, 0.06);
            --grid-line: rgba(15, 23, 42, 0.03);
            --toggle-bg: rgba(15, 23, 42, 0.05);
            --toggle-active-bg: var(--surface-strong);
            --toggle-active-shadow: 0 1px 4px rgba(15, 23, 42, 0.10);
            --code-bg: rgba(15, 23, 42, 0.06);
            --score-widget-bg: linear-gradient(135deg, rgba(37, 99, 235, 0.06) 0%, rgba(96, 165, 250, 0.03) 100%);
            --score-widget-border: rgba(37, 99, 235, 0.14);
            --pulse-glow-start: rgba(37, 99, 235, 0.40);
            --pulse-glow-end: rgba(37, 99, 235, 0.00);
            --brand-mark-from: #2563eb;
            --brand-mark-to: #93c5fd;
            --radius-xl: 28px;
            --radius-lg: 22px;
            --radius-md: 16px;
            color-scheme: light;
        }

        /* ── Theme: Dark — Deep Navy ── */
        [data-theme="dark"] {
            --bg: #030712;
            --bg-alt: #0f172a;
            --bg-end: #131c31;
            --surface: rgba(15, 23, 42, 0.88);
            --surface-strong: rgba(30, 41, 59, 0.95);
            --surface-form: linear-gradient(180deg, rgba(30, 41, 59, 0.96) 0%, rgba(15, 23, 42, 0.94) 100%);
            --ink: #e2e8f0;
            --muted: #94a3b8;
            --line: rgba(226, 232, 240, 0.08);
            --accent: #60a5fa;
            --accent-deep: #3b82f6;
            --accent-soft: rgba(96, 165, 250, 0.12);
            --accent-glow-a: rgba(96, 165, 250, 0.12);
            --accent-glow-b: rgba(147, 197, 253, 0.06);
            --error: #fb7185;
            --error-soft: rgba(251, 113, 133, 0.10);
            --success: #60a5fa;
            --success-soft: rgba(96, 165, 250, 0.10);
            --amber: #fbbf24;
            --amber-soft: rgba(251, 191, 36, 0.10);
            --shadow-panel: 0 24px 70px rgba(0, 0, 0, 0.35);
            --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.25);
            --grid-line: rgba(226, 232, 240, 0.02);
            --toggle-bg: rgba(226, 232, 240, 0.06);
            --toggle-active-bg: rgba(226, 232, 240, 0.12);
            --toggle-active-shadow: 0 1px 4px rgba(0, 0, 0, 0.35);
            --code-bg: rgba(226, 232, 240, 0.08);
            --score-widget-bg: linear-gradient(135deg, rgba(96, 165, 250, 0.06) 0%, rgba(147, 197, 253, 0.03) 100%);
            --score-widget-border: rgba(96, 165, 250, 0.14);
            --pulse-glow-start: rgba(96, 165, 250, 0.45);
            --pulse-glow-end: rgba(96, 165, 250, 0.00);
            --brand-mark-from: #3b82f6;
            --brand-mark-to: #60a5fa;
            color-scheme: dark;
        }

        /* ── Reset & Base ── */
        *, *::before, *::after { box-sizing: border-box; }
        html { scroll-behavior: smooth; }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Manrope", sans-serif;
            color: var(--ink);
            background:
                radial-gradient(circle at top left, var(--accent-glow-a), transparent 28%),
                radial-gradient(circle at top right, var(--accent-glow-b), transparent 24%),
                linear-gradient(180deg, var(--bg) 0%, var(--bg-alt) 44%, var(--bg-end) 100%);
            transition: background 0.4s ease, color 0.3s ease;
        }

        body::before {
            content: "";
            position: fixed;
            inset: 0;
            pointer-events: none;
            background-image:
                linear-gradient(var(--grid-line) 1px, transparent 1px),
                linear-gradient(90deg, var(--grid-line) 1px, transparent 1px);
            background-size: 72px 72px;
            mask-image: linear-gradient(180deg, rgba(0, 0, 0, 0.55), transparent 80%);
            z-index: 0;
        }

        /* Accent bar at top of page */
        body::after {
            content: "";
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--accent), var(--accent-deep), var(--accent));
            z-index: 100;
        }

        a { color: inherit; }

        main {
            position: relative;
            z-index: 1;
            max-width: 1180px;
            margin: 0 auto;
            padding: 36px 20px 60px;
        }

        /* ── Typography ── */
        h1, h2 {
            margin: 0;
            font-family: "Fraunces", serif;
            line-height: 0.98;
            letter-spacing: -0.04em;
        }

        h1 {
            margin-top: 20px;
            font-size: clamp(2.6rem, 6vw, 4.2rem);
        }

        h2 {
            font-size: clamp(1.4rem, 3vw, 1.8rem);
        }

        /* ── Header / Topbar ── */
        .topbar {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 28px;
        }

        .brand {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            padding: 12px 18px;
            border: 1px solid var(--line);
            border-radius: 999px;
            background: var(--surface);
            backdrop-filter: blur(10px);
            transition: background 0.3s ease, border-color 0.3s ease;
        }

        .brand-mark {
            width: 12px;
            height: 12px;
            border-radius: 999px;
            background: linear-gradient(135deg, var(--brand-mark-from) 0%, var(--brand-mark-to) 100%);
            box-shadow: 0 0 0 6px var(--accent-soft);
        }

        .brand-copy {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .brand-copy strong {
            font-size: 0.94rem;
            letter-spacing: 0.02em;
        }

        .brand-copy span {
            color: var(--muted);
            font-size: 0.82rem;
        }

        .topbar-controls {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-left: auto;
        }

        /* ── Toggle Controls ── */
        .lang-toggle {
            display: flex;
            padding: 3px;
            border-radius: 999px;
            background: var(--toggle-bg);
            border: 1px solid var(--line);
        }

        .lang-opt {
            padding: 6px 14px;
            border: 0;
            border-radius: 999px;
            background: transparent;
            font-family: inherit;
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            color: var(--muted);
            cursor: pointer;
            transition: all 0.25s ease;
        }

        .lang-opt[aria-checked="true"] {
            background: var(--toggle-active-bg);
            color: var(--ink);
            box-shadow: var(--toggle-active-shadow);
        }

        .lang-opt:focus-visible {
            outline: 2px solid var(--accent);
            outline-offset: 2px;
        }

        .theme-toggle {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 38px;
            height: 38px;
            padding: 0;
            border: 1px solid var(--line);
            border-radius: 999px;
            background: var(--toggle-bg);
            color: var(--muted);
            cursor: pointer;
            transition: all 0.25s ease;
        }

        .theme-toggle:hover {
            color: var(--ink);
            border-color: var(--accent);
        }

        .theme-toggle:focus-visible {
            outline: 2px solid var(--accent);
            outline-offset: 2px;
        }

        .theme-toggle svg {
            width: 18px;
            height: 18px;
            transition: transform 0.3s ease;
        }

        .theme-toggle:hover svg {
            transform: rotate(15deg);
        }

        [data-theme="light"] .icon-moon,
        [data-theme="dark"] .icon-sun { display: none; }

        /* ── Shared Panel ── */
        .panel {
            border: 1px solid var(--line);
            border-radius: var(--radius-xl);
            background: var(--surface);
            backdrop-filter: blur(14px);
            box-shadow: var(--shadow-panel);
            transition: background 0.3s ease, border-color 0.3s ease, box-shadow 0.3s ease;
        }

        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 10px 16px;
            border-radius: 999px;
            background: var(--accent-soft);
            color: var(--accent-deep);
            font-size: 0.82rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .section-label {
            display: inline-flex;
            margin-bottom: 14px;
            color: var(--muted);
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        /* ── Hero Layout ── */
        .hero {
            display: grid;
            grid-template-columns: minmax(0, 1.1fr) minmax(320px, 0.9fr);
            gap: 24px;
            align-items: start;
        }

        .hero-info {
            padding: 44px;
        }

        .hero-info > p {
            color: var(--muted);
            line-height: 1.75;
            font-size: 1.05rem;
            margin: 22px 0 0;
            max-width: 52ch;
        }

        /* ── Score Widget ── */
        .score-widget {
            margin-top: 32px;
            padding: 28px;
            border-radius: var(--radius-lg);
            background: var(--score-widget-bg);
            border: 1px solid var(--score-widget-border);
            display: flex;
            align-items: center;
            gap: 28px;
            transition: background 0.3s ease, border-color 0.3s ease;
        }

        .score-gauge {
            position: relative;
            flex-shrink: 0;
            width: 140px;
            height: 140px;
        }

        .score-ring {
            width: 100%;
            height: 100%;
            transform: rotate(-90deg);
        }

        .score-track {
            fill: none;
            stroke: var(--line);
            stroke-width: 7;
        }

        .score-fill {
            fill: none;
            stroke: var(--accent);
            stroke-width: 7;
            stroke-linecap: round;
            stroke-dasharray: 0 326.73;
            transition: stroke-dasharray 1.2s cubic-bezier(0.22, 1, 0.36, 1), stroke 0.6s ease;
        }

        .score-center {
            position: absolute;
            inset: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            pointer-events: none;
        }

        .score-number {
            font-family: "Fraunces", serif;
            font-size: 2.4rem;
            font-weight: 700;
            line-height: 1;
            color: var(--muted);
            transition: color 0.6s ease;
        }

        .score-tag {
            margin-top: 4px;
            font-size: 0.72rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--muted);
            transition: color 0.6s ease;
        }

        .score-detail {
            flex: 1;
            min-width: 0;
        }

        .score-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }

        .score-title {
            font-size: 1rem;
            font-weight: 800;
        }

        .live-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: 999px;
            background: var(--success-soft);
            color: var(--success);
            font-size: 0.72rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }

        .live-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: var(--success);
            animation: pulse 2s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; box-shadow: 0 0 0 0 var(--pulse-glow-start); }
            50% { opacity: 0.6; box-shadow: 0 0 0 6px var(--pulse-glow-end); }
        }

        .score-desc {
            margin: 0;
            color: var(--muted);
            font-size: 0.92rem;
            line-height: 1.6;
        }

        .score-threshold {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-top: 10px;
            padding: 6px 12px;
            border-radius: 999px;
            background: var(--code-bg);
            font-size: 0.82rem;
            color: var(--muted);
        }

        .score-threshold code {
            display: inline;
            padding: 2px 7px;
            border-radius: 999px;
            background: var(--code-bg);
            font-family: "SFMono-Regular", Consolas, "Liberation Mono", Menlo, monospace;
            font-size: 0.8rem;
        }

        .score-disabled {
            text-align: center;
            padding: 24px;
            color: var(--muted);
            font-size: 0.92rem;
        }

        /* ── Form Card ── */
        .form-card {
            padding: 34px;
            background: var(--surface-form);
        }

        .form-card > p {
            color: var(--muted);
            line-height: 1.65;
            margin: 6px 0 0;
            font-size: 0.94rem;
        }

        .notice {
            margin-top: 18px;
            padding: 14px 18px;
            border-radius: 18px;
            border: 1px solid transparent;
            font-size: 0.92rem;
            line-height: 1.6;
        }

        .notice.success {
            color: var(--success);
            background: var(--success-soft);
            border-color: var(--score-widget-border);
        }

        .notice.error {
            color: var(--error);
            background: var(--error-soft);
            border-color: rgba(220, 38, 38, 0.16);
        }

        [data-theme="dark"] .notice.error {
            border-color: rgba(251, 113, 133, 0.18);
        }

        .form-card form {
            display: grid;
            gap: 14px;
            margin-top: 20px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }

        .field {
            display: grid;
            gap: 8px;
        }

        .field.full {
            grid-column: 1 / -1;
        }

        label {
            font-size: 0.9rem;
            font-weight: 700;
        }

        input, textarea {
            width: 100%;
            padding: 14px 16px;
            border: 1px solid var(--line);
            border-radius: var(--radius-md);
            background: var(--surface-strong);
            color: var(--ink);
            font: inherit;
            font-size: 0.95rem;
            transition: border-color 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;
        }

        input::placeholder, textarea::placeholder {
            color: var(--muted);
            opacity: 0.6;
        }

        input:focus, textarea:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 4px var(--accent-soft);
            transform: translateY(-1px);
        }

        textarea {
            min-height: 130px;
            resize: vertical;
        }

        button[type="submit"] {
            min-height: 52px;
            border: 0;
            border-radius: 999px;
            background: linear-gradient(135deg, var(--accent) 0%, var(--accent-deep) 100%);
            color: #fff;
            font: inherit;
            font-weight: 800;
            font-size: 0.95rem;
            cursor: pointer;
            transition: transform 0.2s ease, filter 0.2s ease, opacity 0.2s ease, box-shadow 0.2s ease;
            box-shadow: 0 4px 14px rgba(37, 99, 235, 0.30);
        }

        button[type="submit"]:hover:not(:disabled) {
            transform: translateY(-2px);
            filter: brightness(1.06);
            box-shadow: 0 6px 20px rgba(37, 99, 235, 0.35);
        }

        button[type="submit"]:active:not(:disabled) {
            transform: translateY(0);
        }

        button[type="submit"]:disabled {
            cursor: not-allowed;
            opacity: 0.5;
            filter: grayscale(0.3);
            box-shadow: none;
        }

        .form-footnote {
            margin: 0;
            font-size: 0.82rem;
            color: var(--muted);
        }

        code {
            display: inline;
            padding: 3px 8px;
            border-radius: 999px;
            background: var(--code-bg);
            font-family: "SFMono-Regular", Consolas, "Liberation Mono", Menlo, monospace;
            font-size: 0.82rem;
        }

        /* ── How It Works ── */
        .section-heading {
            margin-top: 48px;
            margin-bottom: 6px;
            text-align: center;
        }

        .section-heading h2 {
            margin-bottom: 8px;
        }

        .section-heading p {
            color: var(--muted);
            font-size: 0.95rem;
            margin: 0;
        }

        .how-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-top: 22px;
        }

        .how-card {
            padding: 30px;
            position: relative;
        }

        .how-step {
            position: absolute;
            top: 22px;
            right: 26px;
            font-family: "Fraunces", serif;
            font-size: 2.8rem;
            font-weight: 700;
            line-height: 1;
            color: var(--line);
            pointer-events: none;
            user-select: none;
        }

        .how-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 46px;
            height: 46px;
            border-radius: 14px;
            background: var(--accent-soft);
            color: var(--accent-deep);
            margin-bottom: 18px;
        }

        .how-icon svg {
            width: 22px;
            height: 22px;
            stroke-width: 2;
            fill: none;
            stroke: currentColor;
        }

        .how-card strong {
            display: block;
            margin-bottom: 10px;
            font-size: 1.02rem;
        }

        .how-card p {
            margin: 0;
            color: var(--muted);
            font-size: 0.9rem;
            line-height: 1.7;
        }

        /* ── Footer ── */
        .site-footer {
            margin: 44px auto 0;
            text-align: center;
            color: var(--muted);
            font-size: 0.84rem;
            line-height: 1.8;
        }

        .site-footer a {
            color: var(--accent);
            text-decoration: none;
        }

        .site-footer a:hover {
            text-decoration: underline;
        }

        .footer-divider {
            display: block;
            width: 40px;
            height: 2px;
            margin: 0 auto 14px;
            border: 0;
            background: var(--line);
            border-radius: 2px;
        }

        /* ── Entrance Animation ── */
        .reveal {
            opacity: 0;
            transform: translateY(20px);
            transition: opacity 0.6s ease, transform 0.6s ease;
        }

        .reveal.visible {
            opacity: 1;
            transform: translateY(0);
        }

        /* reCAPTCHA badge: visible by default; attribution also in footer */

        /* ── Responsive ── */
        @media (max-width: 980px) {
            .hero {
                grid-template-columns: 1fr;
            }

            .how-grid {
                grid-template-columns: 1fr;
            }

            .hero-info, .form-card, .how-card {
                padding: 28px;
            }

            .topbar {
                flex-wrap: wrap;
                gap: 12px;
            }
        }

        @media (max-width: 640px) {
            main {
                padding-left: 14px;
                padding-right: 14px;
            }

            .hero-info, .form-card, .how-card {
                padding: 22px;
                border-radius: 22px;
            }

            h1 {
                font-size: clamp(2.2rem, 12vw, 3rem);
            }

            .score-widget {
                flex-direction: column;
                text-align: center;
                gap: 20px;
            }

            .score-header {
                justify-content: center;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .topbar-controls {
                width: 100%;
                justify-content: flex-end;
            }
        }
    </style>
    <script nonce="<?php echo htmlspecialchars($cspNonce, ENT_QUOTES, 'UTF-8'); ?>">
        /* Apply saved theme before first paint to prevent flash */
        (function () {
            var saved = localStorage.getItem('theme');
            var theme = saved || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
            document.documentElement.setAttribute('data-theme', theme);
        }());
    </script>
</head>
<body>
    <main>
        <header class="topbar">
            <div class="brand">
                <span class="brand-mark" aria-hidden="true"></span>
                <div class="brand-copy">
                    <strong><?php echo $siteName; ?></strong>
                    <span>reCAPTCHA v3 &middot; PHP &middot; PHPMailer</span>
                </div>
            </div>

            <div class="topbar-controls">
                <div class="lang-toggle" role="radiogroup" data-i18n-aria-label="langToggleLabel" aria-label="Idioma">
                    <button type="button" class="lang-opt" data-lang-opt="es" role="radio" aria-checked="true">ES</button>
                    <button type="button" class="lang-opt" data-lang-opt="en" role="radio" aria-checked="false">EN</button>
                </div>
                <button type="button" class="theme-toggle" aria-label="Cambiar a tema oscuro">
                    <svg class="icon-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
                    <svg class="icon-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
                </button>
            </div>
        </header>

        <section class="hero">
            <article class="panel hero-info reveal">
                <span class="eyebrow" data-i18n="eyebrow">Demo técnico</span>
                <h1 data-i18n="heroTitle">reCAPTCHA v3 en acción.</h1>
                <p data-i18n="heroDesc">Un formulario de contacto con verificación invisible de Google. Observa tu score actualizarse en tiempo real &mdash; sin puzzles, sin fricciones.</p>

<?php if ($isRecaptchaConfigured): ?>
                <div class="score-widget">
                    <div class="score-gauge">
                        <svg class="score-ring" viewBox="0 0 120 120">
                            <circle class="score-track" cx="60" cy="60" r="52"/>
                            <circle class="score-fill" id="scoreFill" cx="60" cy="60" r="52"/>
                        </svg>
                        <div class="score-center">
                            <span class="score-number" id="scoreNumber">&mdash;</span>
                            <span class="score-tag" id="scoreTag" data-i18n="scoreLoading">cargando</span>
                        </div>
                    </div>
                    <div class="score-detail">
                        <div class="score-header">
                            <span class="score-title" data-i18n="scoreTitle">Tu score</span>
                            <span class="live-badge"><span class="live-dot"></span> <span data-i18n="scoreLive">En vivo</span></span>
                        </div>
                        <p class="score-desc" id="scoreDesc" data-i18n="scoreEvaluating">Evaluando tu interacción con reCAPTCHA v3...</p>
                        <div class="score-threshold">
                            <span data-i18n="scoreThresholdLabel">Umbral configurado:</span> <code><?php echo $threshold; ?></code>
                        </div>
                    </div>
                </div>
<?php else: ?>
                <div class="score-widget">
                    <div class="score-disabled" data-i18n-html="scoreDisabled">
                        Configura las claves de reCAPTCHA en <code>config.local.php</code> para ver el score en vivo.
                    </div>
                </div>
<?php endif; ?>
            </article>

            <aside class="panel form-card reveal" id="contact">
                <span class="section-label" data-i18n="formLabel">Formulario protegido</span>
                <h2 data-i18n="formTitle">Envía un mensaje de prueba.</h2>
                <p data-i18n="formDesc">Protegido con reCAPTCHA v3. El token se genera al enviar y se valida en el backend.</p>

<?php if ($flash): ?>
                <div class="notice <?php echo htmlspecialchars($flash['tone'], ENT_QUOTES, 'UTF-8'); ?>"
                     data-text-es="<?php echo htmlspecialchars($flash['es'], ENT_QUOTES, 'UTF-8'); ?>"
                     data-text-en="<?php echo htmlspecialchars($flash['en'], ENT_QUOTES, 'UTF-8'); ?>">
                    <?php echo htmlspecialchars($flash['es'], ENT_QUOTES, 'UTF-8'); ?>
                </div>
<?php endif; ?>

<?php if (!$canSubmit): ?>
                <div class="notice error"
                     data-text-es="Completa config.local.php con tus claves de reCAPTCHA y SMTP para habilitar el envío."
                     data-text-en="Complete config.local.php with your reCAPTCHA and SMTP keys to enable submission.">
                    Completa <code>config.local.php</code> con tus claves de reCAPTCHA y SMTP para habilitar el envío.
                </div>
<?php endif; ?>

                <form id="contactForm" action="contact.php" method="post">
                    <div class="form-grid">
                        <div class="field">
                            <label for="visitor_name" data-i18n="labelName">Nombre</label>
                            <input id="visitor_name" name="visitor_name" type="text" autocomplete="name" required
                                   data-i18n-placeholder="placeholderName" placeholder="">
                        </div>
                        <div class="field">
                            <label for="visitor_email" data-i18n="labelEmail">Correo</label>
                            <input id="visitor_email" name="visitor_email" type="email" autocomplete="email" required
                                   data-i18n-placeholder="placeholderEmail" placeholder="">
                        </div>
                        <div class="field full">
                            <label for="email_title" data-i18n="labelSubject">Asunto</label>
                            <input id="email_title" name="email_title" type="text" autocomplete="off"
                                   data-i18n-placeholder="placeholderSubject" placeholder="Consulta general">
                        </div>
                        <div class="field full">
                            <label for="visitor_message" data-i18n="labelMessage">Mensaje</label>
                            <textarea id="visitor_message" name="visitor_message" required
                                      data-i18n-placeholder="placeholderMessage" placeholder="Escribe aquí tu mensaje..."></textarea>
                        </div>
                    </div>

                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="recaptcha_token" id="recaptchaToken">
                    <input type="hidden" name="form_page" value="<?php echo htmlspecialchars($formPage, ENT_QUOTES, 'UTF-8'); ?>">

                    <button type="submit" <?php echo $canSubmit ? '' : 'disabled'; ?> data-i18n="submitBtn">Enviar mensaje</button>
                    <p class="form-footnote">
                        <span data-i18n="footAction">Acción:</span> <code>contact_form</code> &middot;
                        <span data-i18n="footThreshold">Umbral:</span> <code><?php echo $threshold; ?></code>
                    </p>
                </form>
            </aside>
        </section>

        <div class="section-heading reveal">
            <span class="eyebrow" data-i18n="howEyebrow">Cómo funciona</span>
            <h2 data-i18n="howTitle">Protección en tres pasos.</h2>
            <p data-i18n="howSubtitle">Sin interrupciones para el usuario, máxima seguridad para tu sitio.</p>
        </div>

        <section class="how-grid">
            <article class="panel how-card reveal">
                <span class="how-step" aria-hidden="true">01</span>
                <div class="how-icon">
                    <svg viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                </div>
                <strong data-i18n="howAnalysisTitle">Análisis invisible</strong>
                <p data-i18n="howAnalysisDesc">Cuando cargaste esta página, reCAPTCHA v3 comenzó a observar. Analiza patrones de movimiento, velocidad de escritura y navegación para distinguir humanos de bots &mdash; sin interrumpirte.</p>
            </article>
            <article class="panel how-card reveal">
                <span class="how-step" aria-hidden="true">02</span>
                <div class="how-icon">
                    <svg viewBox="0 0 24 24"><path d="M18 20V10"/><path d="M12 20V4"/><path d="M6 20v-6"/></svg>
                </div>
                <strong data-i18n="howScoreTitle">Score de confianza</strong>
                <p data-i18n="howScoreDesc">Cada interacción recibe un puntaje de 0.0 (bot) a 1.0 (humano). Cuanto más natural sea tu comportamiento, más alto el score. El indicador de arriba muestra tu score real.</p>
            </article>
            <article class="panel how-card reveal">
                <span class="how-step" aria-hidden="true">03</span>
                <div class="how-icon">
                    <svg viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="M9 12l2 2 4-4"/></svg>
                </div>
                <strong data-i18n="howValidateTitle">Validación en servidor</strong>
                <p data-i18n="howValidateDesc">Al enviar, el backend verifica el token con Google, comprueba que el score supere el umbral ({threshold}) y que la acción coincida, antes de procesar el mensaje.</p>
            </article>
        </section>

        <footer class="site-footer reveal">
            <hr class="footer-divider">
            <p data-i18n="footerMain">Implementación de Google reCAPTCHA v3 con PHP y PHPMailer.</p>
            <p data-i18n-html="footerRecaptcha">
                Este sitio está protegido por reCAPTCHA y aplican la
                <a href="https://policies.google.com/privacy" target="_blank" rel="noopener noreferrer">Política de privacidad</a> y los
                <a href="https://policies.google.com/terms" target="_blank" rel="noopener noreferrer">Términos de servicio</a> de Google.
            </p>
        </footer>
    </main>

    <script id="app-config" type="application/json"><?php
        echo json_encode(array(
            'siteKey'              => RECAPTCHA_SITE_KEY,
            'threshold'            => (float) RECAPTCHA_SCORE_THRESHOLD,
            'formPage'             => $formPage,
            'isRecaptchaConfigured' => $isRecaptchaConfigured,
        ));
    ?></script>

    <script nonce="<?php echo htmlspecialchars($cspNonce, ENT_QUOTES, 'UTF-8'); ?>">
    (function () {
        'use strict';

        var APP = JSON.parse(document.getElementById('app-config').textContent);

        /* ══════════════════════════════════
           i18n — Translation System
           ══════════════════════════════════ */
        var translations = {
            es: {
                eyebrow: 'Demo técnico',
                heroTitle: 'reCAPTCHA v3 en acción.',
                heroDesc: 'Un formulario de contacto con verificación invisible de Google. Observa tu score actualizarse en tiempo real \u2014 sin puzzles, sin fricciones.',
                scoreLoading: 'cargando',
                scoreTitle: 'Tu score',
                scoreLive: 'En vivo',
                scoreEvaluating: 'Evaluando tu interacción con reCAPTCHA v3...',
                scoreThresholdLabel: 'Umbral configurado:',
                scoreHuman: 'Humano',
                scoreUncertain: 'Incierto',
                scoreSuspicious: 'Sospechoso',
                scorePassTpl: 'Score {score} \u2014 supera el umbral de {thr}. Esta interacción sería aceptada.',
                scoreFailTpl: 'Score {score} \u2014 no alcanza el umbral de {thr}. Esta interacción sería rechazada.',
                formLabel: 'Formulario protegido',
                formTitle: 'Envía un mensaje de prueba.',
                formDesc: 'Protegido con reCAPTCHA v3. El token se genera al enviar y se valida en el backend.',
                labelName: 'Nombre',
                labelEmail: 'Correo',
                labelSubject: 'Asunto',
                labelMessage: 'Mensaje',
                placeholderName: '',
                placeholderEmail: '',
                placeholderSubject: 'Consulta general',
                placeholderMessage: 'Escribe aquí tu mensaje...',
                submitBtn: 'Enviar mensaje',
                footAction: 'Acción:',
                footThreshold: 'Umbral:',
                howEyebrow: 'Cómo funciona',
                howTitle: 'Protección en tres pasos.',
                howSubtitle: 'Sin interrupciones para el usuario, máxima seguridad para tu sitio.',
                howAnalysisTitle: 'Análisis invisible',
                howAnalysisDesc: 'Cuando cargaste esta página, reCAPTCHA v3 comenzó a observar. Analiza patrones de movimiento, velocidad de escritura y navegación para distinguir humanos de bots \u2014 sin interrumpirte.',
                howScoreTitle: 'Score de confianza',
                howScoreDesc: 'Cada interacción recibe un puntaje de 0.0 (bot) a 1.0 (humano). Cuanto más natural sea tu comportamiento, más alto el score. El indicador de arriba muestra tu score real.',
                howValidateTitle: 'Validación en servidor',
                howValidateDesc: 'Al enviar, el backend verifica el token con Google, comprueba que el score supere el umbral ({threshold}) y que la acción coincida, antes de procesar el mensaje.',
                footerMain: 'Implementación de Google reCAPTCHA v3 con PHP y PHPMailer.',
                footerRecaptcha: 'Este sitio está protegido por reCAPTCHA y aplican la <a href="https://policies.google.com/privacy" target="_blank" rel="noopener noreferrer">Política de privacidad</a> y los <a href="https://policies.google.com/terms" target="_blank" rel="noopener noreferrer">Términos de servicio</a> de Google.',
                scoreDisabled: 'Configura las claves de reCAPTCHA en <code>config.local.php</code> para ver el score en vivo.',
                langToggleLabel: 'Idioma',
                themeLight: 'Cambiar a tema claro',
                themeDark: 'Cambiar a tema oscuro'
            },
            en: {
                eyebrow: 'Technical Demo',
                heroTitle: 'reCAPTCHA v3 in action.',
                heroDesc: 'A contact form with Google\'s invisible verification. Watch your score update in real time \u2014 no puzzles, no friction.',
                scoreLoading: 'loading',
                scoreTitle: 'Your score',
                scoreLive: 'Live',
                scoreEvaluating: 'Evaluating your interaction with reCAPTCHA v3...',
                scoreThresholdLabel: 'Configured threshold:',
                scoreHuman: 'Human',
                scoreUncertain: 'Uncertain',
                scoreSuspicious: 'Suspicious',
                scorePassTpl: 'Score {score} \u2014 exceeds the {thr} threshold. This interaction would be accepted.',
                scoreFailTpl: 'Score {score} \u2014 below the {thr} threshold. This interaction would be rejected.',
                formLabel: 'Protected form',
                formTitle: 'Send a test message.',
                formDesc: 'Protected with reCAPTCHA v3. The token is generated on submit and validated on the backend.',
                labelName: 'Name',
                labelEmail: 'Email',
                labelSubject: 'Subject',
                labelMessage: 'Message',
                placeholderName: '',
                placeholderEmail: '',
                placeholderSubject: 'General inquiry',
                placeholderMessage: 'Write your message here...',
                submitBtn: 'Send message',
                footAction: 'Action:',
                footThreshold: 'Threshold:',
                howEyebrow: 'How it works',
                howTitle: 'Protection in three steps.',
                howSubtitle: 'No interruptions for the user, maximum security for your site.',
                howAnalysisTitle: 'Invisible analysis',
                howAnalysisDesc: 'When you loaded this page, reCAPTCHA v3 started observing. It analyzes movement patterns, typing speed, and browsing behavior to distinguish humans from bots \u2014 without interrupting you.',
                howScoreTitle: 'Trust score',
                howScoreDesc: 'Each interaction receives a score from 0.0 (bot) to 1.0 (human). The more natural your behavior, the higher the score. The indicator above shows your real score.',
                howValidateTitle: 'Server validation',
                howValidateDesc: 'On submit, the backend verifies the token with Google, checks that the score exceeds the threshold ({threshold}) and that the action matches, before processing the message.',
                footerMain: 'Google reCAPTCHA v3 implementation with PHP and PHPMailer.',
                footerRecaptcha: 'This site is protected by reCAPTCHA and the Google <a href="https://policies.google.com/privacy" target="_blank" rel="noopener noreferrer">Privacy Policy</a> and <a href="https://policies.google.com/terms" target="_blank" rel="noopener noreferrer">Terms of Service</a> apply.',
                scoreDisabled: 'Configure your reCAPTCHA keys in <code>config.local.php</code> to see the live score.',
                langToggleLabel: 'Language',
                themeLight: 'Switch to light theme',
                themeDark: 'Switch to dark theme'
            }
        };

        var currentLang = localStorage.getItem('lang') || (navigator.language && navigator.language.indexOf('es') === 0 ? 'es' : 'en');

        function applyLang(lang) {
            if (!translations[lang]) {
                lang = 'en';
            }

            currentLang = lang;
            localStorage.setItem('lang', lang);
            document.documentElement.setAttribute('lang', lang);

            var t = translations[lang];

            /* data-i18n → textContent */
            var nodes = document.querySelectorAll('[data-i18n]');
            for (var i = 0; i < nodes.length; i++) {
                var key = nodes[i].getAttribute('data-i18n');
                if (t[key] !== undefined) {
                    nodes[i].textContent = t[key].replace('{threshold}', APP.threshold);
                }
            }

            /* data-i18n-html → innerHTML */
            var htmlNodes = document.querySelectorAll('[data-i18n-html]');
            for (var j = 0; j < htmlNodes.length; j++) {
                var hKey = htmlNodes[j].getAttribute('data-i18n-html');
                if (t[hKey] !== undefined) {
                    htmlNodes[j].innerHTML = t[hKey];
                }
            }

            /* data-i18n-placeholder → placeholder */
            var phNodes = document.querySelectorAll('[data-i18n-placeholder]');
            for (var k = 0; k < phNodes.length; k++) {
                var pKey = phNodes[k].getAttribute('data-i18n-placeholder');
                if (t[pKey] !== undefined) {
                    phNodes[k].setAttribute('placeholder', t[pKey]);
                }
            }

            /* data-i18n-aria-label → aria-label */
            var ariaNodes = document.querySelectorAll('[data-i18n-aria-label]');
            for (var m = 0; m < ariaNodes.length; m++) {
                var aKey = ariaNodes[m].getAttribute('data-i18n-aria-label');
                if (t[aKey] !== undefined) {
                    ariaNodes[m].setAttribute('aria-label', t[aKey]);
                }
            }

            /* data-text-es / data-text-en → notice messages */
            var notices = document.querySelectorAll('[data-text-es]');
            for (var n = 0; n < notices.length; n++) {
                var text = notices[n].getAttribute('data-text-' + lang);
                if (text) {
                    notices[n].textContent = text;
                }
            }

            /* Update lang toggle buttons */
            var opts = document.querySelectorAll('.lang-opt');
            for (var l = 0; l < opts.length; l++) {
                opts[l].setAttribute('aria-checked', opts[l].getAttribute('data-lang-opt') === lang ? 'true' : 'false');
            }

            syncThemeToggleLabel();

            /* Re-render score description if we have a cached score */
            if (lastScoreData) {
                updateScoreText(lastScoreData);
            }
        }

        /* ══════════════════════════════════
           Theme System
           ══════════════════════════════════ */
        function getTheme() {
            return document.documentElement.getAttribute('data-theme') || 'light';
        }

        function syncThemeToggleLabel() {
            var btn = document.querySelector('.theme-toggle');
            if (!btn) {
                return;
            }

            var t = translations[currentLang] || translations.en;
            btn.setAttribute('aria-label', getTheme() === 'dark' ? t.themeLight : t.themeDark);
        }

        function setTheme(theme) {
            document.documentElement.setAttribute('data-theme', theme);
            localStorage.setItem('theme', theme);
            syncThemeToggleLabel();
        }

        /* ══════════════════════════════════
           Score Widget
           ══════════════════════════════════ */
        var lastScoreData = null;

        var scoreNumber = document.getElementById('scoreNumber');
        var scoreTag    = document.getElementById('scoreTag');
        var scoreDesc   = document.getElementById('scoreDesc');
        var scoreFill   = document.getElementById('scoreFill');
        var circumference = 2 * Math.PI * 52;

        function updateScoreText(data) {
            var score = data.score;
            var thr   = data.threshold || APP.threshold;
            var t = translations[currentLang];

            var label;
            if (score >= 0.7) {
                label = t.scoreHuman;
            } else if (score >= 0.4) {
                label = t.scoreUncertain;
            } else {
                label = t.scoreSuspicious;
            }

            var desc;
            if (score >= thr) {
                desc = t.scorePassTpl.replace('{score}', score.toFixed(2)).replace('{thr}', thr);
            } else {
                desc = t.scoreFailTpl.replace('{score}', score.toFixed(2)).replace('{thr}', thr);
            }

            if (scoreTag) scoreTag.textContent = label;
            if (scoreDesc) {
                scoreDesc.textContent = desc;
                scoreDesc.removeAttribute('data-i18n');
            }
        }

        function updateScore(data) {
            lastScoreData = data;
            var score = data.score;

            /* Ring fill */
            if (scoreFill) {
                scoreFill.style.strokeDasharray = (score * circumference) + ' ' + circumference;
            }

            /* Color */
            var color;
            if (score >= 0.7) {
                color = getTheme() === 'dark' ? '#60a5fa' : '#2563eb';
            } else if (score >= 0.4) {
                color = getTheme() === 'dark' ? '#fbbf24' : '#d97706';
            } else {
                color = getTheme() === 'dark' ? '#fb7185' : '#dc2626';
            }

            if (scoreFill) scoreFill.style.stroke = color;
            if (scoreNumber) {
                scoreNumber.textContent = score.toFixed(1);
                scoreNumber.style.color = color;
            }
            if (scoreTag) scoreTag.style.color = color;

            updateScoreText(data);
        }

<?php if ($isRecaptchaConfigured): ?>
        var siteKey = APP.siteKey;

        function fetchScore() {
            grecaptcha.execute(siteKey, { action: 'score_check' }).then(function (token) {
                var body = new FormData();
                body.append('token', token);

                fetch('score.php', { method: 'POST', body: body })
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        if (data.success && typeof data.score === 'number') {
                            updateScore(data);
                        }
                    })
                    .catch(function () { /* silent */ });
            }).catch(function () { /* silent */ });
        }

        /* ── Form submission ── */
        var form = document.getElementById('contactForm');
        var tokenField = document.getElementById('recaptchaToken');

        if (form && tokenField) {
            form.addEventListener('submit', function (event) {
                if (form.dataset.submitting === 'true') return;
                event.preventDefault();

                grecaptcha.ready(function () {
                    grecaptcha.execute(siteKey, { action: 'contact_form' }).then(function (token) {
                        tokenField.value = token;
                        form.dataset.submitting = 'true';
                        form.submit();
                    }).catch(function () {
                        window.location.href = APP.formPage + '?status=security_error#contact';
                    });
                });
            });
        }

        /* ── Live score polling with visibility control ── */
        var scoreInterval = null;

        function startPolling() {
            if (scoreInterval) return;
            fetchScore();
            scoreInterval = setInterval(fetchScore, 25000);
        }

        function stopPolling() {
            if (scoreInterval) {
                clearInterval(scoreInterval);
                scoreInterval = null;
            }
        }

        grecaptcha.ready(function () {
            startPolling();
        });

        document.addEventListener('visibilitychange', function () {
            if (document.hidden) {
                stopPolling();
            } else {
                startPolling();
            }
        });
<?php endif; ?>

        /* ══════════════════════════════════
           Event Listeners
           ══════════════════════════════════ */

        /* Language toggle */
        var langOpts = document.querySelectorAll('.lang-opt');
        for (var i = 0; i < langOpts.length; i++) {
            langOpts[i].addEventListener('click', function () {
                applyLang(this.getAttribute('data-lang-opt'));
            });
        }

        /* Theme toggle */
        var themeBtn = document.querySelector('.theme-toggle');
        if (themeBtn) {
            themeBtn.addEventListener('click', function () {
                setTheme(getTheme() === 'dark' ? 'light' : 'dark');
                /* Re-render score colors for new theme */
                if (lastScoreData) updateScore(lastScoreData);
            });
        }

        /* ══════════════════════════════════
           Reveal Animation (IntersectionObserver)
           ══════════════════════════════════ */
        if ('IntersectionObserver' in window) {
            var observer = new IntersectionObserver(function (entries) {
                for (var e = 0; e < entries.length; e++) {
                    if (entries[e].isIntersecting) {
                        entries[e].target.classList.add('visible');
                        observer.unobserve(entries[e].target);
                    }
                }
            }, { threshold: 0.1 });

            var reveals = document.querySelectorAll('.reveal');
            for (var r = 0; r < reveals.length; r++) {
                observer.observe(reveals[r]);
            }
        } else {
            /* Fallback: show everything immediately */
            var all = document.querySelectorAll('.reveal');
            for (var a = 0; a < all.length; a++) {
                all[a].classList.add('visible');
            }
        }

        /* ══════════════════════════════════
           Init
           ══════════════════════════════════ */
        applyLang(currentLang);
    }());
    </script>
</body>
</html>
