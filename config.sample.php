<?php
/**
 * LEAP website — server config.
 *
 * Copy this file to config.php (same folder) and adjust values.
 * config.php is gitignored — it never gets committed, so secrets stay
 * on the server only. Without config.php, the site still works: forms
 * fall back to plain PHP mail() to $notifyEmail below.
 */
return [
    // Where contact-form and waitlist-signup notifications are sent.
    'notify_email' => 'paul@ich-leaps.at',

    // Optional: Brevo API key (Brevo → SMTP & API → API Keys & MCP → "Generate
    // a new API key"). When set, notification mails are sent through Brevo's
    // transactional email API instead of PHP mail() (better deliverability),
    // and new contacts are added to the Brevo list(s) below for CRM/follow-ups.
    'brevo_api_key' => null,

    // 4 Brevo-Listen-IDs — je eine pro Herkunft (Brevo → Contacts → Lists →
    // Create a list; die ID steht danach in der Browser-Adresszeile, z.B.
    // .../lists/id/47 → 47). Leer lassen (null), um eine Kategorie nicht zu
    // tracken.
    'brevo_lists' => [
        'kontakt' => null,   // jede Kontaktformular-Anfrage
        'programm' => null,  // Kontaktformular-Anfragen mit konkretem Programm-Kontext
        'kit' => null,       // Kit-Warteliste-Anmeldungen ("Früher Zugang")
        'kauf' => null,      // echte Käufe über Ablefy
    ],

    // Name des Custom-Attributs, das den genauen Grund/Kontext trägt (z.B.
    // "kit_vertrauen_warteliste", "programm_veraenderung_anmeldung",
    // "kauf_vertrauen") — so reichen 4 Listen statt einer Liste pro Kit/
    // Programm. WICHTIG: Dieses Attribut musst du EINMALIG manuell in Brevo
    // anlegen, bevor die API es setzen kann (sonst schlägt der Aufruf fehl):
    // Brevo → Contacts → Settings (Zahnrad) → Contact attributes →
    // "Add a new attribute" → Name "INTERESSE", Type "Text", Category
    // "Normal attribute". Muss exakt so heißen wie hier eingetragen
    // (Brevo schreibt Attributnamen intern in Großbuchstaben).
    'brevo_attribute' => 'INTERESSE',

    // Geheimer String deiner Wahl, den du in der Ablefy-Webhook-URL als
    // ?token=... anhängst (z.B. https://ich-leaps.at/ablefy-webhook.php?token=DEIN_GEHEIMNIS).
    // So kann niemand außer Ablefy selbst gefälschte "Kauf"-Meldungen an
    // uns schicken. Frei erfinden, einfach ein langer zufälliger String.
    'ablefy_webhook_token' => null,

    // Ordnet den Produkt-Slug (bevorzugt), die Produkt-ID oder den
    // Produktnamen, die Ablefy im Webhook mitschickt, einem unserer drei
    // Kits zu. Bereits mit den echten Slugs aus den Ablefy-Checkout-Links
    // vorausgefüllt — beim ersten echten Kauf trotzdem einmal die
    // Rohdaten-Benachrichtigung gegenchecken, falls Ablefy doch ein
    // anderes Feld verwendet als erwartet.
    'ablefy_products' => [
        'workshop-kit-vertrauen-e315ec6e' => 'vertrauen',
        'workshop-kit-rollen-f8a7f57e' => 'rollen',
        'workshop-kit-feedback-79a89d9b' => 'feedback',
    ],

    // ---- Kunden-Fulfillment nach echtem Kauf (Facilitator Guide, Agenda,
    // Präsentation als Anhang + Begrüßungsvideo + persönliches Miro-Board) ----

    // Dein Begrüßungsvideo auf Vimeo (ein Link für alle Kits).
    'vimeo_welcome_url' => null,

    // Miro-API-Token (Miro → Settings → Your apps → App erstellen →
    // "Install app and get OAuth token" → Token kopieren). Braucht die
    // Scopes boards:read, boards:write.
    'miro_api_token' => null,

    // Board-ID deines fertigen Vorlagen-Boards pro Kit (in Miro-URL:
    // https://miro.com/app/board/BOARD_ID_HIER/ → der Teil zwischen
    // /board/ und der abschließenden / ist die ID). Wird bei jedem Kauf
    // dupliziert, damit der Kunde sein eigenes Board bekommt.
    'miro_templates' => [
        'vertrauen' => null,
        'rollen' => null,
        'feedback' => null,
    ],

    // Board ist öffentlich per Link erreichbar (kein Account nötig), aber
    // nur für diese Anzahl Tage — danach entzieht leap-miro-expire.php
    // (per Cronjob) den öffentlichen Zugriff wieder (Board bleibt erhalten,
    // ist nur nicht mehr per Link erreichbar).
    'miro_board_lifetime_days' => 30,

    // Geheimer String deiner Wahl für den Cronjob-Aufruf von
    // leap-miro-expire.php per URL (falls dein World4You-Hosting nur
    // "URL per Cron abrufen" statt direktem PHP-Aufruf anbietet), z.B.
    // https://ich-leaps.at/leap-miro-expire.php?token=DEIN_GEHEIMNIS
    'miro_cron_token' => null,
];
