<?php
/**
 * LEAP website — Kontaktformular-Handler.
 * Sendet eine Benachrichtigung an notify_email (config.php) und legt den
 * Absender optional in Brevo an, falls dort ein API-Key hinterlegt ist.
 */

require __DIR__ . '/leap-mail.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    leap_json_response(false, 'Methode nicht erlaubt.', 405);
}

$data = leap_read_json_body();

// Honeypot: real users never fill this hidden field.
if (!empty($data['website'])) {
    leap_json_response(true, 'Danke — wir haben deine Nachricht!');
}

$name = leap_clean((string) ($data['name'] ?? ''), 200);
$company = leap_clean((string) ($data['company'] ?? ''), 200);
$email = leap_clean((string) ($data['email'] ?? ''), 200);
$phone = leap_clean((string) ($data['phone'] ?? ''), 60);
$topic = leap_clean((string) ($data['topic'] ?? ''), 120);
$message = leap_clean((string) ($data['message'] ?? ''), 4000);

if ($name === '' || $email === '' || $message === '') {
    leap_json_response(false, 'Bitte fülle Name, E-Mail und Nachricht aus.', 422);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    leap_json_response(false, 'Bitte gib eine gültige E-Mail-Adresse an.', 422);
}

$config = leap_config();

$rows = [
    'Name' => $name,
    'Unternehmen' => $company !== '' ? $company : '—',
    'E-Mail' => $email,
    'Telefon' => $phone !== '' ? $phone : '—',
    'Thema' => $topic !== '' ? $topic : '—',
];

$html = '<h2>Neue Nachricht über das Kontaktformular</h2><table cellpadding="6" cellspacing="0">';
foreach ($rows as $label => $value) {
    $html .= '<tr><td><strong>' . htmlspecialchars($label, ENT_QUOTES) . '</strong></td><td>' . htmlspecialchars($value, ENT_QUOTES) . '</td></tr>';
}
$html .= '</table><p><strong>Nachricht:</strong><br>' . nl2br(htmlspecialchars($message, ENT_QUOTES)) . '</p>';

$sent = leap_send_notification($config, 'Kontaktformular: ' . $name, $html, $email, $name);

leap_sync_brevo_contact($config, $email);

if (!$sent) {
    leap_json_response(false, 'Nachricht konnte nicht gesendet werden. Bitte versuch es später erneut oder schreib direkt an paul@ich-leaps.at.', 502);
}

leap_json_response(true, 'Danke — wir haben deine Nachricht!');
