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

    // Optional: Brevo API key (Brevo → SMTP & API → API-Keys → "Create a new API key").
    // When set, notification mails are sent through Brevo's transactional
    // email API instead of PHP mail() (better deliverability), and new
    // contacts are added to the Brevo list(s) below for CRM/follow-ups.
    'brevo_api_key' => null,

    // Brevo list IDs (Brevo → Kontakte → Listen → Liste anlegen → ID steht
    // in der URL/Listenübersicht) — eine Liste pro Herkunft, damit du in
    // Brevo siehst wer sich wofür interessiert hat. Leer lassen (null) für
    // Kategorien, die du nicht separat tracken willst — die landen dann nur
    // in der 'kontakt'-Sammelliste (falls gesetzt) bzw. gar keiner Liste.
    'brevo_lists' => [
        // Kontaktformular — Sammelliste für alle Anfragen (Fallback)
        'kontakt' => null,
        // Kontaktformular, wenn ein bestimmtes Programm im Kontext war
        'programm_veraenderung' => null,       // "Bereit für die übernächste Veränderung?" (Leadership & Teamentwicklung)
        'programm_coaching_leader' => null,    // "Führen durch Fragen statt durch Antworten." (Coaching as a Leader)
        'programm_change_management' => null,  // "Veränderung gestalten statt verwalten." (Systemisches Change Management)
        // Workshop-Kit-Wartelisten ("Früher Zugang")
        'kit_alle' => null,       // Sammelliste über alle drei Kits
        'kit_vertrauen' => null,
        'kit_rollen' => null,
        'kit_feedback' => null,
        // Echte Käufer:innen über Ablefy (siehe ablefy_* unten) — eigene
        // Listen, weil "hat gekauft" eine andere Zielgruppe ist als
        // "steht auf der Warteliste".
        'kauf_alle' => null,
        'kauf_vertrauen' => null,
        'kauf_rollen' => null,
        'kauf_feedback' => null,
    ],

    // Geheimer String deiner Wahl, den du in der Ablefy-Webhook-URL als
    // ?token=... anhängst (z.B. https://ich-leaps.at/ablefy-webhook.php?token=DEIN_GEHEIMNIS).
    // So kann niemand außer Ablefy selbst gefälschte "Kauf"-Meldungen an
    // uns schicken. Frei erfinden, einfach ein langer zufälliger String.
    'ablefy_webhook_token' => null,

    // Ordnet die Produkt-ID oder den Produktnamen, die Ablefy im Webhook
    // mitschickt, einem unserer drei Kits zu — damit der Kauf in der
    // richtigen Brevo-Liste (kauf_vertrauen/kauf_rollen/kauf_feedback)
    // landet. Die genauen Schlüssel (Ablefys Produkt-ID oder -Name) siehst
    // du im Rohdaten-Dump der ersten Test-Benachrichtigung, die
    // ablefy-webhook.php dir schickt, sobald du in Ablefy einen Test-Webhook
    // auslöst — trag sie danach hier ein. Bis dahin funktioniert die
    // Benachrichtigung trotzdem, nur ohne Kit-genaue Brevo-Zuordnung.
    'ablefy_products' => [
        // 'ablefy-produkt-id-oder-name' => 'vertrauen',
        // 'ablefy-produkt-id-oder-name' => 'rollen',
        // 'ablefy-produkt-id-oder-name' => 'feedback',
    ],
];
