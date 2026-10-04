# Seitenblick für WordPress.org

Stand: 4. Oktober 2026. Version 0.3.0 ist für die Einreichung vorbereitet. Sie ist noch nicht von WordPress.org freigegeben und noch nicht im Plugin-Verzeichnis veröffentlicht.

## Paket und Konto

Plugin-Name: Seitenblick Statistik. Vorgesehener Ordner und Text Domain: seitenblick. WordPress.org-Konto laut Betreiber: eXestend; Contributor-Slug in der Readme: exestend. Autor: René Wurch. Lizenz: GPLv2 oder neuer.

Die ZIP enthält den Ordner seitenblick mit PHP, JavaScript, CSS, Übersetzungsvorlage, WordPress-Readme, Sicherheitsbeschreibung und Lizenz. Es gibt keine minifizierten Dateien, Drittanbieterbibliotheken, Zugangsdaten oder Besucherdaten.

Die Verzeichnis-Readme ist auf Englisch, weil der offizielle Plugin Check dies für die Beschreibung verlangt. Die Anleitung im Repository ist auf Deutsch. PHP-Texte und Dashboard-Texte sind an WordPress-Übersetzungen angebunden; die mitgelieferte Standardoberfläche ist deutsch. Eigene Texte bleiben über die Einstellungen bearbeitbar.

## Geprüfter Stand

| Prüfung | Ergebnis |
| --- | --- |
| WordPress 7.1.2, PHP 8.3.35, MariaDB 11.8.9/InnoDB | Aktivierung und Funktionen geprüft |
| PHP 8.1.2 unter Ubuntu | Syntax aller PHP-Dateien geprüft |
| Offizieller Plugin Check, statisch | 0 Fehler, 46 Datenbankhinweise |
| Offizieller Plugin Check mit Laufzeitprüfungen | 0 Fehler, dieselben 46 Datenbankhinweise |
| WordPress-/Datenbankprüfungen | 157 Prüfungen bestanden |
| Browserprüfung auf isolierter Installation | 29 Prüfungen bestanden |
| Darstellung | 1440, 390 und 320 Pixel ohne Überlauf; visuell geprüft |
| Messung | Tatsächliche Ansicht und aktive Zeit nach Einwilligung; Widerruf geprüft |
| Einstellungen | Speichern und erneutes Laden auf isolierter Installation geprüft |
| Installationspaket | Version, Stable tag, Inhalt und ZIP-Integrität geprüft |

Die Funktionsprüfungen betreffen WordPress 7.1.2 und PHP 8.3.35. Die angegebene Mindestversion WordPress 6.5 ist nicht als vollständig geprüfte Browsermatrix dargestellt. MySQL, Multisite, sämtliche Cache-Systeme und beliebige Fremdplugins sind nicht als getestet behauptet.

Die Browserprüfung verwendet eine eigene Datenbank, eigene Dateien und eine lokale Prüfadresse. Fehlgeschlagene Versuche durch den Prüftransport wurden korrigiert. Es wurden keine produktiven Inhalte oder Einstellungen geändert und keine Zahlung ausgeführt.

## Datenbankhinweise des Plugin Check

Die verbliebenen Hinweise betreffen:

- 23 direkte Abfragen auf eigenen Statistik- und Schutzdatentabellen
- 22 Hinweise auf nicht gecachte Abfragen
- eine beabsichtigte Schemaänderung bei der Deinstallation

Für die eigenen Tabellen gibt es keine passende WordPress-Inhaltsabfrage. Summen werden atomar erhöht; Zeitbelege werden in einer Transaktion gesperrt und fortgeschrieben. Diese Vorgänge und Replay-Prüfungen dürfen keine veralteten Cachewerte verwenden. Berichte lesen aktuelle Summen. Der Schemahinweis betrifft ausschließlich das Löschen der beiden eigenen Tabellen nach zuvor eingeschalteter Löschoption.

Tabellenkennungen und Werte werden vorbereitet. Zählerspalten stammen aus einer festen Liste. Dynamische SQL-Teile enthalten nur selbst erzeugte Platzhalter; die betreffende Ausnahme ist im Code begründet. Die Hinweise wurden nicht pauschal ausgeblendet. Die manuelle Prüfung kann weitere Änderungen verlangen.

## Einreichen

1. Mit eXestend bei WordPress.org anmelden.
2. [Plugin einreichen](https://de.wordpress.org/plugins/developers/add/) öffnen.
3. Das Paket seitenblick-0.3.0.zip hochladen.
4. Die Angaben zur Lizenz, Verantwortung und Pflege im Formular prüfen und die Einreichung abschicken.
5. Auf die Rückmeldung des Plugin-Teams reagieren. Erst dessen Freigabe bestätigt die Aufnahme.

Der endgültige Plugin-Slug wird bei der Prüfung festgelegt. Ein aktuell nicht auffindbarer Name ist keine Zusage, dass er verfügbar ist.

Der Plugin Check ersetzt die manuelle Prüfung nicht. Ebenso sind die technischen Prüfungen keine rechtliche Zertifizierung. Insbesondere Einwilligungen bei Kindern, tatsächliche Fremddienste und Betreiberpflichten müssen für den jeweiligen Einsatz geprüft werden.

## Nach der Freigabe

WordPress.org verteilt Plugins aus seinem SVN-Repository. GitHub ist der Entwicklungsort; ein GitHub-Upload allein veröffentlicht nichts im WordPress-Verzeichnis.

Das Werkzeug tools/prepare_svn.py legt lokal trunk/, tags/0.3.0/ und assets/ an. Übertrage deren Inhalt anschließend in den von WordPress.org zugewiesenen SVN-Checkout. Die Hauptdatei liegt direkt in trunk/ und im Versionsordner. Screenshots kommen nach assets/. Prüfe mit svn status und svn diff, bevor du einen fertigen Release überträgst.

Es gibt keinen automatischen SVN-Upload und keinen Update-Dienst außerhalb von WordPress.org.

## Quellen

- [WordPress-Plugin-Richtlinien](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/)
- [Plugin Check](https://wordpress.org/plugins/plugin-check/)
- [Readme-Standard](https://de.wordpress.org/plugins/readme.txt)
- [Readme-Validator](https://de.wordpress.org/plugins/developers/readme-validator/)
- [SVN-Veröffentlichung](https://developer.wordpress.org/plugins/wordpress-org/how-to-use-subversion/)
