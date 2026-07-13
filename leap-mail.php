<?php
/**
 * LEAP website — shared form-handling helpers.
 * Sends notification mail (via Brevo API if configured, else PHP mail())
 * and optionally syncs the sender into a Brevo contact list.
 */

function leap_config(): array
{
    $defaults = [
        'notify_email' => 'paul@ich-leaps.at',
        'brevo_api_key' => null,
        'brevo_lists' => [
            'kontakt' => null,
            'programm' => null,
            'kit' => null,
            'kauf' => null,
        ],
        'brevo_attribute' => 'INTERESSE',
        'ablefy_webhook_token' => null,
        'ablefy_products' => [],
    ];
    $file = __DIR__ . '/config.php';
    if (is_file($file)) {
        $custom = include $file;
        if (is_array($custom)) {
            $merged = array_merge($defaults, $custom);
            if (isset($custom['brevo_lists']) && is_array($custom['brevo_lists'])) {
                $merged['brevo_lists'] = array_merge($defaults['brevo_lists'], $custom['brevo_lists']);
            }
            return $merged;
        }
    }
    return $defaults;
}

function leap_json_response(bool $ok, string $message = '', int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => $ok, 'message' => $message]);
    exit;
}

function leap_read_json_body(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === '' || $raw === false) {
        return $_POST;
    }
    $data = json_decode($raw, true);
    return is_array($data) ? $data : $_POST;
}

function leap_clean(string $value, int $maxLength = 2000): string
{
    $value = trim($value);
    $value = str_replace(["\r", "\n"], ' ', $value);
    return mb_substr($value, 0, $maxLength);
}

/**
 * Sends a notification email. Tries Brevo's transactional API first
 * (if an API key is configured), falls back to PHP mail() otherwise
 * or if the Brevo call fails.
 */
function leap_send_notification(array $config, string $subject, string $htmlBody, string $replyToEmail = '', string $replyToName = ''): bool
{
    if (!empty($config['brevo_api_key'])) {
        $sent = leap_send_via_brevo($config, $subject, $htmlBody, $replyToEmail, $replyToName);
        if ($sent) {
            return true;
        }
    }
    return leap_send_via_mail($config, $subject, $htmlBody, $replyToEmail);
}

function leap_send_via_brevo(array $config, string $subject, string $htmlBody, string $replyToEmail, string $replyToName): bool
{
    $payload = [
        'sender' => ['name' => 'LEAP Website', 'email' => $config['notify_email']],
        'to' => [['email' => $config['notify_email'], 'name' => 'LEAP']],
        'subject' => $subject,
        'htmlContent' => $htmlBody,
    ];
    if ($replyToEmail !== '') {
        $payload['replyTo'] = ['email' => $replyToEmail, 'name' => $replyToName !== '' ? $replyToName : $replyToEmail];
    }

    $ch = curl_init('https://api.brevo.com/v3/smtp/email');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'api-key: ' . $config['brevo_api_key'],
            'accept: application/json',
        ],
        CURLOPT_TIMEOUT => 10,
    ]);
    curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return $status >= 200 && $status < 300;
}

function leap_send_via_mail(array $config, string $subject, string $htmlBody, string $replyToEmail): bool
{
    $to = $config['notify_email'];
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: LEAP Website <{$to}>\r\n";
    if ($replyToEmail !== '') {
        $headers .= "Reply-To: {$replyToEmail}\r\n";
    }
    return @mail($to, $subject, $htmlBody, $headers);
}

/**
 * Adds/updates a contact in Brevo (no-op if not configured), placing them
 * into the Brevo lists identified by $listKeys (looked up in
 * $config['brevo_lists']; unresolved/null keys are skipped) and stamping
 * the configured custom attribute (default: INTERESSE) with $interesse — a
 * short descriptive slug like "kit_vertrauen_warteliste" or
 * "programm_veraenderung_anmeldung" — so a handful of lists can still tell
 * leads apart by what exactly they did.
 */
function leap_sync_brevo_contact(array $config, string $email, array $listKeys = [], ?string $interesse = null): void
{
    if (empty($config['brevo_api_key']) || $email === '') {
        return;
    }

    $lists = $config['brevo_lists'] ?? [];
    $listIds = [];
    foreach ($listKeys as $key) {
        if (!empty($lists[$key])) {
            $listIds[] = (int) $lists[$key];
        }
    }
    $listIds = array_values(array_unique($listIds));

    $payload = [
        'email' => $email,
        'updateEnabled' => true,
    ];
    if ($interesse !== null && $interesse !== '' && !empty($config['brevo_attribute'])) {
        $payload['attributes'] = [$config['brevo_attribute'] => $interesse];
    }
    if (!empty($listIds)) {
        $payload['listIds'] = $listIds;
    }

    $ch = curl_init('https://api.brevo.com/v3/contacts');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'api-key: ' . $config['brevo_api_key'],
            'accept: application/json',
        ],
        CURLOPT_TIMEOUT => 10,
    ]);
    curl_exec($ch);
    curl_close($ch);
}
