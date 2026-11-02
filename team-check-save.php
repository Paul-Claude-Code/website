<?php
/**
 * LEAP — Team-Check: anonyme Antworten speichern.
 * Schreibt pro abgeschlossenem Check eine Zeile in data/team-check.csv.
 * Gespeichert werden NUR die Antworten (1–5), Ergebnistyp, Punkte je Bereich,
 * ggf. die Vergleichsantworten aus dem Link und die UTM-Quelle.
 * Keine IP-Adresse, kein Name, keine E-Mail. data/ ist per .htaccess gesperrt.
 */

header('Content-Type: application/json; charset=utf-8');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false]);
    exit;
}

$raw = file_get_contents('php://input');
$d = json_decode($raw ?: '', true);
if (!is_array($d)) { $d = $_POST; }

$antworten = (string) ($d['antworten'] ?? '');
$vergleich = (string) ($d['vergleich'] ?? '');
$typ = (string) ($d['typ'] ?? '');

// Plausibilitätsprüfung: genau 12 Antworten von 1 bis 5
if (!preg_match('/^[1-5]{12}$/', $antworten) || !in_array($typ, ['A', 'B', 'C', 'D', 'E'], true)) {
    http_response_code(422);
    echo json_encode(['ok' => false]);
    exit;
}
if ($vergleich !== '' && !preg_match('/^[1-5]{12}$/', $vergleich)) {
    $vergleich = '';
}

// Punkte serverseitig neu berechnen (Reihenfolge der Aussagen: A1-3, B4-6, C7-9, D10-12)
$sum = function (int $from) use ($antworten) {
    return (int) $antworten[$from] + (int) $antworten[$from + 1] + (int) $antworten[$from + 2];
};
$clean = function ($v, $len) {
    return mb_substr(preg_replace('/[^a-zA-Z0-9_\-\.]/', '', (string) $v), 0, $len);
};

$row = [
    date('Y-m-d H:i'),
    $antworten,
    $typ,
    $sum(0), $sum(3), $sum(6), $sum(9),
    $vergleich,
    $clean($d['quelle'] ?? '', 40),
    $clean($d['kampagne'] ?? '', 60),
];

$dir = __DIR__ . '/data';
$file = $dir . '/team-check.csv';
if (!is_dir($dir)) { @mkdir($dir, 0755, true); }
$isNew = !file_exists($file);

$fh = @fopen($file, 'a');
if (!$fh) {
    http_response_code(500);
    echo json_encode(['ok' => false]);
    exit;
}
flock($fh, LOCK_EX);
if ($isNew) {
    fputcsv($fh, ['zeit', 'antworten', 'typ', 'rollen', 'feedback', 'vertrauen', 'kommunikation', 'vergleich_antworten', 'quelle', 'kampagne'], ';');
}
fputcsv($fh, $row, ';');
flock($fh, LOCK_UN);
fclose($fh);

echo json_encode(['ok' => true]);
