<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/recaptcha.php';

define('DEFAULT_FORM_PAGE', 'web.php');

function resolve_form_page()
{
    $formPage = isset($_POST['form_page']) ? basename((string) $_POST['form_page']) : DEFAULT_FORM_PAGE;

    if (!preg_match('/^[A-Za-z0-9._-]+\.php$/', $formPage)) {
        return DEFAULT_FORM_PAGE;
    }

    return $formPage;
}

define('FORM_PAGE', resolve_form_page());

function redirect_with_status($status)
{
    header('Location: ' . FORM_PAGE . '?status=' . urlencode($status) . '#contact');
    exit;
}

function post_value($key, $maxLength = 1000)
{
    $value = isset($_POST[$key]) ? trim((string) $_POST[$key]) : '';
    if ($maxLength > 0 && mb_strlen($value, 'UTF-8') > $maxLength) {
        $value = mb_substr($value, 0, $maxLength, 'UTF-8');
    }
    return $value;
}

function has_required_configuration()
{
    $requiredValues = array(
        RECAPTCHA_SITE_KEY,
        RECAPTCHA_SECRET_KEY,
        CONTACT_EMAIL,
        SMTP_HOST,
        SMTP_USERNAME,
        SMTP_PASSWORD,
    );

    foreach ($requiredValues as $value) {
        if ($value === '' || $value === null) {
            return false;
        }
    }

    return true;
}

function resolve_smtp_encryption()
{
    if (SMTP_ENCRYPTION === 'ssl') {
        return \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
    }

    if (SMTP_ENCRYPTION === 'tls') {
        return \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
    }

    return '';
}

if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_with_status('invalid_request');
}

/* ── CSRF validation ── */
session_start();

$csrfToken = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
$sessionToken = isset($_SESSION['csrf_token']) ? $_SESSION['csrf_token'] : '';

if ($sessionToken === '' || !hash_equals($sessionToken, $csrfToken)) {
    redirect_with_status('security_error');
}

unset($_SESSION['csrf_token']);

/* ── Rate limiting (1 submission per 30s per session) ── */
$now = time();
$lastSubmit = isset($_SESSION['last_submit']) ? (int) $_SESSION['last_submit'] : 0;

if ($now - $lastSubmit < 30) {
    redirect_with_status('security_error');
}

$_SESSION['last_submit'] = $now;

if (!has_required_configuration()) {
    redirect_with_status('config_error');
}

$autoloadPath = __DIR__ . '/vendor/autoload.php';

if (!is_file($autoloadPath)) {
    redirect_with_status('dependencies_error');
}

require_once $autoloadPath;

$recaptchaToken = post_value('recaptcha_token', 4096);

if ($recaptchaToken === '') {
    redirect_with_status('security_error');
}

$remoteIp = isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : '';
$result = verify_recaptcha_token($recaptchaToken, $remoteIp);

if ($result === null || empty($result['success'])) {
    redirect_with_status('security_error');
}

$score = isset($result['score']) ? (float) $result['score'] : 0.0;
$action = isset($result['action']) ? (string) $result['action'] : '';

if ($score < RECAPTCHA_SCORE_THRESHOLD || $action !== 'contact_form') {
    redirect_with_status('security_error');
}

$visitorName = str_replace(array("\r", "\n", "%0a", "%0d"), '', post_value('visitor_name', 100));
$visitorEmail = str_replace(array("\r", "\n", "%0a", "%0d"), '', post_value('visitor_email', 254));
$emailTitle = post_value('email_title', 200);
$visitorMessage = post_value('visitor_message', 5000);

if ($visitorName === '' || $visitorMessage === '') {
    redirect_with_status('validation_error');
}

$validatedEmail = filter_var($visitorEmail, FILTER_VALIDATE_EMAIL);

if ($validatedEmail === false) {
    redirect_with_status('validation_error');
}

$safeName = htmlspecialchars($visitorName, ENT_QUOTES, 'UTF-8');
$safeEmail = htmlspecialchars($validatedEmail, ENT_QUOTES, 'UTF-8');
$safeMessage = nl2br(htmlspecialchars($visitorMessage, ENT_QUOTES, 'UTF-8'));

$subjectText = $emailTitle !== '' ? $emailTitle : 'Consulta desde el sitio';

$mail = new \PHPMailer\PHPMailer\PHPMailer(true);

try {
    $mail->CharSet = 'UTF-8';
    $mail->isSMTP();
    $mail->Host = SMTP_HOST;
    $mail->SMTPAuth = true;
    $mail->Username = SMTP_USERNAME;
    $mail->Password = SMTP_PASSWORD;
    $mail->Port = SMTP_PORT;

    $smtpEncryption = resolve_smtp_encryption();
    if ($smtpEncryption !== '') {
        $mail->SMTPSecure = $smtpEncryption;
    }

    $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
    $mail->addAddress(CONTACT_EMAIL);
    $mail->addReplyTo($validatedEmail, $visitorName);
    $mail->isHTML(true);
    $mail->Subject = MAIL_SUBJECT_PREFIX . ' ' . $subjectText;
    $mail->Body = '
        <h2>Nuevo mensaje desde el formulario</h2>
        <p><strong>Nombre:</strong> ' . $safeName . '</p>
        <p><strong>Email:</strong> ' . $safeEmail . '</p>
        <p><strong>Asunto:</strong> ' . htmlspecialchars($subjectText, ENT_QUOTES, 'UTF-8') . '</p>
        <p><strong>Mensaje:</strong></p>
        <p>' . $safeMessage . '</p>
        <hr>
        <small>Enviado el ' . date('d/m/Y H:i:s') . '</small>
    ';
    $mail->AltBody =
        "Nuevo mensaje desde el formulario\n" .
        "Nombre: " . $visitorName . "\n" .
        "Email: " . $validatedEmail . "\n" .
        "Asunto: " . $subjectText . "\n\n" .
        $visitorMessage;

    $mail->send();
    redirect_with_status('success');
} catch (\PHPMailer\PHPMailer\Exception $exception) {
    error_log('[contact.php] PHPMailer error: ' . $exception->getMessage());
    redirect_with_status('mail_error');
}
