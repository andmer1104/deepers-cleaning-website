<?php
/**
 * Contact Form Submission Handler
 * 
 * Processes contact form submissions, validates input, saves to database,
 * and sends email notifications.
 */

// ===== SECURITY CHECK =====
// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.html');
    exit;
}

// ===== INCLUDE REQUIRED FILES =====
require_once __DIR__ . '/database/config.php';
require_once __DIR__ . '/classes/Database.php';

// ===== CAPTCHA VERIFICATION =====
$turnstile_token = isset($_POST['cf-turnstile-response']) ? trim($_POST['cf-turnstile-response']) : '';

// Check if running on localhost (skip captcha for local development)
$host = $_SERVER['HTTP_HOST'] ?? '';
$serverName = $_SERVER['SERVER_NAME'] ?? '';

$is_local =
    strpos($host, 'localhost') !== false ||
    strpos($host, '127.0.0.1') !== false ||
    strpos($serverName, 'localhost') !== false ||
    strpos($serverName, '127.0.0.1') !== false;

// Verify captcha only on production (not localhost)
if (!$is_local && (empty($turnstile_token) || !verifyTurnstile($turnstile_token))) {
    header('Location: index.html?error=captcha#contact');
    exit;
}

// ===== BOT PROTECTION =====
// Check honeypot field (hidden field that bots might fill)
if (!empty($_POST['bot-field'])) {
    header('Location: index.html');
    exit;
}

// ===== GET AND SANITIZE FORM DATA =====
$full_name = isset($_POST['name']) ? trim($_POST['name']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$service_type = isset($_POST['subject']) ? trim($_POST['subject']) : '';
$message = isset($_POST['message']) ? trim($_POST['message']) : '';

// ===== INPUT VALIDATION =====
// Check required fields
if (empty($full_name) || empty($email) || empty($service_type) || empty($message)) {
    header('Location: index.html?error=validation#contact');
    exit;
}

// Check field length limits
if (
    strlen($full_name) > 100 ||
    strlen($email) > 254 ||
    strlen($service_type) > 150 ||
    strlen($message) > 5000
) {
    header('Location: index.html?error=validation#contact');
    exit;
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: index.html?error=email#contact');
    exit;
}

// ===== SECURITY: PREVENT HEADER INJECTION =====
// Remove line breaks from email-related fields
$email = str_replace(["\r", "\n"], '', $email);
$full_name = str_replace(["\r", "\n"], '', $full_name);
$service_type = str_replace(["\r", "\n"], '', $service_type);

// ===== DATABASE SAVE =====
$db_saved = false;
$email_sent = false;

$database = new Database(DB_HOST, DB_NAME, DB_USER, DB_PASS, DB_CHARSET);

if ($database->Connect()) {
    $sql = "INSERT INTO contact_requests (full_name, email, service_type, message)
            VALUES (:full_name, :email, :service_type, :message)";

    $params = [
        ':full_name' => $full_name,
        ':email' => $email,
        ':service_type' => $service_type,
        ':message' => $message
    ];

    $stmt = $database->Execute($sql, $params);

    if ($stmt !== false) {
        $db_saved = true;
        $insert_id = $database->GetLastInsertId();

        if ($insert_id) {
            error_log("Contact form: Successfully saved submission from {$email} (ID: {$insert_id})");
        } else {
            error_log("Contact form: Successfully saved submission from {$email} (ID: unknown)");
        }
    } else {
        error_log("Contact form: Database save failed for submission from {$email}");
    }

    $database->Disconnect();
} else {
    error_log("Contact form: Database connection failed for submission from {$email}");
}

// ===== EMAIL NOTIFICATION =====
// Send email regardless of database result
$email_sent = sendEmailNotification($full_name, $email, $service_type, $message);

if (!$email_sent) {
    error_log("Contact form: Email notification failed for submission from {$email}");
}

// ===== RESPONSE HANDLING =====
// Show success page if at least one delivery method worked
if ($db_saved || $email_sent) {
    header('Location: thanks.html');
    exit;
}

// Show error if everything failed
header('Location: index.html?error=server#contact');
exit;

// ===== HELPER FUNCTIONS =====

/**
 * Verifies Cloudflare Turnstile captcha token
 */
function verifyTurnstile($token)
{
    if (
        !defined('TURNSTILE_SECRET_KEY') ||
        TURNSTILE_SECRET_KEY === 'YOUR_TURNSTILE_SECRET_KEY_HERE' ||
        empty(TURNSTILE_SECRET_KEY)
    ) {
        error_log("Contact form: Turnstile secret key not configured. Turnstile verification failed.");
        return false;
    }

    $data = [
        'secret' => TURNSTILE_SECRET_KEY,
        'response' => $token,
        'remoteip' => $_SERVER['REMOTE_ADDR'] ?? ''
    ];

    $options = [
        'http' => [
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data),
            'timeout' => 10
        ]
    ];

    $context = stream_context_create($options);
    $result = @file_get_contents('https://challenges.cloudflare.com/turnstile/v0/siteverify', false, $context);

    if ($result === false) {
        error_log("Contact form: Turnstile API request failed");
        return false;
    }

    $response = json_decode($result, true);

    if (!isset($response['success']) || $response['success'] !== true) {
        $error_codes = isset($response['error-codes']) ? implode(', ', $response['error-codes']) : 'unknown';
        error_log("Contact form: Turnstile verification failed. Error codes: {$error_codes}");
        return false;
    }

    return true;
}

/**
 * Sends email notification to the business owner
 * Returns true on success, false on failure
 */
function sendEmailNotification($full_name, $email, $service_type, $message)
{
    $to_email = defined('NOTIFICATION_EMAIL') ? NOTIFICATION_EMAIL : 'deeperscleaning@gmail.com';
    $from_email = defined('EMAIL_FROM') ? EMAIL_FROM : 'noreply@deeperscleaning.com';
    $subject = 'New Contact Form Submission: ' . ($service_type ?: 'General Inquiry');

    // Protect against header injection
    $to_email = str_replace(["\r", "\n"], '', $to_email);
    $from_email = str_replace(["\r", "\n"], '', $from_email);
    $email = str_replace(["\r", "\n"], '', $email);
    $subject = str_replace(["\r", "\n"], '', $subject);

    // Escape HTML for email body
    $safeName = htmlspecialchars($full_name, ENT_QUOTES, 'UTF-8');
    $safeEmail = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
    $safeServiceType = htmlspecialchars($service_type, ENT_QUOTES, 'UTF-8');
    $safeMessage = nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));

    $html_body = '
    <div style="font-family: Arial, sans-serif; max-width: 680px; margin: 0 auto; padding: 24px; border: 1px solid #eee; border-radius: 12px;">
        <div style="text-align:center; margin-bottom: 16px;">
            <h2 style="margin: 0; color:#54d7dd;">New Contact Form Submission</h2>
            <p style="margin: 6px 0 0; color:#666;">Deepers Cleaning Website</p>
        </div>

        <div style="background:#fafafa; border-radius: 10px; padding: 16px; border: 1px solid #eee;">
            <p style="margin: 0 0 10px; color:#333;"><strong>Name:</strong> ' . $safeName . '</p>
            <p style="margin: 0 0 10px; color:#333;"><strong>Email:</strong> <a href="mailto:' . $safeEmail . '" style="color:#e71c6f; text-decoration:none;">' . $safeEmail . '</a></p>
            <p style="margin: 0 0 10px; color:#333;"><strong>Service Type:</strong> ' . $safeServiceType . '</p>
            <p style="margin: 0; color:#333;"><strong>Message:</strong><br>' . $safeMessage . '</p>
        </div>

        <div style="margin-top: 18px; color:#999; font-size: 12px; text-align:center;">
            Submitted from DeepersCleaning contact form
        </div>
    </div>
    ';

    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: Deepers Cleaning Website <" . $from_email . ">\r\n";
    $headers .= "Reply-To: " . $email . "\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();

    return @mail($to_email, $subject, $html_body, $headers);
}