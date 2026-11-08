# Kit-Dateien für den automatischen Kunden-Versand

Lad hier die echten Dateien hoch, die ein Kunde nach dem Kauf per E-Mail
bekommt. `ablefy-webhook.php` hängt sie automatisch an die Kunden-Mail an,
sobald ein Kauf über den Ablefy-Webhook gemeldet wird. Dieser Ordner ist per
`.htaccess` vor direktem Web-Zugriff geschützt, die Dateien werden nie
öffentlich verlinkt.

Wichtig: Die Kit-Dateien gehören nur auf den Server, nicht ins Git-Repo.
Das Repo ist öffentlich.

## Wohin genau

Pro Kit ein Unterordner. Jedes Kit enthält alle Unterlagen auf Deutsch und
Englisch, also acht Dateien pro Ordner:

```
deliverables/
  vertrauen/
    LEAP_FacilitatorGuide_Vertrauen_DE.pdf
    LEAP_Agenda_Vertrauen_DE.pdf
    LEAP_Praesentation_Vertrauen_DE.pptx
    LEAP_E-Mail_Vorlagen_Vertrauen_DE.pdf
    LEAP_FacilitatorGuide_Trust_EN.pdf
    LEAP_Agenda_Trust_EN.pdf
    LEAP_Presentation_Trust_EN.pptx
    LEAP_Email_Templates_Trust_EN.pdf
  feedback/
    LEAP_FacilitatorGuide_Feedback_DE.pdf
    LEAP_Agenda_Feedback_DE.pdf
    LEAP_Praesentation_Feedback_DE.pptx
    LEAP_E-Mail_Vorlagen_Feedback_DE.pdf
    LEAP_FacilitatorGuide_Feedback_EN.pdf
    LEAP_Agenda_Feedback_EN.pdf
    LEAP_Presentation_Feedback_EN.pptx
    LEAP_Email_Templates_Feedback_EN.pdf
  rollen/
    LEAP_FacilitatorGuide_Rollen_DE.pdf
    LEAP_Agenda_Rollen_DE.pdf
    LEAP_Praesentation_Rollen_DE.pptx
    LEAP_E-Mail_Vorlagen_Rollen_DE.pdf
    LEAP_FacilitatorGuide_Roles_EN.pdf
    LEAP_Agenda_Roles_EN.pdf
    LEAP_Presentation_Roles_EN.pptx
    LEAP_Email_Templates_Roles_EN.pdf
```

Die Dateien liegen auf Pauls Laptop unter `Desktop\LEAP\KIT …\FINAL\DE` und
`…\FINAL\EN` und können 1:1 so hochgeladen werden. Endung `.pdf`, `.pptx`,
`.ppt`, `.docx` oder `.zip`, das Skript findet sie automatisch.

Fehlt eine `_DE`-Datei, nimmt das Skript die alten Namen ohne Endung
(`LEAP_FacilitatorGuide_Vertrauen.pdf` usw.). Die alten Dateien also erst
löschen, wenn die neuen oben sind. Fehlt eine Datei ganz, wird die
Kunden-Mail trotzdem verschickt (nur ohne diesen Anhang), und in deiner
eigenen Benachrichtigungs-Mail steht, welche Datei für welches Kit fehlt.

## Upload

Per FTP/Datei-Manager direkt in diesen Ordner auf dem World4You-Server,
neben `index.html`.
