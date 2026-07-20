<?php
/**
 * LEAP website — Ablefy-Kauf-Webhook (Empfänger).
 *
 * Trag diese URL in Ablefy als Webhook-Ziel ein, inkl. Secret-Token:
 *   https://deine-domain.at/ablefy-webhook.php?token=DEIN_GEHEIMNIS
 * (Token in config.php unter 'ablefy_webhook_token' festlegen.)
 *
 * Feldnamen laut Ablefys offizieller Webhook-Doku (Käufer:in/Produktdaten/
 * Zusätzliche Informationen). Unklar blieb nur die genaue JSON-Verschachtelung
 * (flach vs. unter "buyer"/"product" gruppiert) — deshalb wird beides
 * probiert. Trotzdem verschickt dieses Skript IMMER zusätzlich das
 * komplette Rohdaten-Payload an dich, falls doch mal ein Feld nicht wie
 * erwartet ankommt.
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

$buyerEmail = (string) (leap_dig($data, ['payer.email', 'email', 'buyer.email', 'buyer_email']) ?? '');
$firstNameField = (string) (leap_dig($data, ['payer.first_name', 'first_name', 'buyer.first_name', 'buyer_first_name']) ?? '');
$lastNameField = (string) (leap_dig($data, ['payer.last_name', 'last_name', 'buyer.last_name', 'buyer_last_name']) ?? '');
$buyerName = trim($firstNameField . ' ' . $lastNameField);

// Produkt: slug ist am zuverlässigsten für die Kit-Zuordnung, weil wir die
// echten Slugs schon aus den Ablefy-Checkout-Links kennen (siehe
// ablefy_products in config.php) — Produkt-ID/-Name als Fallback.
$productSlug = (string) (leap_dig($data, ['product.slug', 'product_slug', 'slug']) ?? '');
$productLabel = (string) (leap_dig($data, ['product.name', 'product_name', 'name']) ?? '');
$productId = (string) (leap_dig($data, ['product.id', 'product_id']) ?? '');
$internalProductName = (string) (leap_dig($data, ['product.internal_product_name', 'internal_product_name', 'internal product name']) ?? '');

$amount = (string) (leap_dig($data, ['order_amount_gross', 'amount', 'product.price', 'price', 'revenue']) ?? '');
$billNumber = (string) (leap_dig($data, ['bill_number']) ?? '');
$orderId = $billNumber !== '' ? $billNumber : (string) (leap_dig($data, ['order_id', 'transaction_id']) ?? '');
$state = (string) (leap_dig($data, ['payment_state', 'state']) ?? '');
$paymentMethod = (string) (leap_dig($data, ['payment_method']) ?? '');
$successDate = (string) (leap_dig($data, ['success_date', 'success_date_short']) ?? '');
$invoiceLink = (string) (leap_dig($data, ['invoice_link']) ?? '');

$productMap = $config['ablefy_products'] ?? [];
$kit = $productMap[$productSlug] ?? $productMap[$productId] ?? $productMap[$productLabel] ?? $productMap[$internalProductName] ?? null;

$rows = [
    'Token gültig' => $tokenOk ? 'Ja' : 'NEIN — bitte prüfen! (falsches/fehlendes Token in der Ablefy-Webhook-URL)',
    'Zahlungsstatus' => $state !== '' ? $state : '(unbekannt)',
    'Käufer:in' => $buyerName !== '' ? $buyerName : '(unbekannt)',
    'E-Mail' => $buyerEmail !== '' ? $buyerEmail : '(unbekannt)',
    'Produkt' => $productLabel !== '' ? $productLabel : '(unbekannt)',
    'Produkt-Slug' => $productSlug !== '' ? $productSlug : '(unbekannt)',
    'Produkt-ID' => $productId !== '' ? $productId : '(unbekannt)',
    'Zugeordnetes Kit' => $kit ?? '(noch nicht zugeordnet — siehe ablefy_products in config.php)',
    'Betrag' => $amount !== '' ? $amount : '(unbekannt)',
    'Zahlungsart' => $paymentMethod !== '' ? $paymentMethod : '(unbekannt)',
    'Rechnungsnummer' => $billNumber !== '' ? $billNumber : '(unbekannt)',
    'Zahlungsdatum' => $successDate !== '' ? $successDate : '(unbekannt)',
];
if ($invoiceLink !== '') {
    $rows['Rechnung'] = $invoiceLink;
}

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

// Guard against Ablefy retrying/duplicating a webhook delivery for the
// same order — without this, a duplicate would email the customer a
// second time and spin up a second Miro board.
$isDuplicate = false;
if ($tokenOk && $orderId !== '') {
    $isDuplicate = leap_is_duplicate_order($orderId);
}

if ($tokenOk && $kit !== null && $buyerEmail !== '' && !$isDuplicate) {
    // Echte Dateinamen, wie sie tatsächlich hochgeladen wurden
    // (LEAP_<Typ>_<Kit>, z.B. LEAP_FacilitatorGuide_Vertrauen) — mit den
    // generischen Namen als Fallback. "E-Mail-Vorlagen" ist uneinheitlich
    // benannt (mal mit Bindestrich, mal ohne), deshalb beide Varianten.
    $kitTitle = $kitBoardTitles[$kit] ?? ucfirst($kit);
    $deliverables = [
        'Facilitator Guide' => leap_find_deliverable($kit, ["LEAP_FacilitatorGuide_{$kitTitle}", 'facilitator-guide']),
        'Agenda' => leap_find_deliverable($kit, ["LEAP_Agenda_{$kitTitle}", 'agenda']),
        'Präsentation' => leap_find_deliverable($kit, ["LEAP_Präsentation_{$kitTitle}", 'praesentation']),
        'E-Mail-Vorlagen' => leap_find_deliverable($kit, ["LEAP_E-Mail_Vorlagen_{$kitTitle}", "LEAP_EmailVorlagen_{$kitTitle}", 'email-vorlagen']),
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
    $firstName = $firstNameField;
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
    $attachedLabels = array_keys(array_filter($deliverables, function ($path) { return $path !== null; }));
    $customerHtml = leap_render_kit_email(
        $kitLabel2,
        $firstName,
        $attachedLabels,
        $config['vimeo_welcome_url'] ?? null,
        $miro['link'],
        $lifetimeDays
    );

    $customerSent = leap_send_email_with_attachments($config, $buyerEmail, $buyerName, 'Dein LEAP-Kit: ' . $kitLabel2, $customerHtml, $attachments);
    if (!$customerSent) {
        $fulfillmentNotes[] = 'Kunden-Fulfillment-Mail konnte nicht zugestellt werden (weder Brevo noch mail()).';
    }
} elseif ($tokenOk && $isDuplicate) {
    $fulfillmentNotes[] = "Bestellung {$orderId} wurde bereits verarbeitet — Dublette übersprungen, keine zweite Kunden-Mail/kein zweites Miro-Board.";
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
