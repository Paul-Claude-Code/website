<?php
/**
 * LEAP website — "Früher Zugang"-Warteliste-Handler (Workshop-Kit-Seiten).
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
    leap_json_response(true, 'Du bist dabei!');
}

$name = leap_clean((string) ($data['name'] ?? ''), 200);
$email = leap_clean((string) ($data['email'] ?? ''), 200);
$kit = leap_clean((string) ($data['kit'] ?? ''), 60);

$kitLabels = [
    'vertrauen' => 'Vertrauen aufbauen (€390)',
    'rollen' => 'Rollen & Verantwortung (€290)',
    'feedback' => 'Feedback-Kultur aufbauen (€290)',
];
$kitLabel = $kitLabels[$kit] ?? ($kit !== '' ? $kit : 'Unbekanntes Kit');

if ($name === '' || $email === '') {
    leap_json_response(false, 'Bitte Name und E-Mail eintragen.', 422);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    leap_json_response(false, 'Bitte gib eine gültige E-Mail-Adresse an.', 422);
}

$config = leap_config();

$html = '<h2>Neue Warteliste-Anmeldung (Früher Zugang)</h2>'
    . '<table cellpadding="6" cellspacing="0">'
    . '<tr><td><strong>Kit</strong></td><td>' . htmlspecialchars($kitLabel, ENT_QUOTES) . '</td></tr>'
    . '<tr><td><strong>Name</strong></td><td>' . htmlspecialchars($name, ENT_QUOTES) . '</td></tr>'
    . '<tr><td><strong>E-Mail</strong></td><td>' . htmlspecialchars($email, ENT_QUOTES) . '</td></tr>'
    . '</table>';

$sent = leap_send_notification($config, 'Warteliste: ' . $kitLabel . ' — ' . $name, $html, $email, $name);

$kitListKeys = [
    'vertrauen' => 'kit_vertrauen',
    'rollen' => 'kit_rollen',
    'feedback' => 'kit_feedback',
];
$listKeys = ['kit_alle'];
if (isset($kitListKeys[$kit])) {
    $listKeys[] = $kitListKeys[$kit];
}
leap_sync_brevo_contact($config, $email, $listKeys);

if (!$sent) {
    leap_json_response(false, 'Anmeldung konnte nicht gesendet werden. Bitte versuch es später erneut.', 502);
}

leap_json_response(true, 'Du bist dabei!');
