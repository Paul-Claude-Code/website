# Kit-Dateien für den automatischen Kunden-Versand

Lad hier die echten Dateien hoch, die ein Kunde nach dem Kauf per E-Mail
bekommt (Facilitator Guide, Agenda, Präsentation) — `ablefy-webhook.php`
hängt sie automatisch an die Kunden-Mail an, sobald ein Kauf über den
Ablefy-Webhook gemeldet wird. Dieser Ordner ist per `.htaccess` vor
direktem Web-Zugriff geschützt; die Dateien werden nie öffentlich verlinkt.

## Wohin genau

Pro Kit ein Unterordner. Das Skript erkennt sowohl das `LEAP_<Typ>_<Kit>`-
Namensschema (so wie die Dateien tatsächlich abgelegt sind) als auch die
generischen Kurznamen als Fallback — Endung `.pdf`, `.pptx`, `.ppt`,
`.docx` oder `.zip`, das Skript findet sie automatisch:

```
deliverables/
  vertrauen/
    LEAP_FacilitatorGuide_Vertrauen.pdf
    LEAP_Agenda_Vertrauen.pdf
    LEAP_Präsentation_Vertrauen.pdf (oder .pptx)
    LEAP_E-Mail_Vorlagen_Vertrauen.pdf  (oder LEAP_EmailVorlagen_Vertrauen)
  rollen/
    LEAP_FacilitatorGuide_Rollen.pdf
    LEAP_Agenda_Rollen.pdf
    LEAP_Präsentation_Rollen.pdf
    LEAP_EmailVorlagen_Rollen.pdf
  feedback/
    LEAP_FacilitatorGuide_Feedback.pdf
    LEAP_Agenda_Feedback.pdf
    LEAP_Präsentation_Feedback.pdf
    LEAP_EmailVorlagen_Feedback.pdf
```

Fehlt eine Datei, wird die Kunden-Mail trotzdem verschickt (nur ohne diesen
Anhang) — du bekommst in deiner eigenen Benachrichtigungs-Mail einen
Hinweis, welche Datei für welches Kit noch fehlt.

## Upload

Per FTP/Datei-Manager direkt in diesen Ordner auf dem World4You-Server,
neben `index.html`.
