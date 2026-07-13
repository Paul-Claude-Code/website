<?php
/**
 * LEAP website — Miro-Board-Ablauf (Cronjob).
 *
 * Läuft regelmäßig (z.B. täglich) und entzieht den öffentlichen
 * Bearbeiten-Link von Kunden-Miro-Boards, deren Lebensdauer
 * (config.php → 'miro_board_lifetime_days', Standard 30 Tage) abgelaufen
 * ist — das Board selbst bleibt erhalten (nur nicht mehr per Link
 * erreichbar), falls du später nochmal reinschauen willst.
 *
 * Einrichtung auf World4You: im Kundenmenü/Control Panel nach "Cronjob"
 * suchen und EINE der beiden Varianten einrichten (je nachdem, was dein
 * Hosting-Paket anbietet):
 *   a) PHP-CLI-Aufruf, z.B. täglich um 4 Uhr:
 *      php /pfad/zu/deinem/webspace/leap-miro-expire.php
 *   b) URL-Aufruf (falls nur "URL per Cron abrufen" angeboten wird):
 *      https://ich-leaps.at/leap-miro-expire.php?token=DEIN_CRON_TOKEN
 *      (Token in config.php bei 'miro_cron_token' festlegen — sonst kann
 *      jeder diese URL aufrufen und Boards vorzeitig sperren.)
 */

require __DIR__ . '/leap-mail.php';

$isCli = PHP_SAPI === 'cli';

if (!$isCli) {
    $config = leap_config();
    $expectedToken = $config['miro_cron_token'] ?? null;
    $providedToken = $_GET['token'] ?? '';
    if (!empty($expectedToken) && !hash_equals((string) $expectedToken, (string) $providedToken)) {
        leap_json_response(false, 'Ungültiger Token.', 403);
    }
} else {
    $config = leap_config();
}

$file = __DIR__ . '/data/miro-boards.json';
if (!is_file($file)) {
    if ($isCli) {
        echo "Keine data/miro-boards.json gefunden — nichts zu tun.\n";
    } else {
        leap_json_response(true, 'Keine Boards zum Prüfen.');
    }
    exit;
}

$entries = json_decode((string) file_get_contents($file), true);
if (!is_array($entries)) {
    $entries = [];
}

$now = time();
$expiredNow = [];
$errors = [];

foreach ($entries as &$entry) {
    if (!empty($entry['expired'])) {
        continue;
    }
    $expiresAt = strtotime((string) ($entry['expiresAt'] ?? ''));
    if ($expiresAt === false || $expiresAt > $now) {
        continue;
    }

    $ok = leap_revoke_miro_board_access($config, (string) $entry['boardId']);
    if ($ok) {
        $entry['expired'] = true;
        $entry['expiredAt'] = gmdate('c');
        $expiredNow[] = $entry;
    } else {
        $errors[] = "Board {$entry['boardId']} (Kit {$entry['kit']}, {$entry['buyerEmail']}) konnte nicht gesperrt werden.";
    }
}
unset($entry);

file_put_contents($file, json_encode($entries, JSON_PRETTY_PRINT));

if (!empty($expiredNow) || !empty($errors)) {
    $html = '<h2>Miro-Board-Ablauf ausgeführt</h2>';
    if (!empty($expiredNow)) {
        $html .= '<p><strong>' . count($expiredNow) . ' Board(s) gesperrt:</strong></p><ul>';
        foreach ($expiredNow as $e) {
            $html .= '<li>' . htmlspecialchars($e['kit'] . ' — ' . $e['buyerEmail'] . ' (Board ' . $e['boardId'] . ')', ENT_QUOTES) . '</li>';
        }
        $html .= '</ul>';
    }
    if (!empty($errors)) {
        $html .= '<p><strong>Fehler:</strong></p><ul>';
        foreach ($errors as $e) {
            $html .= '<li>' . htmlspecialchars($e, ENT_QUOTES) . '</li>';
        }
        $html .= '</ul>';
    }
    leap_send_notification($config, 'LEAP: Miro-Board-Ablauf', $html);
}

if ($isCli) {
    echo count($expiredNow) . " Board(s) gesperrt, " . count($errors) . " Fehler.\n";
} else {
    leap_json_response(true, count($expiredNow) . ' Board(s) gesperrt, ' . count($errors) . ' Fehler.');
}

/**
 * Revokes public link access on a Miro board by setting its sharing
 * policy back to private. Same caveat as leap_create_miro_board(): not
 * verified against a real Miro account/plan.
 */
function leap_revoke_miro_board_access(array $config, string $boardId): bool
{
    if (empty($config['miro_api_token'])) {
        return false;
    }
    $ch = curl_init('https://api.miro.com/v2/boards/' . rawurlencode($boardId));
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => 'PATCH',
        CURLOPT_POSTFIELDS => json_encode([
            'policy' => [
                'sharingPolicy' => [
                    'access' => 'private',
                    'inviteToAccountAndBoardLinkAccess' => 'no_access',
                ],
            ],
        ]),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $config['miro_api_token'],
            'Accept: application/json',
        ],
        CURLOPT_TIMEOUT => 20,
    ]);
    curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return $status >= 200 && $status < 300;
}
