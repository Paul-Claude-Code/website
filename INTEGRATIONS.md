# LEAP Website — Integrationen (Brevo, Zeitplanr, Ablefy, Umami)

Alles Design & Funktionen sind vorbereitet. Sobald du die untenstehenden
Angaben lieferst, trägst du sie in **`website/integrations.js`** ein — der
Rest läuft ohne weitere Code-Änderung.

## 0. Kontaktformular & Kit-Warteliste (E-Mail-Versand) — bereits aktiv

Das Kontaktformular (`kontakt.html`) und die "Früher Zugang"-Warteliste auf
den drei Kit-Seiten senden **ab sofort echte E-Mails an paul@ich-leaps.at**
— über zwei kleine PHP-Skripte (`send-contact.php`, `send-waitlist.php`),
die serverseitig per PHP `mail()` verschicken. Das funktioniert von Haus aus
auf World4You-Hosting, ganz ohne weitere Einrichtung.

Optional kannst du zusätzlich Brevo anschließen (bessere Zustellqualität +
automatisches Anlegen des Kontakts in einer Brevo-Liste):
1. Kopiere `config.sample.php` zu `config.php` (liegt im selben Ordner,
   wird nicht mit eingecheckt/versioniert).
2. Trag deinen Brevo-API-Key (Brevo → SMTP & API → API-Keys) bei
   `brevo_api_key` ein, und optional die Listen-ID bei `brevo_list_id`
   (Brevo → Kontakte → Listen).
3. Sobald `brevo_api_key` gesetzt ist, laufen Benachrichtigungsmails
   automatisch über die Brevo-API statt über `mail()`, und neue
   Kontakte werden in die angegebene Liste eingetragen.

Beide Formulare haben ein unsichtbares Honeypot-Feld gegen simple
Spam-Bots, serverseitige Validierung (Pflichtfelder, gültige E-Mail) und
zeigen bei einem Fehler eine Meldung mit direktem Mailto-Fallback an.

## 1. Brevo Meetings (Terminbuchung — "Gespräch buchen")
Was ich brauche:
- Link deiner Brevo-Meetings-Buchungsseite (Brevo → Meetings → Buchungsseite
  → Link kopieren, z.B. `https://meetings.brevo.com/paul/gespraech`)

Wie es funktioniert:
- Kontaktseite hat eine Karte "Termin direkt buchen" → öffnet ein Modal mit
  der Brevo-Meetings-Seite per iFrame.
- "Moderation anfragen" / "Coaching anfragen" auf den Kit-Seiten rufen
  dieselbe Funktion mit Kontext auf (`leapBookCall('moderation')` etc.).
- **Ohne Link hinterlegt:** Fällt automatisch aufs Kontaktformular zurück,
  Thema wird passend vorausgewählt. Kein Bruch in der UX.
- Eintragen in `integrations.js` → `brevo.meetingsUrl`.

Vorteil ggü. Drittanbieter: Brevo Meetings ist Teil des ohnehin genutzten
Brevo-Accounts (deutscher Server-Standort, ein AVV für alles) — und da
Buchung + Follow-up-Mail im selben System laufen, kann der Follow-up-Workflow
direkt in Brevo an das Meetings-Event geknüpft werden, ganz ohne Zapier/Webhook
(siehe Punkt 3).

## 2. Ablefy (Zahlungen — Workshop-Kit-Checkout)
Was ich brauche, pro Kit:
- Checkout-Link aus Ablefy (Produkt anlegen → "Verkaufen" → Link kopieren)
  für: Vertrauen aufbauen (€390), Rollen & Verantwortung (€290), Feedback (€290)

Wie es funktioniert:
- Alle Kauf-Buttons auf den drei Kit-Seiten rufen `leapBuy('vertrauen'|'rollen'|'feedback')`
  auf und leiten direkt zum Ablefy-Checkout.
- **Ohne Link hinterlegt:** Fällt automatisch auf das bestehende
  "Früher Zugang"-Warteliste-Modal zurück.
- Eintragen in `integrations.js` → `ablefy.products.<kit>`.

Die Buttons "Moderation anfragen" / "Coaching anfragen" bleiben bewusst
außerhalb von Ablefy — die laufen über Zeitplanr, weil dort erst ein Gespräch
nötig ist, bevor bezahlt wird. Sag Bescheid, falls Moderation/Coaching auch
direkt bezahlbar werden sollen.

## 3. Brevo (E-Mail — automatisierte Follow-ups nach Buchung)
Was ich brauche:
- Falls du ein Brevo-"Web Form" für Newsletter/Kontakt nutzen willst: die
  Embed-URL aus Brevo → Kontakte → Formulare → Teilen.
- Die Liste-ID, der neue Kontakte zugeordnet werden sollen.

Da die Terminbuchung jetzt auch über Brevo Meetings läuft, ist der
Follow-up-Workflow reine Brevo-interne Konfiguration (keine externe
Automatisierung/Webhook nötig): in Brevo unter Automation einen Workflow
anlegen, der auf das Meetings-Event "Termin gebucht" reagiert und die
Follow-up-Mail verschickt. Das richtest du direkt im Brevo-Dashboard ein —
sag Bescheid, falls du dabei Unterstützung willst.

## 4. Umami (Analytics — später)
Was ich brauche, sobald es soweit ist:
- Script-URL deiner Umami-Instanz (z.B. `https://umami.deine-domain.at/script.js`)
- Website-ID

Eintragen in `integrations.js` → `umami.scriptUrl`, `umami.websiteId`,
`umami.enabled: true`. Das Skript wird erst dann überhaupt geladen — bis
dahin passiert nichts, kein Consent-Banner nötig (wolltet ihr separat klären).

---

### Kurzfassung — was du mir schicken kannst, wenn bereit:
1. Brevo-Meetings-Buchungslink
2. 3× Ablefy-Checkout-Link (Vertrauen / Rollen / Feedback)
3. Brevo: ggf. Formular-Embed-URL + Liste-ID (Follow-up-Workflow richtest du direkt in Brevo ein)
4. Umami: Script-URL + Website-ID
