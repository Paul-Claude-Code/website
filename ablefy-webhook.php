<?php
/**
 * LEAP website — Ablefy-Kauf-Webhook (Empfänger).
 *
 * Trag diese URL in Ablefy als Webhook-Ziel ein, inkl. Secret-Token:
 *   https://deine-domain.at/ablefy-webhook.php?token=DEIN_GEHEIMNIS
 * (Token in config.php unter 'ablefy_webhook_token' festlegen.)
 *
 * Ablefys genaues Payload-Format war zum Zeitpunkt der Umsetzung nicht
 * einsehbar (Doku hinter Login) — dieses Skript verschickt deshalb IMMER
 * das komplette Rohdaten-Payload an dich (nichts geht verloren), plus
 * einen Best-Effort-Versuch, E-Mail/Produkt/Betrag herauszulesen. Sobald
 * ein echter Test-Kauf/Test-Webhook durchgelaufen ist, kann die Zuordnung
 * (siehe 'ablefy_products' in config.php) auf die tatsächlichen Feldnamen
 * geschärft werden.
 */

require __DIR__ . '/leap-mail.php';

// Some webhook providers ping the URL with GET once when it's first saved.
// Answer harmlessly instead of erroring, in case Ablefy does that too.
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'GET') {
    leap_json_response(true, 'LEAP Ablefy-Webhook ist bereit.');
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    leap_json_response(false, 'Methode nicht erlaubt.', 405);
}

$config = leap_config();

// Basic authenticity check via shared-secret token in the URL (see header
// comment). If no token is configured yet, we still accept the webhook
// (so it works before setup is finished) but flag it clearly in the email.
$expectedToken = $config['ablefy_webhook_token'] ?? null;
$providedToken = $_GET['token'] ?? '';
$tokenOk = empty($expectedToken) || hash_equals((string) $expectedToken, (string) $providedToken);

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) {
    $data = $_POST;
}

// Best-effort extraction — Ablefy's exact field names are unconfirmed, so
// we probe a handful of plausible paths rather than assuming one shape.
function leap_dig(array $data, array $paths)
{
    foreach ($paths as $path) {
        $cursor = $data;
        $found = true;
        foreach (explode('.', $path) as $segment) {
            if (is_array($cursor) && array_key_exists($segment, $cursor)) {
                $cursor = $cursor[$segment];
            } else {
                $found = false;
                break;
            }
        }
        if ($found && $cursor !== null && $cursor !== '') {
            return $cursor;
        }
    }
    return null;
}

$buyerEmail = (string) (leap_dig($data, ['email', 'buyer_email', 'customer.email', 'customer_email', 'order.email', 'data.email', 'data.customer.email']) ?? '');
$buyerName = (string) (leap_dig($data, ['name', 'buyer_name', 'customer.name', 'customer_name', 'order.name', 'data.name', 'data.customer.name', 'first_name']) ?? '');
$productLabel = (string) (leap_dig($data, ['product', 'product_name', 'product.name', 'item.name', 'order.product_name', 'data.product.name']) ?? '');
$productId = (string) (leap_dig($data, ['product_id', 'product.id', 'item.id', 'order.product_id', 'data.product.id']) ?? '');
$amount = (string) (leap_dig($data, ['amount', 'price', 'total', 'order.total', 'data.amount']) ?? '');
$orderId = (string) (leap_dig($data, ['order_id', 'id', 'order.id', 'transaction_id', 'data.id']) ?? '');
$eventType = (string) (leap_dig($data, ['event', 'type', 'event_type']) ?? '');

$productMap = $config['ablefy_products'] ?? [];
$kit = $productMap[$productId] ?? $productMap[$productLabel] ?? null;

$rows = [
    'Token gültig' => $tokenOk ? 'Ja' : 'NEIN — bitte prüfen! (falsches/fehlendes Token in der Ablefy-Webhook-URL)',
    'Event' => $eventType !== '' ? $eventType : '(unbekannt)',
    'Käufer:in' => $buyerName !== '' ? $buyerName : '(unbekannt)',
    'E-Mail' => $buyerEmail !== '' ? $buyerEmail : '(unbekannt)',
    'Produkt' => $productLabel !== '' ? $productLabel : '(unbekannt)',
    'Produkt-ID' => $productId !== '' ? $productId : '(unbekannt)',
    'Zugeordnetes Kit' => $kit ?? '(noch nicht zugeordnet — siehe ablefy_products in config.php)',
    'Betrag' => $amount !== '' ? $amount : '(unbekannt)',
    'Bestell-ID' => $orderId !== '' ? $orderId : '(unbekannt)',
];

// --- Customer fulfillment: only once we're sure this is a genuine,
// kit-identified purchase (valid token + resolved kit + known buyer email).
// Attaches the Facilitator Guide / Agenda / Präsentation from
// deliverables/<kit>/, links the Vimeo welcome video, and creates +
// shares the customer's own Miro board (duplicated from the kit's
// template board).
$kitLabels2 = [
    'vertrauen' => 'Vertrauen aufbauen',
    'rollen' => 'Rollen & Verantwortung',
    'feedback' => 'Feedback-Kultur aufbauen',
];
$kitBoardTitles = [
    'vertrauen' => 'Vertrauen',
    'rollen' => 'Rollen',
    'feedback' => 'Feedback',
];
$fulfillmentNotes = [];

if ($tokenOk && $kit !== null && $buyerEmail !== '') {
    $deliverables = [
        'Facilitator Guide' => leap_find_deliverable($kit, 'facilitator-guide'),
        'Agenda' => leap_find_deliverable($kit, 'agenda'),
        'Präsentation' => leap_find_deliverable($kit, 'praesentation'),
    ];
    $attachments = [];
    foreach ($deliverables as $label => $path) {
        if ($path !== null) {
            $attachments[] = ['path' => $path, 'name' => basename($path)];
        } else {
            $fulfillmentNotes[] = "Datei fehlt: '{$label}' für Kit '{$kit}' (erwartet in deliverables/{$kit}/, siehe INTEGRATIONS.md).";
        }
    }

    // "Vorname LEAP Workshop Kit <Kit>", z.B. "Erika LEAP Workshop Kit Vertrauen"
    $firstName = trim(explode(' ', trim($buyerName))[0] ?? '');
    $boardTitle = $kitBoardTitles[$kit] ?? $kit;
    $boardName = ($firstName !== '' ? $firstName . ' ' : '') . 'LEAP Workshop Kit ' . $boardTitle;

    $miro = leap_create_miro_board($config, $kit, $boardName);
    if ($miro['error'] !== null) {
        $fulfillmentNotes[] = 'Miro: ' . $miro['error'];
    }
    if ($miro['boardId'] !== null) {
        leap_register_miro_board($config, $miro['boardId'], $kit, $buyerEmail);
    }

    $lifetimeDays = (int) ($config['miro_board_lifetime_days'] ?? 30);
    $kitLabel2 = $kitLabels2[$kit] ?? $kit;
    $customerHtml = '<h2>Danke für deinen Kauf — ' . htmlspecialchars($kitLabel2, ENT_QUOTES) . '!</h2>';
    $customerHtml .= '<p>Im Anhang findest du Facilitator Guide, Agenda und Präsentation.</p>';
    if (!empty($config['vimeo_welcome_url'])) {
        $customerHtml .= '<p><strong>Begrüßungsvideo:</strong> <a href="' . htmlspecialchars($config['vimeo_welcome_url'], ENT_QUOTES) . '">' . htmlspecialchars($config['vimeo_welcome_url'], ENT_QUOTES) . '</a></p>';
    }
    if ($miro['link'] !== null) {
        $customerHtml .= '<p><strong>Dein persönliches Miro-Board:</strong> <a href="' . htmlspecialchars($miro['link'], ENT_QUOTES) . '">' . htmlspecialchars($miro['link'], ENT_QUOTES) . '</a></p>';
        $customerHtml .= '<p style="font-size:13px;color:#666;">Der Link ist ' . $lifetimeDays . ' Tage lang gültig — kein Miro-Account nötig, einfach öffnen und mit deinem Team loslegen.</p>';
    } else {
        $customerHtml .= '<p>Dein persönliches Miro-Board folgt in Kürze in einer separaten Mail.</p>';
    }
    $customerHtml .= '<p>Bei Fragen einfach auf diese Mail antworten — melden uns persönlich.<br>Freude und Führung gehören fix zam.<br>Paul</p>';

    $customerSent = leap_send_email_with_attachments($config, $buyerEmail, $buyerName, 'Dein LEAP-Kit: ' . $kitLabel2, $customerHtml, $attachments);
    if (!$customerSent) {
        $fulfillmentNotes[] = 'Kunden-Fulfillment-Mail konnte nicht zugestellt werden (weder Brevo noch mail()).';
    }
} elseif ($tokenOk && $kit === null) {
    $fulfillmentNotes[] = 'Kein Kit zugeordnet (siehe ablefy_products in config.php) — Kunden-Fulfillment-Mail wurde NICHT verschickt.';
}

if (!empty($fulfillmentNotes)) {
    $rows['Fulfillment-Hinweise'] = implode(' | ', $fulfillmentNotes);
}

$html = '<h2>Ablefy-Webhook eingegangen</h2><table cellpadding="6" cellspacing="0">';
foreach ($rows as $label => $value) {
    $html .= '<tr><td><strong>' . htmlspecialchars($label, ENT_QUOTES) . '</strong></td><td>' . htmlspecialchars((string) $value, ENT_QUOTES) . '</td></tr>';
}
$html .= '</table><p><strong>Komplettes Rohdaten-Payload</strong> (falls oben etwas als "unbekannt" steht, hilft das beim Nachjustieren der Feldnamen in ablefy-webhook.php):</p>';
$html .= '<pre style="background:#f5f5f5;padding:12px;white-space:pre-wrap;">' . htmlspecialchars(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), ENT_QUOTES) . '</pre>';

$subjectProduct = $productLabel !== '' ? $productLabel : 'unbekanntes Produkt';
leap_send_notification($config, 'Ablefy-Kauf: ' . $subjectProduct, $html, $buyerEmail, $buyerName);

if ($tokenOk && $buyerEmail !== '') {
    $interesse = $kit !== null ? ('kauf_' . $kit) : 'kauf_unbekannt';
    leap_sync_brevo_contact($config, $buyerEmail, ['kauf'], $interesse);
}

// Always answer 200 so Ablefy doesn't retry/disable the webhook — mail
// delivery failures are only visible to us, not something Ablefy can fix.
leap_json_response(true, 'Empfangen.');
