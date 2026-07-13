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
automatisches Anlegen des Kontakts, sortiert nach Herkunft, statt alles in
einem Topf) — mit nur **4 Brevo-Listen** plus **1 Attribut**, das den genauen
Grund trägt:
1. Kopiere `config.sample.php` zu `config.php` (liegt im selben Ordner,
   wird nicht mit eingecheckt/versioniert).
2. Trag deinen Brevo-API-Key (Brevo → SMTP & API → API Keys & MCP →
   "Generate a new API key") bei `brevo_api_key` ein.
3. Leg in Brevo (Contacts → Lists → Create a list) **4 Listen** an und
   trag die jeweilige Listen-ID (steht nach dem Anklicken der Liste in der
   Browser-Adresszeile, z.B. `.../lists/id/47`) bei `brevo_lists` ein:
   - `kontakt` — jede Kontaktformular-Anfrage
   - `programm` — Kontaktformular-Anfragen mit konkretem Programm-Kontext
     (zusätzlich zu `kontakt`, wenn die Anfrage über einen "Jetzt
     anmelden"/"Platz anfragen"-Button einer Programmseite kam)
   - `kit` — Kit-Warteliste-Anmeldungen ("Früher Zugang")
   - `kauf` — echte Käufe über Ablefy (siehe Punkt 2b)
4. **Einmalig** in Brevo das Attribut anlegen, das den genauen Grund trägt:
   Contacts → Settings (Zahnrad) → Contact attributes → "Add a new
   attribute" → Name **`INTERESSE`**, Type **Text**, Category
   **Normal attribute**. Ohne diesen Schritt schlägt der Brevo-Aufruf fehl
   (Brevo verlangt, dass Attribute vorher existieren).
5. Sobald `brevo_api_key` gesetzt ist, laufen Benachrichtigungsmails
   automatisch über die Brevo-API statt über `mail()`, und neue Kontakte
   landen in der passenden Liste mit `INTERESSE` z.B. auf
   `kit_vertrauen_warteliste`, `programm_veraenderung_anmeldung` oder
   `kauf_rollen` gesetzt — so lässt sich in Brevo trotz nur 4 Listen genau
   filtern/segmentieren, was jemand konkret getan hat.

Beide Formulare haben ein unsichtbares Honeypot-Feld gegen simple
Spam-Bots, serverseitige Validierung (Pflichtfelder, gültige E-Mail) und
zeigen bei einem Fehler eine Meldung mit direktem Mailto-Fallback an. Die
E-Mail-Benachrichtigung an dich enthält in jedem Fall auch Thema/Programm
im Klartext — auch ohne Brevo siehst du also immer, worum es ging.

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

### 2b. Ablefy-Webhook — Benachrichtigung bei echtem Kauf (bereits vorbereitet)

Ablefy selbst übernimmt Zahlung, Rechnung und Produktzustellung komplett —
aber ohne zusätzliche Anbindung erfährt unsere Website/Brevo davon nichts.
Dafür gibt es jetzt `ablefy-webhook.php`, den du in Ablefy als Webhook-Ziel
hinterlegst (Ablefy → Einstellungen → Webhooks/API → Webhook-URL). Wichtig:
Ablefys genaues Payload-Format konnte ich nicht einsehen (Doku hinter Login),
darum ist der Empfänger bewusst defensiv gebaut:

- Er schickt dir bei **jedem** eingehenden Webhook eine Mail mit einer
  Best-Effort-Zusammenfassung (Käufer:in, Produkt, Betrag) **und** dem
  kompletten Rohdaten-Payload als Anhang im Mailtext — falls die
  Best-Effort-Erkennung mal daneben liegt, siehst du trotzdem alles.
- Setup:
  1. In `config.php` einen beliebigen geheimen String bei
     `ablefy_webhook_token` festlegen.
  2. In Ablefy als Webhook-URL eintragen:
     `https://deine-domain.at/ablefy-webhook.php?token=DEIN_GEHEIMNIS`
     (verhindert, dass irgendwer sonst gefälschte "Kauf"-Meldungen schickt).
  3. Einen Test-Kauf/Test-Webhook in Ablefy auslösen — du bekommst die Mail
     mit dem Rohdaten-Payload. Sag mir kurz, wie die Produkt-ID/der
     Produktname darin aussieht, dann trage/trag ich sie in
     `ablefy_products` in `config.php` ein (z.B.
     `'prod_abc123' => 'vertrauen'`) — erst dann landet der Kauf zusätzlich
     zur Mail auch in der Brevo-Liste `kauf` mit `INTERESSE = kauf_vertrauen`.

### 2c. Kunden-Fulfillment-Mail — Dateien + Video + persönliches Miro-Board (bereits gebaut)

Sobald ein Kauf über `ablefy-webhook.php` reinkommt und einem Kit zugeordnet
werden kann (siehe `ablefy_products` oben), verschickt das Skript **direkt
an den Kunden** (nicht nur an dich) eine E-Mail mit:
- Facilitator Guide, Agenda und Präsentation als Dateianhang
- dem Begrüßungsvideo-Link (Vimeo)
- dem Link zu seinem persönlichen, frisch duplizierten Miro-Board

Du bekommst zusätzlich weiterhin deine eigene interne Benachrichtigung wie
bisher — mit einem Extra-Hinweis, falls beim Kunden-Fulfillment etwas
gefehlt hat (fehlende Datei, Miro-Fehler etc.), damit nichts unbemerkt
durchrutscht.

**Setup — 3 Teile:**

1. **Dateien hochladen.** Lad Facilitator Guide, Agenda und Präsentation
   pro Kit in `deliverables/<kit>/` hoch (genaue Dateinamen und Anleitung
   in `deliverables/README.md`) — per FTP/Datei-Manager, direkt neben
   `index.html`. Dieser Ordner ist per `.htaccess` vor Web-Zugriff
   geschützt, die Dateien werden nur serverseitig an die Mail angehängt,
   nie öffentlich verlinkt.

2. **Vimeo-Link eintragen.** In `config.php` bei `vimeo_welcome_url` den
   Link zu deinem Begrüßungsvideo eintragen (ein Link für alle Kits).

3. **Miro-API einrichten** (der aufwendigste Teil):
   - Miro → Avatar → Settings → "Your apps" → "Create new app" (z.B.
     "LEAP Fulfillment").
   - App-Einstellungen → Scopes `boards:read` und `boards:write` aktivieren.
   - Unten "Install app and get OAuth token" → "Install & authorize" →
     Token kopieren → in `config.php` bei `miro_api_token` eintragen.
   - Für jedes deiner 3 fertigen Vorlagen-Boards (die, die schon als
     Screenshots auf den Kit-Seiten zu sehen sind) die Board-ID aus der
     Miro-URL holen (`https://miro.com/app/board/BOARD_ID/` → der Teil
     zwischen `/board/` und dem abschließenden `/`) und in `config.php`
     bei `miro_templates.vertrauen` / `.rollen` / `.feedback` eintragen.

   **Wichtiger Vorbehalt:** Miros genaues API-Verhalten beim Duplizieren
   eines Boards und beim Einladen einer fremden E-Mail-Adresse als
   Editor (statt eines bereits bestehenden Team-Mitglieds) konnte ich
   nicht gegen einen echten Miro-Account testen — je nach deinem
   Miro-Plan (Free/Starter/Business/Enterprise) kann es sein, dass
   externe Personen automatisch nur Ansichts- statt Bearbeitungsrechte
   bekommen, oder dass das Einladen einen zusätzlichen zahlungspflichtigen
   Platz auf deinem Miro-Konto braucht. Der Code ist so gebaut, dass er
   in jedem Fall wenigstens den Board-Link mitschickt (auch wenn die
   Einladung fehlschlägt) und dir in der internen Benachrichtigung genau
   sagt, ob die Einladung geklappt hat — **am besten einmal einen echten
   Test-Kauf durchspielen**, dann schauen wir uns zusammen an, ob die
   Rechte beim Kunden ankommen oder ob wir nachjustieren müssen (z.B.
   Board stattdessen auf "jeder mit Link kann bearbeiten" stellen, falls
   dein Plan das für Personen außerhalb deines Teams erlaubt).

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
1. Brevo-Meetings-Buchungslink ✅ erledigt (meet.brevo.com/paul-scheipl)
2. 3× Ablefy-Checkout-Link (Vertrauen / Rollen / Feedback)
3. Ablefy-Webhook: nach dem ersten Test-Webhook das Rohdaten-Payload aus der
   Mail (für die Produkt-ID → Kit-Zuordnung in `ablefy_products`)
4. Kunden-Fulfillment: Dateien in `deliverables/<kit>/` hochladen, Vimeo-Link
   für `vimeo_welcome_url`, Miro-API-Token + 3 Vorlagen-Board-IDs
5. Brevo: ggf. Formular-Embed-URL + Liste-ID (Follow-up-Workflow richtest du direkt in Brevo ein)
6. Umami: Script-URL + Website-ID
