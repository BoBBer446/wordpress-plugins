# Seitenblick installieren und einstellen

Seitenblick funktioniert auf einer einzelnen WordPress-Website. Für den vorgesehenen produktiven Einsatz brauchst du HTTPS und MySQL oder MariaDB mit InnoDB. Multisite wird noch nicht unterstützt.

## Installation

Installiere das ZIP unter **Plugins → Installieren → Plugin hochladen** und aktiviere Seitenblick Statistik. Die Verwaltung erscheint unter **Seitenblick**.

Bei einer neuen Installation ist die Browsermessung ausgeschaltet. Allgemeine Seitenaufrufe werden auf dem Server gezählt. Prüfe zuerst die Einstellungen und deine Datenschutzhinweise, bevor du die freiwillige Zeitmessung anbietest.

## Zahlen lesen

Serveraufrufe und gemessene Browseransichten sind verschiedene Größen. Ein Serveraufruf ist eine HTML-Anfrage, die WordPress erreicht. Eine Browseransicht setzt eine Freigabe voraus und kann auch durch Navigation innerhalb einer App entstehen. Addiere diese Werte nicht.

Aufrufe sind keine eindeutigen Besucher. Seitenblick verfolgt keine einzelnen Personen und führt keine Besucherliste. Wiederholte Aufrufe können mehrfach gezählt werden.

Die aktive Zeit berücksichtigt sichtbare, fokussierte Seiten und kürzliche Interaktionen. Sie ist eine Näherung. Durchschnittswerte beziehen sich nur auf Ansichten mit tatsächlich gemessener Zeit. Caches, Verbindungsfehler und fehlende Einwilligungen beeinflussen die Zahlen.

Der Knopf **Beispieldaten ansehen** zeigt eine gekennzeichnete Vorschau. Er speichert keine Werte und erlaubt keinen Export erfundener Zahlen.

## Texte und Dienste

Öffne unten die Einstellungen. Dort kannst du die Texte der Datenschutz-Auswahl ändern. Ein leeres Textfeld verwendet wieder den Standard. Änderungen an Einwilligungstexten oder angebotenen Diensten können eine neue Auswahl erforderlich machen.

Aktiviere nur Dienste, die auf deiner Website tatsächlich eingebunden sind. YouTube und Vimeo sind zunächst ausgeschaltet. Ohne ausgewählte externe Medien erscheint diese Kategorie auch nicht im Dialog.

Für Videos verwende die Seitenblick-Shortcodes aus der WordPress-Readme. Die Auswahl eines Dienstes allein sperrt keine beliebigen iframes aus anderen Plugins.

**Inhalte prüfen & Empfehlungen anzeigen** sucht in veröffentlichten Inhalten nach möglichen Fremdeinbindungen. Der Bericht ist begrenzt und schaltet nichts automatisch frei. Dynamische Einbindungen und serverseitige Übertragungen müssen zusätzlich geprüft werden.

## Eigene Aufrufe ausschließen

Trage eine IPv4-Adresse, IPv6-Adresse oder ein CIDR-Netz pro Zeile in die IP-Ausschlussliste ein. Beispiele aus den Dokumentationsnetzen:

```text
192.0.2.10
2001:db8::10
192.0.2.0/24
```

Die Statistik speichert dafür keine eingehenden Besucheradressen. Die eingegebenen Listen bleiben private Einstellungen. Ein Ausschluss betrifft künftige Aufrufe; alte Summen können nicht nach einer IP-Adresse gefiltert werden.

Vertraute Proxys solltest du nur eintragen, wenn du die tatsächlich verwendete Infrastruktur kennst. Ohne diese Einstellung vertraut Seitenblick fremden Weiterleitungs-Headern nicht.

## Aufbewahrung und Löschen

Summen bleiben standardmäßig 90 Tage erhalten. Die Aufbewahrung lässt sich auf 7 bis 365 Tage einstellen. Sorge dafür, dass WordPress-Cron mindestens stündlich läuft. Ohne regelmäßige Wartung können abgelaufene Daten länger gespeichert bleiben.

Deaktivierung behält die Daten. Eine spätere Deinstallation löscht sie nur, wenn du vorher ausdrücklich **Plugin-Daten bei späterem Löschen endgültig entfernen** eingeschaltet hast. Backups und Webserverprotokolle musst du unabhängig davon verwalten.

## Spendenhinweis

Der Hinweis zur Unterstützung erscheint nur in der Plugin-Verwaltung. Du kannst ihn ausblenden und seine Überschrift, Beschreibung sowie den Knopftext bearbeiten. PayPal öffnet sich erst beim Anklicken. Eine Spende ist für keine Funktion erforderlich.

## Vor dem Einsatz

Passe den Textvorschlag unter **Einstellungen → Datenschutz** an deine Website an. Prüfe die Rechtsgrundlage der Serverzählung, die Einwilligung für Browsermessung und die tatsächlichen Fremddienste. Bei einem Kinderangebot reicht die Checkbox nicht als Nachweis überprüfter Sorgeberechtigung.

Seitenblick ist kein Nachweis, dass eine Website rechtlich oder technisch vollständig abgesichert ist.
