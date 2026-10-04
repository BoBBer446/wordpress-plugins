# Prüfen und Pakete bauen

Für die Paketwerkzeuge genügt Python 3. PHP-, WordPress- und Browserprüfungen benötigen zusätzlich die jeweiligen Laufzeiten.

## Installationspaket

```sh
python3 tools/package.py seitenblick
```

Das ZIP und seine Prüfsumme landen in dist/. Der Name des Plugin-Ordners steht an der Wurzel des ZIPs. Das Werkzeug prüft, ob Plugin-Version und Stable tag übereinstimmen. Es nimmt keine Tests, Zugangsdaten, Datenbankinhalte oder Node-Pakete auf.

## Formatierung und Syntax

```sh
npm ci --ignore-scripts
npm run format:check
find plugins/seitenblick -name '*.php' -exec php -l {} \;
```

JavaScript und CSS sind unminifiziert. Die Formatierung ist ein Entwicklungswerkzeug; das Plugin benötigt Node nicht.

## WordPress-Prüfungen

Lege eine isolierte WordPress-Testinstallation mit InnoDB, einem Administrator namens review, Standard-Permalinks /%postname%/ und der veröffentlichten Beispielsseite /sample-page/ an. Installiere Seitenblick und den offiziellen Plugin Check dort. Verwende keine produktive Website.

```sh
wp plugin check seitenblick
wp plugin check seitenblick --require=wp-content/plugins/plugin-check/cli.php
wp --skip-plugins=seitenblick eval '$args=["/absoluter/pfad/plugins/seitenblick"]; require "/absoluter/pfad/tests/seitenblick-wordpress.php";'
```

Die PHP-Prüfungen verwenden verbindungsgebundene TEMPORARY-Tabellen und Einstellungen im Arbeitsspeicher. Die Browserprüfung ändert Einstellungen und erzeugt echte Messwerte auf der Testinstallation. Beginne dafür mit einer frischen Installation und leeren Plugin-Tabellen. Das Testpasswort bleibt außerhalb des Repositorys.

```sh
npx playwright install chromium
TEST_URL=http://127.0.0.1:18124 ALLOW_MUTATING_WP_TESTS=1 SEITENBLICK_TEST_PASSWORD='<lokales-testpasswort>' node tests/browser.mjs
```

Die Browserprüfung akzeptiert nur eine ausdrücklich freigegebene Loopback-Adresse. Sie prüft den Dialog, das Speichern von Einstellungen, echte Messung, Widerruf und die Darstellung bei 1440, 390 und 320 Pixeln. Zahlungslinks werden nicht geöffnet.

## WordPress.org

```sh
python3 tools/prepare_svn.py seitenblick
```

Das Werkzeug legt trunk/, tags/<version>/ und assets/ unter dist/ an. Es erstellt weder ein SVN-Repository noch einen Upload. Nach der Freigabe durch WordPress.org müssen die Dateien in dessen zugewiesenen SVN-Checkout übertragen und dort geprüft werden.
