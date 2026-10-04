# Änderungen beitragen

Beschreibe Fehler mit der Plugin-Version, WordPress- und PHP-Version und den Schritten zur Reproduktion. Screenshots können helfen; entferne vorher Zugangsdaten und Daten anderer Personen.

Jedes Plugin liegt unter plugins/<ordnername>. Neue Plugins bekommen einen eigenen Ordner, eine WordPress-Readme, eine Lizenz und passende Tests. Installationspakete enthalten nur die Dateien des jeweiligen Plugins.

Bei Seitenblick dürfen Änderungen keine Besucherprofile, Telemetrie oder versteckten Fremdabrufe einführen. Optionale Messung und Fremddienste brauchen eine nachvollziehbare Freigabe. Bereits vorhandene Daten und Einstellungen müssen bei Updates erhalten bleiben.

Prüfe PHP-Syntax, das Installationspaket und den offiziellen WordPress Plugin Check. Datenbank- und Browserprüfungen laufen ausschließlich auf einer isolierten Testinstallation. Eine fremde produktive Website ist keine Testumgebung.

Formatiere JavaScript und CSS mit dem im Repository angegebenen Werkzeug. PHP orientiert sich an den WordPress Coding Standards. Kommentare sollen Entscheidungen erklären, statt den Code nachzuerzählen.
