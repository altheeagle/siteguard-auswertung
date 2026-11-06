# SiteGuard-Auswertung

Eine Zero Conf Webanwendung, die SiteGuard-Sicherheitsmeldungen aus einem E-Mail-Postfach übersichtlich aufbereitet.

## Warum dieses Projekt?

SiteGuard-Meldungen enthalten oft viele einzelne Dateioperationen. Als lange E-Mail sind diese schwer zu überblicken und zu vergleichen. Diese Anwendung stellt die Meldungen deshalb strukturiert dar: Vorgänge werden nach Datei gruppiert und lassen sich samt Zeitstempel und Details prüfen.

## So funktioniert es

1. Mit den Zugangsdaten des Postfachs anmelden, in dem die SiteGuard-Meldungen eingehen. Die Konfigurationsdaten werden automatisch ermittelt. Falls die Konfiguration nicht ermittelt werden kann besteht die Möglichkeit die Daten entweder in der Datei `/config.php` zu hinterlegen, oder bei der Anmeldung direkt mit einzugeben.
2. Die Anwendung verbindet sich per IMAP mit dem konfigurierten Postfach und zeigt passende Meldungen aus dem Posteingang an.
3. Beim Öffnen einer Meldung werden deren protokollierte Dateioperationen geladen, gruppiert und übersichtlich angezeigt.
4. Nach der Prüfung kann die Meldung in den Ordner `SiteGuard` verschoben werden. Fehlt der Ordner, wird er beim Anmelden erstellt.

Der Ordnername und die Absenderadresse der Meldungen lassen sich in `config.php` anpassen.

## Datenschutz und Zugangsdaten

Die Zugangsdaten werden **nicht dauerhaft** in einer Datenbank oder Konfigurationsdatei gespeichert. Für die Dauer der angemeldeten Sitzung werden Benutzername und das verschlüsselte Passwort in der PHP-Session gehalten; der Schlüssel liegt getrennt in einem Cookie. Beim Abmelden wird die Session gelöscht. Das Cookie ist für HTTPS vorgesehen, daher sollte die Anwendung über HTTPS betrieben werden.

## Installation

Voraussetzungen:

- PHP 8.3 oder neuer mit den benötigten Erweiterungen, insbesondere Sodium und OpenSSL
- Composer
- Apache mit aktiviertem `mod_rewrite` (die enthaltene `.htaccess` übernimmt die Weiterleitung)
- HTTPS für den produktiven Betrieb

1. Repository auf den Webserver kopieren und Composer-Abhängigkeiten installieren:

   ```bash
   composer install --no-dev --optimize-autoloader
   ```

2. In `config.php` IMAP-Server, Port, Verschlüsselung, Absenderadresse und Zielordner prüfen bzw. anpassen. Standardmäßig ist der IMAP-Server von STRATO eingetragen. Benutzername und Passwort des Postfachs werden **nicht** dort hinterlegt, sondern auf der Anmeldeseite eingegeben.
3. Den Webserver so konfigurieren, dass das Projektverzeichnis als Webroot verwendet wird und PHP-Dateien ausgeführt werden. `mod_rewrite` muss aktiv sein, damit die Routen der Anwendung funktionieren.
4. Die Website über HTTPS öffnen und mit den Zugangsdaten des E-Mail-Postfachs anmelden.

Nach erfolgreicher Anmeldung erscheinen die passenden SiteGuard-Meldungen aus dem Posteingang.


## Hilfe

Frage: Ich bekomme die Meldung `Für diese E-Mailadresse konnten keine Autoconfig Einträge gefunden werden. Bitte tragen Sie die Zugangsdaten in der Datei /config.php ein.`.

Antwort: Die Anwendung versucht die Zugangsdaten per Autoconfig zu ermitteln. Für deine Mailadresse wurden keine Autoconfig Daten gefunden. Daher musst du die Zugangsdaten manuell bei der Anmeldung angeben. Klicke dazu auf der Anmeldeseite auf `Verbindungsinformationen` und trage deinen IMAP-Server und Port ein. Um die Daten dauerhaft zu hinterlegen kannst du sie in der Datei <code>/config.php</code> eingetragen.
WICHTIG: Es werden nur IMAP Postfächer unterstützt.