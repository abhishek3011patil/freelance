<?php
/**
 * Shree Caterers & Decorators — Quote Request Mailer
 * File: send_quote.php
 * Place this file in the same folder as index.html on your server.
 *
 * Requirements: PHP 7.4+ with mail() enabled (works on most shared hosts).
 * For better deliverability, swap mail() for PHPMailer + SMTP (see bottom comment).
 */

// ─── CONFIGURATION ──────────────────────────────────────────────────────────
define('TO_EMAIL',    'shreecatring60@gmail.com');   // Where enquiries go
define('TO_NAME',     'Shree Caterers & Decorators');
define('FROM_EMAIL',  'noreply@shreecaterers.in');   // Must match your domain
define('FROM_NAME',   'Shree Caterers Website');
define('SITE_NAME',   'Shree Caterers & Decorators');
define('WHATSAPP_NO', '919967683584');
// ────────────────────────────────────────────────────────────────────────────

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['success' => false, 'message' => 'Method not allowed.']));
}

// ─── HELPERS ────────────────────────────────────────────────────────────────
function clean(string $value): string {
    return htmlspecialchars(strip_tags(trim($value)), ENT_QUOTES, 'UTF-8');
}

function required(string $key): string {
    if (empty($_POST[$key])) {
        respond(false, "Field '{$key}' is required.");
    }
    return clean($_POST[$key]);
}

function respond(bool $success, string $message, array $extra = []): void {
    header('Content-Type: application/json');
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $extra));
    exit;
}

// ─── COLLECT & VALIDATE FIELDS ──────────────────────────────────────────────
$name       = required('name');
$phone      = required('phone');
$event_type = required('event_type');
$event_date = required('event_date');
$guests     = required('guests');
$budget     = required('budget');

// Optional email
$email = !empty($_POST['email']) ? filter_var(clean($_POST['email']), FILTER_VALIDATE_EMAIL) : '';

// Basic phone validation (allow +91, spaces, dashes, 10-digit)
if (!preg_match('/^[\+\d\s\-]{7,15}$/', $phone)) {
    respond(false, 'Please enter a valid phone number.');
}

// Date must be today or future
$eventDate = DateTime::createFromFormat('Y-m-d', $event_date);
$today     = new DateTime('today');
if (!$eventDate || $eventDate < $today) {
    respond(false, 'Please enter a valid future event date.');
}

// Allowed values (whitelist)
$allowedEventTypes = ['wedding','reception','engagement','birthday','corporate','other'];
$allowedGuests     = ['100-200','200-500','500-1000','1000+'];
$allowedBudgets    = ['under-1L','1L-2L','2L-5L','5L-10L','above-10L'];

if (!in_array($event_type, $allowedEventTypes, true)) respond(false, 'Invalid event type.');
if (!in_array($guests,     $allowedGuests,     true)) respond(false, 'Invalid guest count.');
if (!in_array($budget,     $allowedBudgets,    true)) respond(false, 'Invalid budget range.');

// ─── LABEL MAPS ─────────────────────────────────────────────────────────────
$eventLabels  = [
    'wedding'    => 'Wedding',
    'reception'  => 'Reception',
    'engagement' => 'Engagement',
    'birthday'   => 'Birthday Party',
    'corporate'  => 'Corporate Event',
    'other'      => 'Other',
];
$guestLabels  = [
    '100-200'   => '100 – 200 Guests',
    '200-500'   => '200 – 500 Guests',
    '500-1000'  => '500 – 1000 Guests',
    '1000+'     => '1000+ Guests',
];
$budgetLabels = [
    'under-1L'   => 'Under ₹1 Lakh',
    '1L-2L'      => '₹1 – 2 Lakh',
    '2L-5L'      => '₹2 – 5 Lakh',
    '5L-10L'     => '₹5 – 10 Lakh',
    'above-10L'  => 'Above ₹10 Lakh',
];

$eventLabel  = $eventLabels[$event_type]  ?? $event_type;
$guestLabel  = $guestLabels[$guests]      ?? $guests;
$budgetLabel = $budgetLabels[$budget]     ?? $budget;
$dateFormatted = $eventDate->format('d F Y');
$submittedAt = (new DateTime())->format('d M Y, h:i A');

// ─── BUILD HTML EMAIL (TO BUSINESS) ─────────────────────────────────────────
$waText  = urlencode("Hello Shree Caterers! I'd like a quote.\nName: $name\nPhone: $phone\nEvent: $eventLabel\nDate: $dateFormatted\nGuests: $guestLabel\nBudget: $budgetLabel");
$waLink  = "https://wa.me/" . WHATSAPP_NO . "?text=$waText";

$emailRow = $email
    ? "<tr><td class='label'>Email</td><td class='value'><a href='mailto:{$email}'>{$email}</a></td></tr>"
    : '';

$htmlBody = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>New Quote Request — Shree Caterers</title>
<style>
  body{margin:0;padding:0;background:#f5edd8;font-family:'Segoe UI',Arial,sans-serif}
  .wrap{max-width:620px;margin:30px auto;background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.10)}
  .header{background:linear-gradient(135deg,#4A0E0E,#6B1A1A);padding:36px 40px;text-align:center}
  .header img{height:70px;margin:0 auto 16px;display:block}
  .header h1{margin:0;color:#E8C97A;font-size:22px;font-weight:700;letter-spacing:.03em}
  .header p{margin:6px 0 0;color:rgba(255,255,255,.65);font-size:13px}
  .badge{display:inline-block;background:#C9A84C;color:#fff;font-size:11px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;padding:5px 16px;border-radius:20px;margin-top:14px}
  .body{padding:36px 40px}
  .section-title{font-size:11px;font-weight:700;letter-spacing:.2em;text-transform:uppercase;color:#9A7A2A;margin:0 0 16px;border-bottom:2px solid #F5EDD8;padding-bottom:8px}
  table.details{width:100%;border-collapse:collapse;margin-bottom:28px}
  table.details td{padding:10px 12px;font-size:14px;border-bottom:1px solid #F5EDD8}
  table.details td.label{font-weight:700;color:#5A4030;width:38%;background:#FDF8F0;border-radius:4px 0 0 4px}
  table.details td.value{color:#2C1810}
  table.details tr:last-child td{border-bottom:none}
  .highlight{background:linear-gradient(135deg,rgba(201,168,76,.08),rgba(201,168,76,.04));border:1px solid rgba(201,168,76,.25);border-left:4px solid #C9A84C;border-radius:4px;padding:16px 20px;margin-bottom:28px}
  .highlight p{margin:0;font-size:14px;color:#2C1810}
  .highlight strong{color:#4A0E0E}
  .actions{text-align:center;margin-bottom:28px}
  .btn{display:inline-block;padding:14px 28px;border-radius:4px;font-size:13px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;text-decoration:none;margin:6px}
  .btn-wa{background:linear-gradient(135deg,#25D366,#128C7E);color:#fff}
  .btn-call{background:linear-gradient(135deg,#6B1A1A,#4A0E0E);color:#fff}
  .footer-bar{background:#1A1A1A;padding:20px 40px;text-align:center}
  .footer-bar p{margin:0;font-size:11px;color:rgba(255,255,255,.35);letter-spacing:.05em}
  .footer-bar a{color:#E8C97A;text-decoration:none}
  @media(max-width:480px){.body,.header{padding:24px 20px}.footer-bar{padding:16px 20px}}
</style>
</head>
<body>
<div class="wrap">
  <div class="header">
    <h1>🎉 New Quote Request</h1>
    <p>Someone just filled your catering enquiry form</p>
    <span class="badge">Action Required</span>
  </div>
  <div class="body">

    <p class="section-title">Customer Details</p>
    <table class="details">
      <tr><td class="label">Full Name</td><td class="value"><strong>{$name}</strong></td></tr>
      <tr><td class="label">Phone</td><td class="value"><a href="tel:{$phone}" style="color:#C9A84C;font-weight:700">{$phone}</a></td></tr>
      {$emailRow}
    </table>

    <p class="section-title">Event Details</p>
    <table class="details">
      <tr><td class="label">Event Type</td><td class="value">{$eventLabel}</td></tr>
      <tr><td class="label">Event Date</td><td class="value"><strong>{$dateFormatted}</strong></td></tr>
      <tr><td class="label">Guests</td><td class="value">{$guestLabel}</td></tr>
      <tr><td class="label">Budget Range</td><td class="value"><strong>{$budgetLabel}</strong></td></tr>
    </table>

    <div class="highlight">
      <p>⏰ <strong>Respond within 2 hours</strong> — the customer expects a quick reply. Early follow-ups convert significantly better.</p>
    </div>

    <div class="actions">
      <a href="{$waLink}" class="btn btn-wa">💬 Reply on WhatsApp</a>
      <a href="tel:{$phone}" class="btn btn-call">📞 Call Now</a>
    </div>

    <p style="font-size:12px;color:#8A7060;text-align:center;margin:0">Submitted: {$submittedAt} &nbsp;|&nbsp; Source: shreecaterers.in</p>
  </div>
  <div class="footer-bar">
    <p>Shree Caterers &amp; Decorators &nbsp;·&nbsp; <a href="tel:+919967683584">+91 9967683584</a> &nbsp;·&nbsp; Ulwe, Navi Mumbai</p>
  </div>
</div>
</body>
</html>
HTML;

// ─── BUILD PLAIN-TEXT FALLBACK ───────────────────────────────────────────────
$textBody = <<<TEXT
NEW QUOTE REQUEST — SHREE CATERERS & DECORATORS
================================================
Submitted: {$submittedAt}

CUSTOMER
--------
Name   : {$name}
Phone  : {$phone}
Email  : {$email}

EVENT
-----
Type   : {$eventLabel}
Date   : {$dateFormatted}
Guests : {$guestLabel}
Budget : {$budgetLabel}

ACTION: Reply within 2 hours!
WhatsApp: https://wa.me/{$waText}

--
shreecaterers.in
TEXT;

// ─── SEND EMAIL TO BUSINESS ──────────────────────────────────────────────────
$boundary = '----=_Part_' . md5(uniqid('', true));

$headers  = "From: " . FROM_NAME . " <" . FROM_EMAIL . ">\r\n";
$headers .= "Reply-To: {$name} <" . (empty($email) ? FROM_EMAIL : $email) . ">\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n";
$headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
$headers .= "X-Priority: 1 (Highest)\r\n";
$headers .= "Importance: High\r\n";

$subject = "=?UTF-8?B?" . base64_encode("🎉 New Catering Enquiry — {$name} | {$eventLabel} | {$dateFormatted}") . "?=";

$message  = "--{$boundary}\r\n";
$message .= "Content-Type: text/plain; charset=UTF-8\r\n";
$message .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
$message .= $textBody . "\r\n\r\n";
$message .= "--{$boundary}\r\n";
$message .= "Content-Type: text/html; charset=UTF-8\r\n";
$message .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
$message .= $htmlBody . "\r\n\r\n";
$message .= "--{$boundary}--";

$sent = mail(TO_EMAIL, $subject, $message, $headers);

if (!$sent) {
    // Log the failure server-side (never expose details to client)
    error_log('[Shree Caterers] mail() failed for: ' . $name . ' / ' . $phone . ' at ' . $submittedAt);
    respond(false, 'Mail delivery failed. Please call us directly at +91 9967683584.', ['whatsapp' => $waLink]);
}

// ─── SEND CONFIRMATION EMAIL TO CUSTOMER (if email provided) ─────────────────
if (!empty($email)) {
    $confirmHtml = <<<HTML2
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
  body{margin:0;padding:0;background:#f5edd8;font-family:'Segoe UI',Arial,sans-serif}
  .wrap{max-width:560px;margin:30px auto;background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.10)}
  .header{background:linear-gradient(135deg,#4A0E0E,#6B1A1A);padding:36px 40px;text-align:center}
  .header h1{margin:0;color:#E8C97A;font-size:20px;font-weight:700}
  .header p{margin:8px 0 0;color:rgba(255,255,255,.65);font-size:13px}
  .body{padding:32px 40px}
  .body p{font-size:15px;color:#2C1810;line-height:1.7;margin:0 0 16px}
  .detail-box{background:#FDF8F0;border:1px solid rgba(201,168,76,.25);border-left:4px solid #C9A84C;border-radius:4px;padding:16px 20px;margin:20px 0}
  .detail-box p{margin:4px 0;font-size:14px}
  .detail-box strong{color:#4A0E0E}
  .btn-wa{display:block;text-align:center;background:linear-gradient(135deg,#25D366,#128C7E);color:#fff;text-decoration:none;padding:15px 24px;border-radius:4px;font-size:13px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;margin:24px 0}
  .footer-bar{background:#1A1A1A;padding:18px 40px;text-align:center}
  .footer-bar p{margin:0;font-size:11px;color:rgba(255,255,255,.35)}
  .footer-bar a{color:#E8C97A;text-decoration:none}
</style>
</head>
<body>
<div class="wrap">
  <div class="header">
    <h1>Thank You, {$name}! 🎉</h1>
    <p>Your catering enquiry has been received</p>
  </div>
  <div class="body">
    <p>We've received your quote request and our team will get back to you <strong>within 2 hours</strong>.</p>

    <div class="detail-box">
      <p><strong>Event:</strong> {$eventLabel}</p>
      <p><strong>Date:</strong> {$dateFormatted}</p>
      <p><strong>Guests:</strong> {$guestLabel}</p>
      <p><strong>Budget:</strong> {$budgetLabel}</p>
    </div>

    <p>For faster assistance, you can also reach us directly on WhatsApp:</p>
    <a href="https://wa.me/{$waText}" class="btn-wa">💬 Chat on WhatsApp</a>

    <p style="font-size:13px;color:#8A7060">📍 Shop No 2, Tulsa Namdev Residency, Sector 17, Ulwe, Navi Mumbai 410206<br>
    📞 <a href="tel:+919967683584" style="color:#C9A84C">+91 9967683584</a> &nbsp;|&nbsp; 🕐 10 AM – 10 PM, All Days</p>
  </div>
  <div class="footer-bar">
    <p>Shree Caterers &amp; Decorators &nbsp;·&nbsp; <a href="https://shreecaterers.in">shreecaterers.in</a></p>
  </div>
</div>
</body>
</html>
HTML2;

    $confirmHeaders  = "From: " . TO_NAME . " <" . FROM_EMAIL . ">\r\n";
    $confirmHeaders .= "MIME-Version: 1.0\r\n";
    $confirmHeaders .= "Content-Type: text/html; charset=UTF-8\r\n";

    $confirmSubject = "=?UTF-8?B?" . base64_encode("We received your catering enquiry — Shree Caterers & Decorators") . "?=";

    @mail($email, $confirmSubject, $confirmHtml, $confirmHeaders);
    // Failure of confirmation mail is non-critical — don't block success response
}

// ─── SUCCESS ─────────────────────────────────────────────────────────────────
respond(true, 'Your enquiry has been sent! We will contact you within 2 hours.', [
    'whatsapp' => $waLink,
]);

/*
 * ─── UPGRADE TO SMTP WITH PHPMAILER (recommended for Gmail / high deliverability) ─
 *
 * 1. In your project root: composer require phpmailer/phpmailer
 * 2. Replace the mail() block above with:
 *
 *    use PHPMailer\PHPMailer\PHPMailer;
 *    require 'vendor/autoload.php';
 *
 *    $mail = new PHPMailer(true);
 *    $mail->isSMTP();
 *    $mail->Host       = 'smtp.gmail.com';      // or smtp.hostinger.com etc.
 *    $mail->SMTPAuth   = true;
 *    $mail->Username   = 'your@gmail.com';
 *    $mail->Password   = 'your_app_password';   // Gmail App Password
 *    $mail->SMTPSecure = 'tls';
 *    $mail->Port       = 587;
 *    $mail->setFrom(FROM_EMAIL, FROM_NAME);
 *    $mail->addAddress(TO_EMAIL, TO_NAME);
 *    $mail->isHTML(true);
 *    $mail->Subject = "New Enquiry — $name";
 *    $mail->Body    = $htmlBody;
 *    $mail->AltBody = $textBody;
 *    $mail->send();
 */
