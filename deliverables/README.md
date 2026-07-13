# Kit-Dateien für den automatischen Kunden-Versand

Lad hier die echten Dateien hoch, die ein Kunde nach dem Kauf per E-Mail
bekommt (Facilitator Guide, Agenda, Präsentation) — `ablefy-webhook.php`
hängt sie automatisch an die Kunden-Mail an, sobald ein Kauf über den
Ablefy-Webhook gemeldet wird. Dieser Ordner ist per `.htaccess` vor
direktem Web-Zugriff geschützt; die Dateien werden nie öffentlich verlinkt.

## Wohin genau

Pro Kit ein Unterordner, mit **genau diesen Dateinamen** (Endung `.pdf`,
`.pptx`, `.ppt`, `.docx` oder `.zip` — such dir eine passende Endung aus,
das Skript findet sie automatisch):

```
deliverables/
  vertrauen/
    facilitator-guide.pdf
    agenda.pdf
    praesentation.pdf   (oder .pptx)
  rollen/
    facilitator-guide.pdf
    agenda.pdf
    praesentation.pdf
  feedback/
    facilitator-guide.pdf
    agenda.pdf
    praesentation.pdf
```

Fehlt eine Datei, wird die Kunden-Mail trotzdem verschickt (nur ohne diesen
Anhang) — du bekommst in deiner eigenen Benachrichtigungs-Mail einen
Hinweis, welche Datei für welches Kit noch fehlt.

## Upload

Per FTP/Datei-Manager direkt in diesen Ordner auf dem World4You-Server,
neben `index.html`.
