# Lokale Installation (Beispiel Windows)

Voraussetzungen: 
- XAMPP mit PHP 7.4
- Composer 
- nodeJS/npm 

1. Ordner anlegen in xampp/htdocs, z.B. "mediawiki" und das [Installationsskript](install_mw_loop_1_35_dev.sh) von dort aus ausführen. "bash /path/to/script.sh"
2. http://localhost/mediawiki/ aufrufen, Setup starten
3. Wizard: 
	- weiter
	- weiter
	- ggf. anpassen und weiter
	- weiter
	- ausfüllen; keine daten an MW-entwickler; Ja zu weiteren Konfigurationseinstellungen 
4. Wizard Seite Optionen: 
	- offenes Wiki 
	- Emails können deaktiviert werden
	- Loop als Standardoberfläche aktivieren (extra Radio-Button!)
	- alle Erweiterungen aktivieren
	- Hochladen von Dateien ermöglichen
	- kein Objektcaching nötig
	- weiter
	- weiter
	- Installation sollte abgeschlossen sein; weiter
5. Heruntergeladene LocalSettings.php nach /path/to/mediawiki/LocalSettings.php verschieben
6. LocalSettings unten ergänzen um Inhalt aus [LocalSettings_local.txt](LocalSettings_local.txt); Dev-Settings Kommentar bei Bedarf entfernen (ganz unten)
7. http://localhost/mediawiki/ aufrufen und einloggen

Manche Extensions brauchen weitere Konfiguration, z.B. SyntaxHighlight und Math. Das ist für Entwicklung an LOOP aber nicht vorrangig.
