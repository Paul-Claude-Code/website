<?php
/**
 * LEAP — Team-Check im Team: gemeinsames, anonymes Teamergebnis.
 *
 * POST {"action":"create","antworten":"…12 Ziffern…"}          → neues Team, erste Antwort
 * POST {"action":"add","team":"ID","antworten":"…"}             → Antwort eines Teammitglieds
 * GET  ?team=ID                                                 → Teamergebnis
 *
 * Gespeichert in data/teams/<ID>.json (data/ per .htaccess gesperrt).
 * Keine Namen, keine Mailadressen, keine IP-Adressen.
 * Das Teamergebnis (Mittelwert je Bereich) wird erst ab MIN_TEAM Antworten
 * herausgegeben, Einzelantworten nie. Auch die Antworten der Person, die den
 * Link erstellt hat (meist die Führungskraft), zählen ganz normal und anonym mit.
 */

const MIN_TEAM = 3;
const MAX_TEAM = 60;

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function tc_out(array $data, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($data);
    exit;
}

function tc_valid_answers($a): bool
{
    return is_string($a) && preg_match('/^[1-5]{12}$/', $a) === 1;
}

function tc_valid_id($id): bool
{
    return is_string($id) && preg_match('/^[A-Za-z0-9]{12}$/', $id) === 1;
}

function tc_sums(string $a): array
{
    $s = [];
    foreach (['A' => 0, 'B' => 3, 'C' => 6, 'D' => 9] as $k => $i) {
        $s[$k] = (int) $a[$i] + (int) $a[$i + 1] + (int) $a[$i + 2];
    }
    return $s;
}

function tc_file(string $id): string
{
    return __DIR__ . '/data/teams/' . $id . '.json';
}

function tc_summary(array $team): array
{
    $members = $team['members'] ?? [];
    $n = count($members);
    $out = [
        'ok' => true,
        'n' => $n,
        'min' => MIN_TEAM,
        'ready' => $n >= MIN_TEAM,
    ];
    if ($n >= MIN_TEAM) {
        $mean = [];
        $agree = [];
        foreach (['A', 'B', 'C', 'D'] as $k) {
            $vals = array_map(function ($a) use ($k) { return tc_sums($a)[$k]; }, $members);
            $m = array_sum($vals) / $n;
            $var = 0;
            foreach ($vals as $v) { $var += ($v - $m) ** 2; }
            $sd = sqrt($var / $n);
            $mean[$k] = round($m, 1);
            // nur eine grobe Einordnung, keine Spannweite: sonst ließe sich zurückrechnen
            $agree[$k] = $sd < 1.5 ? 'einig' : ($sd < 3 ? 'gemischt' : 'uneinig');
        }
        $out['mean'] = $mean;
        $out['agree'] = $agree;
    }
    return $out;
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    $id = $_GET['team'] ?? '';
    if (!tc_valid_id($id) || !is_file(tc_file($id))) {
        tc_out(['ok' => false, 'error' => 'unbekannt'], 404);
    }
    $team = json_decode((string) file_get_contents(tc_file($id)), true) ?: [];
    tc_out(tc_summary($team));
}

if ($method !== 'POST') {
    tc_out(['ok' => false], 405);
}

$d = json_decode((string) file_get_contents('php://input'), true);
if (!is_array($d)) { $d = $_POST; }
$action = $d['action'] ?? '';
$answers = $d['antworten'] ?? '';
if (!tc_valid_answers($answers)) {
    tc_out(['ok' => false, 'error' => 'antworten'], 422);
}

$dir = __DIR__ . '/data/teams';
if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
    tc_out(['ok' => false, 'error' => 'speicher'], 500);
}

if ($action === 'create') {
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789';
    do {
        $id = '';
        for ($i = 0; $i < 12; $i++) { $id .= $chars[random_int(0, strlen($chars) - 1)]; }
    } while (is_file(tc_file($id)));
    $team = ['created' => date('Y-m-d H:i'), 'members' => [$answers]];
    if (file_put_contents(tc_file($id), json_encode($team), LOCK_EX) === false) {
        tc_out(['ok' => false, 'error' => 'speicher'], 500);
    }
    $out = tc_summary($team);
    $out['team'] = $id;
    tc_out($out);
}

if ($action === 'add') {
    $id = $d['team'] ?? '';
    if (!tc_valid_id($id) || !is_file(tc_file($id))) {
        tc_out(['ok' => false, 'error' => 'unbekannt'], 404);
    }
    $fh = fopen(tc_file($id), 'c+');
    if (!$fh) { tc_out(['ok' => false, 'error' => 'speicher'], 500); }
    flock($fh, LOCK_EX);
    $team = json_decode((string) stream_get_contents($fh), true) ?: ['members' => []];
    if (count($team['members'] ?? []) >= MAX_TEAM) {
        flock($fh, LOCK_UN); fclose($fh);
        tc_out(['ok' => false, 'error' => 'voll'], 409);
    }
    $team['members'][] = $answers;
    ftruncate($fh, 0);
    rewind($fh);
    fwrite($fh, json_encode($team));
    fflush($fh);
    flock($fh, LOCK_UN);
    fclose($fh);
    tc_out(tc_summary($team));
}

tc_out(['ok' => false], 400);
