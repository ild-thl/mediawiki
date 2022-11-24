# LOOP-Farm Installation

Voraussetzungen: 

https://www.mediawiki.org/wiki/Manual:Installation_requirements

Diese Anleitung bezieht sich auf MediaWiki 1.35.
- PHP 7.4+
- Composer 
- nodeJS/npm 
- MariaDB oder MySQL

1. Server für Farm Konfigurieren
	- Der Webserver muss so konfiguriert sein, dass alle Subdomains auf den mediawiki Ordner zeigen. Eine Installation kann so viele verschiedene LOOPs unter Subdomains betreiben. [Auszüge der httpd Konfiguration](loop_httpd.md) 

	Ein Alias für .../mediawiki/index.php ermöglicht kurze Artikel URLs (konfiguriert in $wgArticlePath) wie z.B. https://{fqdn}/loop/Startseite anstatt https://{fqdn}/mediawiki/index.php/Startseite. Die Dateien unter /mediawiki müssen unter {fqdn}/mediawiki erreichbar sein. 

2. Skript ausführen
	- Ordner "mediawiki" im web root anlegen und dort das [Installationsskript](install_mw_loop_1_35_prod.sh) ausführen. `bash /path/to/script.sh`

3. MW-Installationsskript ausführen
	- MediaWiki über CLI installiert. Das Skript legt eine Datenbank und einen Images Ordner an. Siehe Dokumentation https://www.mediawiki.org/wiki/Manual:Install.php 

	Hier wird das "Haupt-LOOP" angelegt, hier am Beispiel loop.example.de. 
	
	3.1 Beispiel vom /mediawiki Ordner aus: 
	`php maintenance/install.php --dbname=loop.example.de --dbserver="localhost" --installdbuser=root --installdbpass=rootpassword --dbuser=grabber --dbpass=grabber --server="https://loop.example.de" --scriptpath="/mediawiki" --lang=de --pass=min10characters --skins=Loop --with-extensions "LOOP" "Admin"`
	
	Nach der Installation befindet sich LocalSettings.php im MediaWiki-Root Ordner. 
	
	3.2 Ein weiteres Datenbank-Update durchführen: `php maintenance/update.php --quick`

4. Haupt-LocalSettings Datei anpassen.
	- Inhalt von [LocalSettings.md](LocalSettings_prod.md) ans Ende der Datei /mediawiki/LocalSettings.php anfügen. 
	Wichtig: Diese Variable anpassen: `$wgScriptPath = "/mediawiki";` (Übereinstimmend mit dem mediawiki-Alias in 1.)

	Ggf. weitere Anpassung nach Bedarf, siehe Dokumentation https://www.mediawiki.org/wiki/Manual:LocalSettings.php/de 

5. Einzelne Farm-LocalSettings anlegen
	- Jedes LOOP unter benötigt eine eigene Konfigurationsdatei. Die Dateien werden in /mediawiki/LocalSettings/ abgelegt. 
	Namensschema: LocalSettings_{fqdn}.php, also z.B. LocalSettings_loop.example.de.php
	- Inhalt von [LocalSettings_farm.md](LocalSettings_single.md) einfügen und Variablen füllen.
	- Die Datei kann (bis auf <?php) leer gelassen werden, um die Konfiguration der Haupt-LocalSettings zu übernehmen. Sie muss aber vorhanden sein.
	- Hier kann auch weitere Konfiguration (z.B. Sprache) eingefügt werden, die nicht für alle LOOPs gelten soll.

6. Testen
	- Wenn die Haupt-LocalSettings und die Farm-LocalSettings angelegt sind und alles stimmt, ist loop.example.de nun erreichbar.
	- Bei der Installation wurde ein User "Admin" angelegt mit dem in Schritt 3.1 angepassten Passwort. 

7. Import von LOOPs
	Soll ein LOOP von einer anderen Instanz importiert werden, muss die Datenbank und der Images-Ordner übertragen werden. 

	7.1 Datenbank importieren

	7.2 Images Ordner in /mediawiki/images/loop.example.de kopieren

	7.3 Farm-LocalSettings anlegen mit Datenbankname und Images-Ordner

	7.4 Ggf. Passwort des Admin Users ändern https://www.mediawiki.org/wiki/Manual:ChangePassword.php 

# Empfehlungen zur Namensgebung
In einer MW-Instanz können viele LOOPs entstehen. Es ist sinnvoll, Datenbanken und Images-Ordner stets nach der fqdn des LOOPs zu benennen, um keine Verwechslung zu riskieren. 

# Maintenance
Alle Mainenance-Skripte benötigen den Parameter --wiki um zu funktionieren. z.B. `php maintenance/update.php --wiki=loop.example.de`

# Weitere Software
Die Folgenden Extensions benötigen weitere installierte Software, werden jedoch nicht für jeden Inhalt benötigt. Bitte die Informationen und Hinweise in den jeweiligen Dokumentationen entnehmen:
- Math: https://www.mediawiki.org/wiki/Extension:Math/de
- SyntaxHighlight_GeSHi: https://www.mediawiki.org/wiki/Extension:SyntaxHighlight
- Score: https://www.mediawiki.org/wiki/Extension:Score
Nach der Installation ggf. erneut npm install und composer install in den jeweiligen Extension-Ordnern ausführen.


# Troubleshooting
- Die Konfigurationsdatei konnte nicht gefunden werden.
	- Die LocalSettings Datei fehlt. Siehe Schritt 4. und 5. 
- 404 Not Found bei URL wie http://localhost/loop/Erstes_Unterkapitel
	- Lösung: `$wgArticlePath` in LocalSettings auskommentieren
- Login-Seite: Fataler Ausnahmefehler des Typs „Error“
	- Lösung: `wfLoadExtension('ConfirmEdit/ReCaptchaNoCaptcha');` in LocalSettings einfügen
- Bei Updates gibt es Fehler im Lingo Extension Ordner
	- Lösung: Lingo auf branch REL1_36 updaten (Fehler in MW 1.35)
- Im Skin fehlt ein Submodule
	- Das Submodule ist nicht für den Betrieb notwendig und kann ignoriert werden

