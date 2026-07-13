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
        'vimeo_welcome_url' => null,
        'miro_api_token' => null,
        'miro_templates' => [
            'vertrauen' => null,
            'rollen' => null,
            'feedback' => null,
        ],
        'miro_board_lifetime_days' => 30,
        'miro_cron_token' => null,
    ];
    $file = __DIR__ . '/config.php';
    if (is_file($file)) {
        $custom = include $file;
        if (is_array($custom)) {
            $merged = array_merge($defaults, $custom);
            if (isset($custom['brevo_lists']) && is_array($custom['brevo_lists'])) {
                $merged['brevo_lists'] = array_merge($defaults['brevo_lists'], $custom['brevo_lists']);
            }
            if (isset($custom['miro_templates']) && is_array($custom['miro_templates'])) {
                $merged['miro_templates'] = array_merge($defaults['miro_templates'], $custom['miro_templates']);
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

/**
 * Sends an email with file attachments to an arbitrary recipient (unlike
 * leap_send_notification, which always mails notify_email). Used for the
 * customer-facing kit-fulfillment email. $attachments is a list of
 * ['path' => absolute filesystem path, 'name' => filename shown to the
 * recipient]; missing files are silently skipped (caller should check for
 * that beforehand and flag it in the internal notification).
 * Tries Brevo's transactional API first (supports attachments natively),
 * falls back to a hand-built MIME multipart PHP mail() otherwise.
 */
function leap_send_email_with_attachments(array $config, string $toEmail, string $toName, string $subject, string $htmlBody, array $attachments = []): bool
{
    $files = [];
    foreach ($attachments as $att) {
        if (!empty($att['path']) && is_file($att['path'])) {
            $files[] = $att;
        }
    }

    if (!empty($config['brevo_api_key'])) {
        if (leap_send_via_brevo_with_attachments($config, $toEmail, $toName, $subject, $htmlBody, $files)) {
            return true;
        }
    }
    return leap_send_via_mail_with_attachments($config, $toEmail, $toName, $subject, $htmlBody, $files);
}

function leap_send_via_brevo_with_attachments(array $config, string $toEmail, string $toName, string $subject, string $htmlBody, array $files): bool
{
    $payload = [
        'sender' => ['name' => 'LEAP', 'email' => $config['notify_email']],
        'to' => [['email' => $toEmail, 'name' => $toName !== '' ? $toName : $toEmail]],
        'subject' => $subject,
        'htmlContent' => $htmlBody,
    ];

    $attachmentPayload = [];
    foreach ($files as $file) {
        $content = @file_get_contents($file['path']);
        if ($content === false) {
            continue;
        }
        $attachmentPayload[] = [
            'name' => $file['name'],
            'content' => base64_encode($content),
        ];
    }
    if (!empty($attachmentPayload)) {
        $payload['attachment'] = $attachmentPayload;
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
        CURLOPT_TIMEOUT => 30,
    ]);
    curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return $status >= 200 && $status < 300;
}

function leap_send_via_mail_with_attachments(array $config, string $toEmail, string $toName, string $subject, string $htmlBody, array $files): bool
{
    $boundary = 'leap-' . bin2hex(random_bytes(12));
    $from = $config['notify_email'];

    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "From: LEAP <{$from}>\r\n";
    $headers .= "Content-Type: multipart/mixed; boundary=\"{$boundary}\"\r\n";

    $body = "--{$boundary}\r\n";
    $body .= "Content-Type: text/html; charset=UTF-8\r\n";
    $body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
    $body .= $htmlBody . "\r\n";

    foreach ($files as $file) {
        $content = @file_get_contents($file['path']);
        if ($content === false) {
            continue;
        }
        $body .= "--{$boundary}\r\n";
        $body .= 'Content-Type: application/octet-stream; name="' . $file['name'] . "\"\r\n";
        $body .= "Content-Transfer-Encoding: base64\r\n";
        $body .= 'Content-Disposition: attachment; filename="' . $file['name'] . "\"\r\n\r\n";
        $body .= chunk_split(base64_encode($content)) . "\r\n";
    }
    $body .= "--{$boundary}--";

    $toHeader = $toName !== '' ? "{$toName} <{$toEmail}>" : $toEmail;
    return @mail($toHeader, $subject, $body, $headers);
}

/**
 * Creates the customer's personal Miro board (named $boardName, e.g.
 * "Erika LEAP Workshop Kit Vertrauen") by duplicating the template board
 * configured for $kit, and sets it to "anyone with the link can edit" —
 * no Miro account needed for the customer or their team. No password
 * (Miro doesn't support setting one via API at all — see INTEGRATIONS.md);
 * instead access is time-boxed by leap_expire_miro_boards() elsewhere.
 *
 * IMPORTANT: Miro's exact REST API behavior here (copy-board response
 * shape, and whether `sharingPolicy.access: "edit"` reliably grants
 * edit rights to anonymous visitors — Miro's own community has flagged
 * this as being beta/inconsistent via the API) was not verified against
 * a real Miro account/plan while building this — treat it as a
 * best-effort first cut that may need adjusting after the first real
 * test purchase.
 */
function leap_create_miro_board(array $config, string $kit, string $boardName): array
{
    $result = ['link' => null, 'boardId' => null, 'error' => null];

    if (empty($config['miro_api_token'])) {
        $result['error'] = 'Kein Miro-API-Token in config.php hinterlegt.';
        return $result;
    }
    $templateId = $config['miro_templates'][$kit] ?? null;
    if (empty($templateId)) {
        $result['error'] = "Keine Miro-Vorlagen-Board-ID für Kit '{$kit}' in config.php hinterlegt.";
        return $result;
    }

    $ch = curl_init('https://api.miro.com/v2/boards/' . rawurlencode($templateId) . '/copy');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode([
            'name' => $boardName,
            'policy' => [
                'sharingPolicy' => [
                    // "Jeder mit dem Link kann bearbeiten" — kein Account nötig.
                    'access' => 'edit',
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
    $response = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($status < 200 || $status >= 300) {
        $result['error'] = "Miro copy-board fehlgeschlagen (HTTP {$status}): " . substr((string) $response, 0, 500);
        return $result;
    }

    $data = json_decode((string) $response, true);
    $boardId = $data['id'] ?? null;
    $result['boardId'] = $boardId;
    $result['link'] = $data['viewLink'] ?? ($boardId ? ('https://miro.com/app/board/' . $boardId . '/') : null);

    if (!$boardId) {
        $result['error'] = 'Miro copy-board Antwort enthielt keine Board-ID.';
    }

    return $result;
}

/**
 * Appends a created board to the local expiry registry (data/miro-boards.json)
 * so leap-miro-expire.php can revoke public access once its lifetime is up.
 */
function leap_register_miro_board(array $config, string $boardId, string $kit, string $buyerEmail): void
{
    $days = (int) ($config['miro_board_lifetime_days'] ?? 30);
    $dir = __DIR__ . '/data';
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    $file = $dir . '/miro-boards.json';
    $entries = [];
    if (is_file($file)) {
        $decoded = json_decode((string) file_get_contents($file), true);
        if (is_array($decoded)) {
            $entries = $decoded;
        }
    }
    $entries[] = [
        'boardId' => $boardId,
        'kit' => $kit,
        'buyerEmail' => $buyerEmail,
        'createdAt' => gmdate('c'),
        'expiresAt' => gmdate('c', time() + $days * 86400),
        'expired' => false,
    ];
    @file_put_contents($file, json_encode($entries, JSON_PRETTY_PRINT));
}

/**
 * Finds a deliverable file for $kit trying a few common extensions, e.g.
 * leap_find_deliverable('vertrauen', 'facilitator-guide') looks for
 * deliverables/vertrauen/facilitator-guide.{pdf,pptx,ppt,docx,zip}.
 * Returns null if none of them exist.
 */
function leap_find_deliverable(string $kit, string $basename): ?string
{
    $dir = __DIR__ . '/deliverables/' . $kit;
    foreach (['pdf', 'pptx', 'ppt', 'docx', 'zip'] as $ext) {
        $path = $dir . '/' . $basename . '.' . $ext;
        if (is_file($path)) {
            return $path;
        }
    }
    return null;
}

/**
 * Renders the LEAP-styled kit-confirmation email (email-templates/
 * kit-bestellbestaetigung.html) for a customer purchase. $attachmentLabels
 * lists what's actually attached (e.g. ['Facilitator Guide', 'Agenda']) —
 * only files that were actually found get listed. $vimeoUrl / $miroLink
 * may be null (video block / Miro section fall back accordingly).
 */
function leap_render_kit_email(string $kitLabel, string $firstName, array $attachmentLabels, ?string $vimeoUrl, ?string $miroLink, int $lifetimeDays): string
{
    $html = (string) file_get_contents(__DIR__ . '/email-templates/kit-bestellbestaetigung.html');

    $rows = '';
    foreach ($attachmentLabels as $label) {
        $rows .= '<div style="display: flex; gap: 10px; align-items: baseline;"><span style="font-weight: 700; color: var(--leap-lime); font-size: 13px;">&rarr;</span><span style="font-weight: 400; font-size: 14px; color: #000;">' . htmlspecialchars($label, ENT_QUOTES) . '</span></div>';
    }

    $svgPath = __DIR__ . '/email-templates/footer-curve.svg';
    $footerImageSrc = is_file($svgPath) ? 'data:image/svg+xml;base64,' . base64_encode((string) file_get_contents($svgPath)) : '';

    $html = str_replace('{{FIRST_NAME_GREETING}}', $firstName !== '' ? ', ' . htmlspecialchars($firstName, ENT_QUOTES) : '', $html);
    $html = str_replace('{{KIT_LABEL}}', htmlspecialchars($kitLabel, ENT_QUOTES), $html);
    $html = str_replace('{{ATTACHMENT_ROWS}}', $rows, $html);
    $html = str_replace('{{LIFETIME_DAYS}}', (string) $lifetimeDays, $html);
    $html = str_replace('{{FOOTER_IMAGE_SRC}}', $footerImageSrc, $html);

    if ($vimeoUrl !== null && $vimeoUrl !== '') {
        $html = str_replace('{{VIMEO_URL}}', htmlspecialchars($vimeoUrl, ENT_QUOTES), $html);
        $html = leap_keep_template_block($html, 'VIDEO_BLOCK', true);
    } else {
        $html = leap_keep_template_block($html, 'VIDEO_BLOCK', false);
    }

    if ($miroLink !== null && $miroLink !== '') {
        $html = str_replace('{{MIRO_LINK}}', htmlspecialchars($miroLink, ENT_QUOTES), $html);
        $html = leap_keep_template_block($html, 'MIRO_BLOCK', true);
        $html = leap_keep_template_block($html, 'MIRO_FALLBACK', false);
    } else {
        $html = leap_keep_template_block($html, 'MIRO_BLOCK', false);
        $html = leap_keep_template_block($html, 'MIRO_FALLBACK', true);
    }

    return $html;
}

function leap_keep_template_block(string $html, string $name, bool $keep): string
{
    $pattern = '/<!--' . $name . '_START-->(.*?)<!--' . $name . '_END-->/s';
    return (string) preg_replace_callback($pattern, function ($m) use ($keep) {
        return $keep ? $m[1] : '';
    }, $html);
}
