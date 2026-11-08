# Brevo einrichten: Team-Check auf Englisch

**Stand 6.11.2026: Schritte 1 bis 3 sind in Brevo erledigt.** Liste „Team-Check EN“ hat ID **10**, die englische Double-Opt-in-Vorlage „Team-Check EN – Double opt-in“ hat ID **13** (aktiv, Tag `optin`), Automation „Team-Check EN“ (#2) ist aktiv mit Mail 1 sofort, 7 Tage warten, Mail 2. Offen ist nur noch Schritt 4 (IDs in config.php eintragen) und der Test in Schritt 5.

Die englische Seite (ich-leaps.at/en/team-check) schickt das Ergebnis über dieselbe Brevo-Anbindung wie die deutsche. Damit die Mails auf Englisch rausgehen, braucht es in Brevo drei eigene Dinge: eine Liste, eine englische Bestätigungsmail und eine eigene Automation. Die Kontaktfelder (TC_…) sind dieselben wie beim deutschen Check und existieren schon. Die Website füllt sie bei englischen Kontakten mit englischen Texten.

Solange Schritt 4 nicht erledigt ist, sagt das Formular auf der englischen Seite höflich, dass der Mailversand noch nicht eingerichtet ist, und verweist auf den PDF-Button. Es geht also nichts kaputt.

## 1. Liste anlegen

Kontakte → Listen → „Liste erstellen“, Name z. B. **Team-Check EN**. Die **ID** der neuen Liste notieren (steht in der Listenübersicht).

## 2. Englische Bestätigungsmail (Double-Opt-in)

Kampagnen → Vorlagen → die deutsche Vorlage #1 „Default Template Double opt-in confirmation“ **kopieren** und die Kopie umbenennen in **Team-Check EN – Double opt-in**. Wichtig: Der Tag `optin` muss an der Kopie dranbleiben, sonst lehnt Brevo sie ab.

Texte ersetzen:

Betreff: **Please confirm: your Team Check result**

    Hi {{ contact.FIRSTNAME }},

    one click and your Team Check result is on its way.

    [Button „Confirm and get my result“ mit dem Bestätigungslink {{ doubleoptin }}]

    If you didn't request this, just ignore this email and nothing will happen.

    Paul from LEAP

Die Vorlage aktivieren und ihre **ID** notieren.

## 3. Automation anlegen

Automations → neue Automation, z. B. **Team-Check EN**:

1. Auslöser: „Kontakt wird zu einer Liste hinzugefügt“, Liste **Team-Check EN**
2. Mail 1 senden, ohne Wartezeit
3. Warten: 7 Tage
4. Mail 2 senden

Absender und Antwortadresse: paul@ich-leaps.at.

### Mail 1, sofort

Betreff: **Your Team Check result**

    Hi {{ contact.FIRSTNAME }},

    here's your result from the Team Check:

    "{{ contact.TC_SATZ }}"

    Roles & Responsibility: {{ contact.TC_ROLLEN }}/15
    Feedback: {{ contact.TC_FEEDBACK }}/15
    Trust & Psychological Safety: {{ contact.TC_VERTRAUEN }}/15
    Communication & Clarity: {{ contact.TC_KOMMUNIKATION }}/15

    Your biggest lever: {{ contact.TC_BEREICH }}.

    One exercise for this week: {{ contact.TC_UEBUNG_TITEL }}
    {{ contact.TC_UEBUNG }}

    [Button „View my result and save it as a PDF“ mit Link {{ contact.TC_LINK }}]

    You can use the same link to create a team link. Your team then fills in the check anonymously, and together you see how your team ticks on average.

    Paul

### Mail 2, nach 7 Tagen

Betreff: **So, how did it go?**

    Hi {{ contact.FIRSTNAME }},

    a week ago you took the Team Check, and {{ contact.TC_BEREICH }} came out as your team's biggest lever.

    I'm genuinely curious how things went from there. Did you try the exercise? And what would you like to tackle with your team?

    Just reply to this email, one sentence is enough. I read every reply myself.

    And if you feel it needs more than an exercise, let's talk for 20 minutes. Free, and no sales pitch.

    Paul

## 4. IDs auf dem Server eintragen

In der `config.php` auf dem Server (per FTP, liegt neben `team-check-mail.php`) zwei Zeilen ergänzen, mit den IDs aus Schritt 1 und 2:

    'teamcheck_en_list_id' => 10,
    'teamcheck_en_doi_template_id' => 13,

Die Zeilen gehören in das große Array, z. B. direkt unter `'teamcheck_doi_template_id'`.

## 5. Testen

Auf ich-leaps.at/en/team-check mit einer neuen Adresse (z. B. paul+en1@ich-leaps.at) den Check machen, Ergebnis per Mail anfordern, bestätigen. Danach muss die englische Danke-Seite kommen (/en/team-check-thanks.html) und kurz darauf Mail 1 auf Englisch. Der Link in der Mail öffnet das Ergebnis auf der englischen Seite.

Wenn das Formular einen Fehler meldet, steht der Grund in `data/team-check-mail-fehler.log` auf dem Server, englische Anfragen sind dort mit `[EN]` markiert.
