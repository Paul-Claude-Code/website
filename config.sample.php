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
    // contacts are added to the Brevo list below for CRM/follow-ups.
    'brevo_api_key' => null,

    // Brevo list ID that new contacts (from the contact form and kit
    // waitlists) get added to. Find it under Brevo → Kontakte → Listen.
    'brevo_list_id' => null,
];
