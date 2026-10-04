# Sicherheit und Datenverarbeitung

Seitenblick verarbeitet Statistiken lokal in WordPress. Diese Beschreibung erklärt die Schutzmaßnahmen und ihre Grenzen. Sie ersetzt keine Sicherheitsprüfung der gesamten Website.

## Sicherheitslücken melden

Bitte melde eine vermutete Sicherheitslücke vertraulich über [GitHub Security Advisories](https://github.com/BoBBer446/wordpress-plugins/security/advisories/new). Beschreibe die betroffene Version und die Schritte zur Reproduktion. Veröffentliche keine Zugangsdaten, Cookies oder Daten anderer Personen in einem Issue.

## Zugriffsprüfung

Statistik, Export und Einstellungen sind für Benutzer mit `manage_options` vorgesehen. Schreibende Formulare und Exporte prüfen zusätzlich einen WordPress-Nonce. Die Statistik-API verlangt die passende Berechtigung und einen REST-Nonce. Ein Nonce allein erlaubt keinen Zugriff.

Eingaben werden auf Typ, Größe und erlaubte Werte geprüft. Texte werden als Klartext gespeichert und bei der Ausgabe escaped. SQL-Werte und Tabellenkennungen werden über WordPress vorbereitet; zulässige Zählerspalten stammen aus einer festen Liste. CSV-Zellen werden gegen Formeleinschleusung abgesichert.

## Öffentliche Messung

Browsermessung verlangt eine gültige signierte Auswahl. Die Endpunkte prüfen Herkunft, Anfragetyp, Größe und erlaubte Felder. Zufällige Belege gelten nur für eine Ansicht und verfallen nach 30 Minuten. Der Server speichert deren Hash. Zeitmessungen prüfen Reihenfolge, Wiederholungen, abgelaufene Belege und plausible Zeitwerte. Datenbanktransaktionen verhindern Teilupdates.

Globale Mengenbegrenzungen schützen die Endpunkte gegen einfache Überlastung. Sie ersetzen keinen Schutz des Webservers. Browserkennungen und Interaktionen lassen sich nachahmen; Aufrufe beweisen keine menschliche Nutzung.

## Einstellungs-Cookie

Das Cookie wird erst nach einer ausdrücklichen Auswahl gesetzt. Es ist HMAC-signiert und wird auf Struktur, Ablauf und Richtlinienversion geprüft. Es ist host-only, HttpOnly und SameSite=Lax. Bei HTTPS verwendet es Secure und das __Host-Präfix. Seine Laufzeit beträgt höchstens 90 Tage.

Es enthält die aktuelle Auswahl und eine Alters-/Sorgeberechtigungsbestätigung, jedoch keine zufällige Besucherkennung. Es ist kein serverseitiges Einwilligungsarchiv. Eine Checkbox prüft weder das Alter noch die Sorgeberechtigung.

## Fremddienste

Seitenblick kontrolliert klassische WordPress-Skripte und Stylesheets sowie seine eigenen Video-Shortcodes. Nicht zugeordnete externe Skripte bleiben blockiert. Registrierte optionale Dienste werden erst nach der entsprechenden Freigabe geladen.

Hartkodierte Tags, Inline-Code, andere Modul-Einbindungen, externe Bilder und serverseitige Übertragungen brauchen eine eigene Prüfung. Bereits ausgeführter Fremdcode lässt sich nicht zuverlässig entladen; ein Widerruf löst deshalb gegebenenfalls eine neue Seitenladung aus.

Die Inhaltsprüfung liest begrenzte Mengen lokaler veröffentlichter Inhalte. Sie ruft keine Anbieter auf, führt keine Shortcodes aus und schaltet keine Dienste frei. Dynamische Einbindungen kann sie übersehen.

## IP-Ausschlüsse und Proxys

Die Besucheradresse wird für Ausschlüsse nur im Arbeitsspeicher geprüft. Weiterleitungs-Header werden nur von ausdrücklich vertrauten unmittelbaren Absendern berücksichtigt. Die Kette wird von rechts nach links bis zum ersten unvertrauten Absender ausgewertet. Ungültige Ketten, Hostnamen und pauschales Vertrauen in /0 werden abgewiesen.

Die eingetragenen Ausschluss- und Proxylisten liegen in geschützten Einstellungen und können personenbezogene Adressen enthalten. Sie gehören nicht ins öffentliche HTML, in öffentliche API-Antworten oder in Fehlerberichte.

## Aufbewahrung und Grenzen

Summen bleiben standardmäßig 90 Tage erhalten. Ansichtsbelege verfallen nach 30 Minuten und werden durch die stündliche Wartung entfernt. Ohne funktionierenden Cron können Daten länger gespeichert bleiben. Webserverprotokolle, andere Plugins und Backups werden von Seitenblick nicht verwaltet.

Das Plugin garantiert keine vollständige Anonymität oder Rechtskonformität. Rechtsgrundlage, Hinweise, wirksame Einwilligung und Nachweispflichten müssen für den konkreten Einsatz geprüft werden. Das gilt besonders für Angebote an Kinder.

## Freiwillige Unterstützung

Der Spendenhinweis ist auf die eigene Plugin-Verwaltungsseite beschränkt und lässt sich ausblenden. Der feste PayPal-Link wird erst beim Anklicken geöffnet, ohne Referrer und Zugriff auf den Ursprungstab. Es gibt kein Zahlungs-SDK und keine Verarbeitung von Zahlungsstatus oder Spenderdaten im Plugin. Sämtliche Funktionen stehen ohne Spende zur Verfügung.
