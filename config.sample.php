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

    // Ordnet die Produkt-ID oder den Produktnamen, die Ablefy im Webhook
    // mitschickt, einem unserer drei Kits zu — damit der Kauf mit dem
    // richtigen INTERESSE-Wert (z.B. "kauf_vertrauen") in Brevo landet. Die
    // genauen Schlüssel (Ablefys Produkt-ID oder -Name) siehst du im
    // Rohdaten-Dump der ersten Test-Benachrichtigung, die ablefy-webhook.php
    // dir schickt, sobald du in Ablefy einen Test-Webhook auslöst — trag sie
    // danach hier ein. Bis dahin funktioniert die Benachrichtigung trotzdem,
    // nur ohne Kit-genaue Brevo-Zuordnung.
    'ablefy_products' => [
        // 'ablefy-produkt-id-oder-name' => 'vertrauen',
        // 'ablefy-produkt-id-oder-name' => 'rollen',
        // 'ablefy-produkt-id-oder-name' => 'feedback',
    ],
];
