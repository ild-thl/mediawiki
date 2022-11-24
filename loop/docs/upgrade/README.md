# MediaWiki Upgrade
Wenn MediaWiki eine neue Version herausbringt, müssen unsere Änderungen übertragen werden. Unser Repository ist *kein* Fork! Das hängt damit zusammen, dass sich die Änderungen komplizierter einpflegen lassen.
https://github.com/oncampus/mediawiki

Beispiel für ein Update auf REL1_35:
1. Start in Kommandozeile in unserem Mediawiki Repo (NICHT im Produktivsystem!)
2. git remote -v
    - Ist das wikimedia Repository als remove vorhanden? Wenn nein:
    - git remote add wikimedia https://github.com/wikimedia/mediawiki.git
3. git fetch wikimedia
4. git checkout wikimedia/REL1_35
    - Warnung wegen detached HEAD state, ist aber ok
5. git checkout -b REL1_35
    - Der Stand von Wikimedia ist jetzt in unserem lokalen branch REL1_35
6. git push origin REL1_35
7. Unsere Code-Anpassungen einpflegen (Siehe Kapitel Patches)
    - Achtung: Sind unsere Anpassungen noch aktuell? Sind Funktionen ggf. deprecated? (Mehr Infos siehe Deprecation)
8. Code-Anpassungen auch für FlaggedRevs durchführen https://github.com/oncampus/mediawiki-extensions-FlaggedRevs
    - Dort haben wir ein einfaches Fork. Vom Prinzip können die Infos aber auf das Repository übertragen werden.
9. Extensions auf REL1_35 anheben
10. LOOP upgraden (eigenes Kapitel)
11. Mediawiki Updates durchführen (für die ganze Test-Farm - laufen sie durch?)
11. Einmal alles testen. Lässt es sich installieren? Laufen die Funktionen? 


# Patches
Zum Upgrade von Mediawiki auf die aktuellste Version benutzen wir Patches, um unsere Coreänderungen und -ergänzungen einzupflegen.

## Erstellen von Patch-Dateien
Eine Patchdatei wird erzeugt, indem man das git diff zwischen zwei Commits in eine Datei schreibt. Die Vorgenommenen Änderungen müssen also committed sein, bevor ein Patch erstellt werden kann. 
Info: Gelöschte Dateien und .gitignore (ggf. noch mehr) werden nicht in die Patches aufgenommen.

Beispiel: 
- Commit 123456 (letzte Mediawiki Änderung von Wikimedia)
- Commit 7890ab (Anpassung LOOP-Installer)
- Commit cdefgh (Anpassung LOOP-Farm)

- git diff 123456 cdefgh > patch_loop.txt
    - Der Befehl erzeugt ein diff zwischen den beiden Commits - der mittlere ist enthalten
- git show > patch_loopfarm.txt
    - Es wird nur der letzte Commit benutzt - nur die Änderungen zur LOOP-Farm sind enthalten

## Einspielen von Patch-Dateien
In einem neuen Repository muss die Patchdatei dort abgelegt werden, wo sie erstellt wurde - also im MW-Hauptordner. Die erstellten Patches befinden sich in diesem Branch unter /upgrades/patches.
- git apply patch.txt --3way --check
    - Mit --check testen wir zunächst nur. Ist alles ok, kann der Patch ausgeführt werden. Sonst muss doch manuell geändert werden, hierzu empfiehlt sich die diff-Ansicht auf GitHub. (z.B. https://github.com/oncampus/mediawiki/compare/2ccea98e4537ec7ababd7e8c00cffb9e1e5d5571...REL1_34 )
- git apply patch.txt --3way

Anschließend committen.


# LOOP Upgraden
Bevor der Code in LOOP angepasst wird, einmal in die Changelog(s) sehen. Was ist deprecated? Wurden Funktionen gelöscht? Manche IDEs bieten auch gute Extensions, die einem deprecated Funktionen gleich anzeigen. Auch wenn solche Funktionen manchmal noch jahrelang erhalten bleiben, ist es sinnvoll, sie so früh wie möglich zu ersetzen. 

Die betroffenen Repositories: 
- LOOP Extension https://github.com/oncampus/mediawiki-extensions-Loop
- LOOP Skin https://github.com/oncampus/mediawiki-skins-Loop
- LOOP Authenticator https://github.com/oncampus/mediawiki-extensions-LoopAuthenticator
- LOOP Custom Styles (Submodule vom Skin, benutzt LESS) https://github.com/oncampus/mediawiki-skins-loop-customstyles

Wenn die Änderungen abgeschlossen sind, nicht vergessen, die Dependencies in der extension/skin.json anzupassen.


# Produktivsystem Updaten (Draft)
Je nach Größe der Farm kann das durchaus einen Werktag in Anspruch nehmen, in der LOOP nicht erreichbar ist.

1. Backups von Datenbank und Webserver sicherstellen
2. MediaWiki in den Wartungsmodus setzen
3. MW, LOOP und andere Extensions per git auf die aktuelle Version anheben. composer install etc nicht vergessen (siehe update.sh - Versionsnummern müssen natürlich angepasst werden)
4. Updateskript über die Farm laufen lassen (kann sehr lange dauern - 0,5 bis 5 Min pro LOOP)
5. Log des Updates auf Fehler überprüfen
