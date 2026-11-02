<?php
/**
 * LEAP — Team-Check: Ergebnis per Mail (über Brevo, mit Double-Opt-in).
 *
 * POST {"vorname","email","einwilligung":true,"antworten":"…12 Ziffern…","website":""}
 *
 * Legt den Kontakt über die Brevo-API mit Double-Opt-in an. Nach dem Klick auf
 * den Bestätigungslink landet die Person auf team-check-danke.html und kommt in
 * die Team-Check-Liste. Die Ergebnis-Felder (TC_…) werden dabei am Kontakt
 * gespeichert, die Brevo-Automation verschickt daraus Mail 1 und Mail 2.
 *
 * Alle Texte werden hier auf dem Server aus den Antworten berechnet, damit
 * niemand über das Formular beliebige Inhalte in Mails schleusen kann.
 */

require __DIR__ . '/leap-mail.php';

// Brevo-Liste „Team-Check“
const TC_LIST_ID = 9;
// ID der Double-Opt-in-Vorlage in Brevo (Kampagnen → Vorlagen, die Vorlage mit dem Bestätigungslink).
// Kann auch in config.php als 'teamcheck_doi_template_id' gesetzt werden.
const TC_DOI_TEMPLATE_ID = null;
const TC_REDIRECT = 'https://www.ich-leaps.at/team-check-danke.html';
const TC_RESULT_URL = 'https://www.ich-leaps.at/team-check?ergebnis=';

const TC_TYPES = [
    'A' => ['satz' => 'Bei uns sind alle ein bisschen zuständig.', 'uebung_titel' => 'Die Frage nach der Aufgabe ohne Namen', 'uebung' => 'Frag im nächsten Teammeeting: „Welche Aufgabe hat bei uns gerade niemanden, der sich dafür zuständig fühlt?“ Schreib die Antworten mit und vergib für jede einen Namen, bevor ihr auseinandergeht.'],
    'B' => ['satz' => 'Wir sind ein sehr höfliches Team.', 'uebung_titel' => 'Selbst nach Feedback fragen', 'uebung' => 'Bitte diese Woche eine Person ausdrücklich um Feedback zu etwas, das du gemacht hast. Dann hörst du zu, bedankst dich und erklärst nichts. Das zeigt deinem Team mehr als jede Feedback-Regel.'],
    'C' => ['satz' => 'Bei uns überlegt man zweimal, bevor man was sagt.', 'uebung_titel' => 'Mit dem eigenen Fehler anfangen', 'uebung' => 'Erzähl im nächsten Meeting von einem Fehler, der dir in letzter Zeit passiert ist, und was du daraus mitgenommen hast. Solange die Führungskraft nicht damit anfängt, macht es meistens auch sonst niemand.'],
    'D' => ['satz' => 'Bei uns wird nach dem Meeting geredet.', 'uebung_titel' => 'Zwei Minuten am Ende', 'uebung' => 'Nimm dir am Ende des nächsten Meetings zwei Minuten und lass jemand anderen zusammenfassen, was entschieden wurde und wer was bis wann macht. Du wirst überrascht sein, wie oft die Zusammenfassung von deiner abweicht.'],
    'E' => ['satz' => 'Wir sind ein eingespieltes Team.', 'uebung_titel' => 'Den Check ans Team weitergeben', 'uebung' => 'Schick deinem Team den Vergleichslink weiter unten. Wenn das Bild deines Teams genauso gut ausfällt wie deins, umso besser. Wenn nicht, weißt du jetzt, wo du hinschauen solltest.'],
];
const TC_AREAS = ['A' => 'Rollen & Verantwortung', 'B' => 'Feedback', 'C' => 'Vertrauen & psychologische Sicherheit', 'D' => 'Kommunikation & Klarheit'];

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    leap_json_response(false, 'Methode nicht erlaubt.', 405);
}

$d = leap_read_json_body();

// Honeypot
if (!empty($d['website'])) {
    leap_json_response(true, 'ok');
}

$vorname = leap_clean((string) ($d['vorname'] ?? ''), 80);
$email = leap_clean((string) ($d['email'] ?? ''), 200);
$antworten = (string) ($d['antworten'] ?? '');

if ($vorname === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    leap_json_response(false, 'Bitte gib deinen Vornamen und eine gültige E-Mail-Adresse an.', 422);
}
if (empty($d['einwilligung'])) {
    leap_json_response(false, 'Bitte bestätige noch das Häkchen zur Einwilligung.', 422);
}
if (!preg_match('/^[1-5]{12}$/', $antworten)) {
    leap_json_response(false, 'Das Ergebnis ist unvollständig. Bitte mach den Check noch einmal.', 422);
}

// Punkte je Bereich (Aussagen 1–3 = A, 4–6 = B, 7–9 = C, 10–12 = D)
$s = [];
foreach (['A' => 0, 'B' => 3, 'C' => 6, 'D' => 9] as $k => $i) {
    $s[$k] = (int) $antworten[$i] + (int) $antworten[$i + 1] + (int) $antworten[$i + 2];
}
// Ergebnistyp wie auf der Seite: alle ≥ 13 → E, sonst niedrigster Bereich (bei Gleichstand C, A, B, D)
if (min($s) >= 13) {
    $typ = 'E';
} else {
    $min = min($s);
    $typ = 'A';
    foreach (['C', 'A', 'B', 'D'] as $k) {
        if ($s[$k] === $min) { $typ = $k; break; }
    }
}
$bereich = $typ === 'E' ? 'allen vier Bereichen' : TC_AREAS[$typ];

$config = leap_config();
$template = $config['teamcheck_doi_template_id'] ?? TC_DOI_TEMPLATE_ID;
if (empty($config['brevo_api_key']) || empty($template)) {
    leap_json_response(false, 'Der Mailversand ist gerade nicht eingerichtet. Bitte speichere dein Ergebnis über den PDF-Button.', 503);
}

$payload = [
    'email' => $email,
    'includeListIds' => [TC_LIST_ID],
    'templateId' => (int) $template,
    'redirectionUrl' => TC_REDIRECT,
    'attributes' => [
        'FIRSTNAME' => $vorname,
        'TC_TYP' => $typ,
        'TC_SATZ' => TC_TYPES[$typ]['satz'],
        'TC_BEREICH' => $bereich,
        'TC_UEBUNG_TITEL' => TC_TYPES[$typ]['uebung_titel'],
        'TC_UEBUNG' => TC_TYPES[$typ]['uebung'],
        'TC_ROLLEN' => $s['A'],
        'TC_FEEDBACK' => $s['B'],
        'TC_VERTRAUEN' => $s['C'],
        'TC_KOMMUNIKATION' => $s['D'],
        'TC_LINK' => TC_RESULT_URL . $antworten,
        'TC_DATUM' => date('Y-m-d'),
    ],
];

$ch = curl_init('https://api.brevo.com/v3/contacts/doubleOptinConfirmation');
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 15,
    CURLOPT_HTTPHEADER => [
        'accept: application/json',
        'content-type: application/json',
        'api-key: ' . $config['brevo_api_key'],
    ],
    CURLOPT_POSTFIELDS => json_encode($payload),
]);
$response = curl_exec($ch);
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($status >= 200 && $status < 300) {
    leap_json_response(true, 'ok');
}

// Fehler für Paul nachvollziehbar mitloggen (ohne Mailadresse)
@file_put_contents(__DIR__ . '/data/team-check-mail-fehler.log', date('Y-m-d H:i') . ' ' . $status . ' ' . substr((string) $response, 0, 500) . "\n", FILE_APPEND | LOCK_EX);
leap_json_response(false, 'Das hat gerade nicht geklappt. Bitte versuch es gleich noch einmal oder speichere dein Ergebnis über den PDF-Button.', 502);
