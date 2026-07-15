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

### 2b. Ablefy-Webhook — Benachrichtigung bei echtem Kauf (bereits fertig)

Ablefy selbst übernimmt Zahlung, Rechnung und Produktzustellung komplett —
aber ohne zusätzliche Anbindung erfährt unsere Website/Brevo davon nichts.
Dafür gibt es `ablefy-webhook.php`, den du in Ablefy als Webhook-Ziel
hinterlegst (Ablefy → Market & Sell → Webhook → Create). Als auslösendes
Event **"Betrag wurde vollständig gezahlt"** auswählen (nicht "alle
Events" — sonst könnten auch Rückerstattungen o.ä. versehentlich eine
Kunden-Mail auslösen).

Die Feldnamen im Payload (E-Mail, Vorname, Produkt-Slug etc.) sind jetzt
anhand von Ablefys offizieller Webhook-Doku fest verdrahtet — die
Produkt-Zuordnung in `ablefy_products` ist bereits mit den echten Slugs
deiner drei Kits vorausgefüllt, ein Testkauf ist dafür **nicht mehr nötig**.
Trotzdem schickt dir das Skript bei jedem Webhook zusätzlich das komplette
Rohdaten-Payload mit — falls Ablefy doch mal ein Feld anders benennt als
dokumentiert, siehst du das sofort und wir passen nach.

- Setup:
  1. In `config.php` einen beliebigen geheimen String bei
     `ablefy_webhook_token` festlegen.
  2. In Ablefy als Webhook-URL eintragen:
     `https://deine-domain.at/ablefy-webhook.php?token=DEIN_GEHEIMNIS`
     (verhindert, dass irgendwer sonst gefälschte "Kauf"-Meldungen schickt).
  3. Am Produkt selbst unter "Weiteres" den Webhook aktivieren.
  4. Sobald der erste echte Kauf durchläuft: kurz die interne
     Benachrichtigungs-Mail gegenchecken, ob "Zugeordnetes Kit" korrekt
     erkannt wurde.

### 2c. Kunden-Fulfillment-Mail — Dateien + Video + persönliches Miro-Board (bereits gebaut)

Sobald ein Kauf über `ablefy-webhook.php` reinkommt und einem Kit zugeordnet
werden kann (siehe `ablefy_products` oben), verschickt das Skript **direkt
an den Kunden** (nicht nur an dich) eine E-Mail mit:
- Facilitator Guide, Agenda und Präsentation als Dateianhang
- dem Begrüßungsvideo-Link (Vimeo)
- dem Link zu seinem persönlichen, frisch duplizierten Miro-Board — benannt
  "Vorname LEAP Workshop Kit <Kit>" (z.B. "Erika LEAP Workshop Kit Vertrauen"),
  öffentlich per Link bearbeitbar (kein Miro-Account nötig für Kunde oder
  Team), aber nur 30 Tage lang (siehe Punkt 3 unten) — bewusst **kein
  Passwort**: Miro erlaubt das Setzen eines Board-Passworts nicht über die
  API, nur manuell im Miro-Webinterface, was die volle Automatisierung
  gebrochen hätte. Die zeitliche Begrenzung ist der Ausgleich dafür.

Du bekommst zusätzlich weiterhin deine eigene interne Benachrichtigung wie
bisher — mit einem Extra-Hinweis, falls beim Kunden-Fulfillment etwas
gefehlt hat (fehlende Datei, Miro-Fehler etc.), damit nichts unbemerkt
durchrutscht.

**Setup — 4 Teile:**

1. **Dateien hochladen.** Lad Facilitator Guide, Agenda und Präsentation
   pro Kit in `deliverables/<kit>/` hoch (genaue Dateinamen und Anleitung
   in `deliverables/README.md`) — per FTP/Datei-Manager, direkt neben
   `index.html`. Dieser Ordner ist per `.htaccess` vor Web-Zugriff
   geschützt, die Dateien werden nur serverseitig an die Mail angehängt,
   nie öffentlich verlinkt.

2. **Vimeo-Link eintragen.** In `config.php` bei `vimeo_welcome_url` den
   Link zu deinem Begrüßungsvideo eintragen (ein Link für alle Kits).

3. **Miro-API einrichten:**
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
   - Optional: `miro_board_lifetime_days` in `config.php` anpassen (Standard
     30 Tage).

   **Wichtiger Vorbehalt:** Miros genaues API-Verhalten beim Duplizieren
   eines Boards mit `sharingPolicy.access: "edit"` (öffentlicher
   Bearbeiten-Link ohne Account) konnte ich nicht gegen einen echten
   Miro-Account testen — in Miros eigenem Community-Forum gibt es
   Hinweise, dass dieses Feld über die API teils inkonsistent
   funktioniert (als Beta markiert). Der Code schickt in jedem Fall den
   Board-Link mit und meldet dir in der internen Benachrichtigung, falls
   das Duplizieren fehlschlägt — **am besten einmal einen echten Test-Kauf
   durchspielen** und prüfen, ob der Link beim Kunden wirklich ohne
   Anmeldung bearbeitbar ist.

4. **Cronjob für den Board-Ablauf einrichten.** `leap-miro-expire.php`
   entzieht nach Ablauf von `miro_board_lifetime_days` den öffentlichen
   Link wieder (Board selbst bleibt erhalten). Muss regelmäßig laufen
   (z.B. täglich) — im World4You-Kundenmenü nach "Cronjob" suchen, dann
   je nach Angebot:
   - PHP-Datei direkt ausführen lassen: `leap-miro-expire.php`, **oder**
   - falls nur URL-Abruf angeboten wird: geheimen Token bei
     `miro_cron_token` in `config.php` festlegen, dann als Cron-URL
     `https://ich-leaps.at/leap-miro-expire.php?token=DEIN_TOKEN` eintragen.
   Ohne eingerichteten Cronjob bleiben die Boards einfach dauerhaft
   öffentlich erreichbar — funktional kein Problem, nur ohne die
   gewünschte zeitliche Begrenzung.

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
2. 3× Ablefy-Checkout-Link (Vertrauen / Rollen / Feedback) ✅ erledigt
3. Ablefy-Webhook: Produkt-Zuordnung ✅ bereits vorausgefüllt (echte Slugs) —
   nach dem ersten echten Kauf trotzdem kurz die Benachrichtigungs-Mail
   gegenchecken
4. Kunden-Fulfillment: Dateien in `deliverables/<kit>/` hochladen, Vimeo-Link
   für `vimeo_welcome_url`, Miro-API-Token + 3 Vorlagen-Board-IDs ✅ erledigt,
   Cronjob für `leap-miro-expire.php` einrichten (oder manuell per Browser
   aufrufen, siehe Punkt 2c)
5. Brevo: ggf. Formular-Embed-URL + Liste-ID (Follow-up-Workflow richtest du direkt in Brevo ein)
6. Umami: Script-URL + Website-ID
