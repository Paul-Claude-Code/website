<?php
/**
 * LEAP website — Leadership-Navigator "Fehlt was?"-Handler.
 * Leichtgewichtiges Gegenstück zu send-contact.php: kein Pflicht-Name,
 * keine Pflicht-E-Mail — nur ein kurzer Vorschlag, der als Mail an
 * notify_email geht (Brevo-API, falls konfiguriert, sonst PHP mail()).
 */

require __DIR__ . '/leap-mail.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    leap_json_response(false, 'Methode nicht erlaubt.', 405);
}

$data = leap_read_json_body();

// Honeypot-kompatibel, falls später ein verstecktes Feld ergänzt wird.
if (!empty($data['website'])) {
    leap_json_response(true, 'Danke!');
}

$step = leap_clean((string) ($data['step'] ?? ''), 40);
$suggestion = leap_clean((string) ($data['suggestion'] ?? ''), 2000);
$context = leap_clean((string) ($data['context'] ?? ''), 500);
$email = leap_clean((string) ($data['email'] ?? ''), 200);

if ($suggestion === '') {
    leap_json_response(false, 'Schreib uns kurz, was dir fehlt.', 422);
}
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    leap_json_response(false, 'Das sieht nicht nach einer gültigen E-Mail aus.', 422);
}

$config = leap_config();

$stepLabels = [
    'situation' => 'Situation fehlt (Schritt 1)',
    'herausforderung' => 'Herausforderung fehlt (Schritt 2)',
    'massnahme' => 'Maßnahme/Lösung fehlt (Schritt 3)',
];
$stepLabel = $stepLabels[$step] ?? 'Leadership-Navigator';

$html = '<div style="font-family:Arial,sans-serif;font-size:14px;color:#222;line-height:1.6;">';
$html .= '<h2 style="margin:0 0 16px;">Neuer Vorschlag im Leadership-Navigator</h2>';
$html .= '<p><strong>Wo:</strong> ' . htmlspecialchars($stepLabel, ENT_QUOTES) . '</p>';
if ($context !== '') {
    $html .= '<p><strong>Kontext zu dem Zeitpunkt:</strong><br>' . nl2br(htmlspecialchars($context, ENT_QUOTES)) . '</p>';
}
$html .= '<p><strong>Vorschlag:</strong><br>' . nl2br(htmlspecialchars($suggestion, ENT_QUOTES)) . '</p>';
$html .= $email !== ''
    ? '<p><strong>Antwort gewünscht an:</strong> ' . htmlspecialchars($email, ENT_QUOTES) . '</p>'
    : '<p style="color:#888;">Keine E-Mail hinterlassen — keine Antwort erwartet.</p>';
$html .= '</div>';

$sent = leap_send_notification($config, 'Navigator-Vorschlag: ' . $stepLabel, $html, $email, '');

if (!$sent) {
    leap_json_response(false, 'Senden hat gerade nicht geklappt. Alternativ: paul@ich-leaps.at', 502);
}

leap_json_response(true, 'Danke — wir schauen uns das an!');
