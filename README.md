# WordPress-Plugins

Hier liegen meine WordPress-Plugins. Jedes Plugin hat einen eigenen Ordner, eine Installationsanleitung und eine Lizenz. Weitere Plugins kommen später dazu.

## Seitenblick Statistik

Seitenblick zählt Seitenaufrufe auf dem eigenen Server und zeigt die Ergebnisse in einer farbigen Übersicht. Wer zustimmt, kann zusätzlich die aktive Zeit auf einer Seite messen lassen. Der integrierte Einwilligungsdialog bietet nur die Dienste an, die der Betreiber ausgewählt hat.

- Seitenaufrufe, Tagesverlauf, aktive Zeit und CSV-Export
- Bearbeitbare Texte für die Datenschutz-Auswahl
- Getrennte Freigaben für Statistik und registrierte Fremddienste
- Ausschluss von IP-Adressen und Netzen
- Inhaltsprüfung mit Hinweisen auf mögliche externe Einbindungen
- Freiwilliger, ausblendbarer Spendenhinweis im Plugin-Backend

Die Statistik speichert keine Besucherprofile und keine IP-Adressen. Aufrufe sind keine eindeutigen Besucher. Eine durchschnittliche Verweildauer bezieht sich nur auf die Ansichten, für die eine Zeitmessung freigegeben wurde.

Quellcode und WordPress-Readme: [plugins/seitenblick](plugins/seitenblick). Anleitung: [Seitenblick installieren](docs/seitenblick.md). Prüfstand und Schritte für WordPress.org: [Veröffentlichung](docs/veroeffentlichung.md).

## Download

Das installierbare [ZIP für Seitenblick 0.3.1](https://github.com/BoBBer446/wordpress-plugins/releases/download/seitenblick-v0.3.1/seitenblick-0.3.1.zip) liegt bei den [Releases](https://github.com/BoBBer446/wordpress-plugins/releases/tag/seitenblick-v0.3.1). Das Paket ist für die WordPress.org-Einreichung vorbereitet; die dortige Prüfung steht noch aus.

## Entwicklung

Die Plugin-Dateien sind lesbarer PHP-, JavaScript- und CSS-Code. Es gibt keine minifizierten Dateien und keinen Build-Schritt für die Installation. Mit `python3 tools/package.py seitenblick` entsteht ein ZIP mit dem Plugin-Ordner. Entwicklungsunterlagen und Tests gehören nicht ins Installationspaket.

Tests und Prüfwerkzeuge stehen unter [tests](tests) und [tools](tools). Unterstützte und tatsächlich geprüfte Versionen sind in der Veröffentlichungsdokumentation getrennt aufgeführt.

## Lizenz

Seitenblick steht unter GPLv2 oder neuer. Die vollständige Lizenz liegt im [Plugin-Ordner](plugins/seitenblick/LICENSE). Für künftige Plugins gilt die jeweils beiliegende Lizenz.

## Unterstützung

Fehler und Verbesserungsvorschläge können über die GitHub-Issues gemeldet werden. Für Sicherheitslücken bitte die vertrauliche Meldemöglichkeit dieses Repositorys nutzen; Zugangsdaten und Besucherinformationen gehören nicht in öffentliche Issues.
