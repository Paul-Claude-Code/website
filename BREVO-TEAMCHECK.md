# Brevo einrichten: Team-Check-Ergebnis per Mail

Stand 2.11.2026: Schritte 1 bis 5 sind in Brevo erledigt (Felder angelegt, Vorlage #1 mit Tag „optin“, Automatisierung #1 mit Mail 1, 7 Tage Wartezeit, Mail 2). Offen: die alten Mails 3 und 4 am Ende der Automation entfernen und testen.

Die Website legt Kontakte über die Brevo-API mit Double-Opt-in in Liste 9 an und speichert das Ergebnis in Kontaktfeldern. Brevo verschickt daraus zwei Mails: das Ergebnis sofort nach der Bestätigung und eine Nachfrage nach 7 Tagen.

## 1. Kontaktfelder anlegen

Kontakte → Einstellungen (Zahnrad) → Kontaktattribute → „Neues Attribut“. Namen genau so schreiben:

| Name | Typ |
| --- | --- |
| TC_TYP | Text |
| TC_SATZ | Text |
| TC_BEREICH | Text |
| TC_UEBUNG_TITEL | Text |
| TC_UEBUNG | Text |
| TC_LINK | Text |
| TC_DATUM | Datum |
| TC_ROLLEN | Zahl |
| TC_FEEDBACK | Zahl |
| TC_VERTRAUEN | Zahl |
| TC_KOMMUNIKATION | Zahl |

FIRSTNAME gibt es schon.

## 2. Double-Opt-in-Vorlage

Verwendet wird „Default Template Double opt-in confirmation“ (#1) mit dem Tag `optin`. Die ID ist in team-check-mail.php eingetragen. Wer eine andere Vorlage will, setzt in config.php `'teamcheck_doi_template_id' => <ID>`.

## 3. Automation umbauen

Automations → die bisherige Team-Check-Strecke öffnen (oder neu anlegen):

1. Auslöser: „Kontakt wird zu einer Liste hinzugefügt“, Liste 9
2. Mail 1 senden, ohne Wartezeit
3. Warten: 7 Tage
4. Mail 2 senden

Absender und Antwortadresse: paul@ich-leaps.at. Die alten Mails aus der Strecke entfernen.

## 4. Mail 1, sofort

Betreff: Dein Team-Check-Ergebnis

    Hallo {{ contact.FIRSTNAME }},

    hier ist dein Ergebnis aus dem Team-Check:

    „{{ contact.TC_SATZ }}“

    Rollen & Verantwortung: {{ contact.TC_ROLLEN }}/15
    Feedback: {{ contact.TC_FEEDBACK }}/15
    Vertrauen & psychologische Sicherheit: {{ contact.TC_VERTRAUEN }}/15
    Kommunikation & Klarheit: {{ contact.TC_KOMMUNIKATION }}/15

    Euer größter Hebel liegt bei {{ contact.TC_BEREICH }}.

    Eine Übung für diese Woche: {{ contact.TC_UEBUNG_TITEL }}
    {{ contact.TC_UEBUNG }}

    [Button „Ergebnis ansehen und als PDF speichern“ mit Link {{ contact.TC_LINK }}]

    Über denselben Link kannst du auch einen Team-Link erstellen. Dann füllt dein Team anonym aus, und ihr seht gemeinsam, wie euer Team im Durchschnitt tickt.

    Paul

## 5. Mail 2, nach 7 Tagen

Betreff: Und, wie war's?

    Hallo {{ contact.FIRSTNAME }},

    vor einer Woche hast du den Team-Check gemacht, und bei euch kam {{ contact.TC_BEREICH }} als größter Hebel raus.

    Mich interessiert ehrlich, wie es weitergegangen ist. Hast du die Übung ausprobiert? Und was davon willst du mit deinem Team angehen?

    Antworte einfach kurz auf diese Mail, ein Satz reicht. Ich lese jede Antwort selbst.

    Und wenn du merkst, dass es mehr braucht als eine Übung, telefonieren wir gern 20 Minuten. Kostenlos und ohne Verkaufsgespräch.

    Paul

## 6. Testen

Mit einer neuen Adresse (z. B. paul+test10@ich-leaps.at) den Check machen, Ergebnis per Mail anfordern, bestätigen. Danach muss die Danke-Seite kommen und kurz darauf Mail 1.

Wenn das Formular einen Fehler meldet, steht der Grund in `data/team-check-mail-fehler.log` auf dem Server (per FTP abrufbar).
