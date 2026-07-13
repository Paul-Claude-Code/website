# Laufzeitdaten

`miro-boards.json` wird automatisch von `ablefy-webhook.php` befüllt und
von `leap-miro-expire.php` gepflegt (Liste erstellter Kunden-Boards mit
Ablaufdatum für den öffentlichen Zugriff). Enthält Kunden-E-Mail-Adressen
— per `.htaccess` vor Web-Zugriff geschützt und bewusst nicht in Git
versioniert (siehe `.gitignore`).
