=== Seitenblick Statistik ===
Contributors: exestend
Donate link: https://www.paypal.com/donate/?hosted_button_id=G72QV8CQAL6DY&locale.x=de_DE
Tags: statistics, analytics, privacy, consent, dashboard
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 8.1
Stable tag: 0.3.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Local page-view statistics, optional active-time measurement and a consent dialog with editable text and service settings.

== Description ==

Seitenblick records page views on your own server and presents them in a dashboard. It shows a daily trend, page totals, broad device and traffic-source categories, and the hours when pages are opened. Reports cover 7, 30, 90 or 365 days and can be exported as CSV.

Optional browser measurement records page views and active time after a visitor has agreed. Server requests and browser views are separate figures: they are not added together. Active time is an estimate based on a visible, focused page and recent interaction, with a limit of 30 minutes per view. Averages include only views with recorded active time.

The plugin does not count unique visitors. It does not store IP addresses, IP hashes, full browser identifiers, complete referrer URLs, search terms, visitor profiles or session identifiers in its statistics. Known bots, monitoring requests, logged-in users, Do Not Track and Global Privacy Control are excluded. Bot detection cannot identify every automated request. IP exclusion rules are checked in memory; the administrator's configured address lists are stored privately as settings.

= Consent and external content =

The consent dialog separates necessary functions, statistics and registered external services. Optional choices start disabled. You can edit the text and select which registered services to offer. YouTube and Vimeo start disabled. The external-media category is omitted when no service in that category is selected.

Browser measurement starts disabled on a new installation. Once offered, it requires the visitor's recorded consent and an age or parental-responsibility confirmation. The checkbox does not verify age or parental authority.

An explicit choice sets a signed preference cookie for up to 90 days. It is HttpOnly and SameSite=Lax; HTTPS installations use Secure and a __Host- prefix. The cookie contains the chosen categories, a policy version, an age/parental confirmation and dates, without a random visitor ID. There are no statistics cookies or local-storage entries for statistics. A saved refusal stops subsequent server counting; an initial aggregate request before that refusal may already have been counted.

Classic WordPress-enqueued external scripts and styles are blocked until their registered category is accepted. Unclassified external scripts remain blocked. Use the supplied video shortcodes for YouTube and Vimeo. Hard-coded tags, inline code, module scripts outside these hooks, external images and server-side transfers by other plugins need their own integration and review. The plugin is not a sandbox for other plugins.

= External services =

Statistics and consent requests stay on the WordPress site. The content audit reads local published content and does not contact providers.

* YouTube: only after an enabled service and visitor consent, the video shortcode loads a player from youtube-nocookie.com. Google receives the resulting network request and may process technical data such as the visitor's IP address. [Privacy policy](https://policies.google.com/privacy) and [terms](https://www.youtube.com/static?template=terms).
* Vimeo: only after an enabled service and visitor consent, the video shortcode loads player.vimeo.com. Vimeo receives the resulting request and may process technical data such as the visitor's IP address. [Privacy policy](https://vimeo.com/privacy) and [terms](https://vimeo.com/terms).
* PayPal: an optional donation link appears only on the plugin's own admin page. PayPal is contacted only when the administrator follows the link. There is no payment SDK, remote image, payment form or payment-status recording. [Privacy statement](https://www.paypal.com/de/legalhub/paypal/privacy-full) and [terms](https://www.paypal.com/de/legalhub/paypal/useragreement-full).
* Custom services: a developer may register a service through the seitenblick_services filter. The site's operator must document its actual purpose, provider and data transfers before enabling it.

= Data retention =

Totals are kept for 90 days by default; the setting allows 7 to 365 days. Temporary per-view receipts expire after 30 minutes. They store a hash of a random view token, a page reference and timing information, without linking separate views. Global request-limit windows expire after at most two minutes. Hourly WordPress cron removes expired records. Reliable deletion requires working cron; with hourly maintenance, an expired view receipt can remain for about another hour. Backups and web-server logs must be managed separately.

Deactivation keeps the data. Uninstall removes it only if the administrator previously enabled deletion in the settings.

= Site setup =

HTTPS, a single-site WordPress installation and MySQL or MariaDB with InnoDB are required for the intended production use. Multisite is not supported. Query-based permalinks, private content, search results, previews and unknown routes are not counted. Full-page caches can bypass server-side counting. An optional adapter supports the Wald-Schein learning app; the plugin also works without it.

Seitenblick supplies controls and a privacy-policy draft, not a legal certification. The operator must assess the site's legal basis, notices, consent requirements, children’s data, external integrations, logs and backups. The cookie records the current browser preference; it is not a server-side consent archive.

== Installation ==

1. Upload the ZIP through Plugins > Add New > Upload Plugin, or copy the seitenblick folder to wp-content/plugins.
2. Activate Seitenblick Statistics.
3. Open Seitenblick and review measurement, retention, editable text, services and exclusions.
4. Keep browser measurement disabled until your site's notices and consent process are ready.
5. Review the draft under Settings > Privacy and adapt it to the actual site.
6. Check that WordPress cron runs at least hourly and test the consent dialog on a public page.

== Frequently Asked Questions ==

= Are page views unique visitors? =

No. Repeat views by the same person can be counted. Seitenblick does not identify individual visitors.

= Why do server requests and measured views differ? =

Server counting records HTML requests that reach WordPress. Browser counting records only agreed views and app navigation. A full-page cache can serve a page without reaching WordPress.

= Can I edit the dialog text and hide unused providers? =

Yes. Open the settings on the Seitenblick page. Only selected registered services are offered. YouTube and Vimeo are initially disabled.

= How do I exclude my own IP address? =

Enter an IPv4 address, IPv6 address or CIDR network in the exclusion list. Each entry goes on its own line. Configure trusted proxies only when you know the actual forwarding chain. Exclusions affect future counts; old totals cannot be filtered by IP.

= Does the content audit find every external service? =

No. It checks a bounded amount of local published content for likely embeds and gives recommendations. It cannot fully inspect dynamic JavaScript, PHP, CSS, server-side requests or other plugins. It does not enable services automatically.

= How do I embed a video? =

Enable the provider, document its use and insert one of these shortcodes:

[seitenblick_video anbieter="youtube" id="abcdefghijk" titel="Video title"]
[seitenblick_video anbieter="vimeo" id="12345678" titel="Video title"]

The player is loaded only after the relevant consent. Normal WordPress embeds and hard-coded iframes need a separate review.

= Is a donation required? =

No. Every included feature works without a donation. You can hide the admin donation notice and edit its heading, description and button text.

== Screenshots ==

1. Dashboard with clearly marked example data. No visitor data is shown.
2. The same dashboard on a narrow screen.

== Changelog ==

= 0.3.1 =
* Link the author name to wald-schein.de.

= 0.3.0 =
* Prepare the plugin for WordPress.org review.
* Use WordPress enqueue functions on standard themes.
* Prepare SQL table and column identifiers explicitly.
* Add directory documentation and translation support.

= 0.2.2 =
* Open the PayPal donation page in German.

= 0.2.1 =
* Add an optional admin donation notice with editable text.

= 0.2.0 =
* Add editable consent text, service selection, IP exclusions and content recommendations.

= 0.1.0 =
* First installable development version.

== Upgrade Notice ==

= 0.3.1 =
The author link now opens wald-schein.de. Statistics and settings are unchanged.

= 0.3.0 =
Review service settings and consent text after upgrading. Existing statistics and settings are retained.
