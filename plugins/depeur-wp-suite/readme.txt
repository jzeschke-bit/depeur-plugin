=== Depeur WP Suite ===

Contributors: depeur
Tags: suite, modules, settings, support
Requires at least: 6.5
Tested up to: 6.5
Stable tag: 1.2.0
Requires PHP: 8.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Modulare WordPress-Plugin-Suite mit Core, Modul-System, Einstellungen und Support.

== Beschreibung ==

Depeur WP Suite ist eine modulare Plugin-Basis. Der Core enthält:

* **Dashboard** – Status und aktive Module
* **Module** – Module aktivieren und deaktivieren
* **Einstellungen** – Tabs pro Modul (inkl. allgemeine Optionen wie Logging)
* **Support** – Systembericht zum Kopieren für Support-Anfragen
* **Autoload Inspector** (Modul) – Anzeige und Bereinigung von Autoload-Optionen: Größe pro Option, Filter (Suche, Prefix, Mindestgröße), Einzel-/Bulk-/Prefix-Löschen mit Preview, Verdächtig-Filter (Warnstufen), Plugin-Scan für Prefix-Mappings, Ignore-Listen.

Das Plugin wird ohne Composer ausgeliefert und ist WordPress.org-konform (GPL-2.0-or-later, keine Telemetrie, keine Update-URI).

== Installation ==

1. Plugin in den Ordner `wp-content/plugins/depeur-wp-suite/` hochladen oder einbinden.
2. Plugin im Menü „Plugins“ aktivieren.
3. Unter „Depeur Suite“ die gewünschten Module aktivieren und Einstellungen anpassen.

== Häufig gestellte Fragen ==

= Muss Composer installiert sein? =

Nein. Das Plugin läuft ohne Composer. Ein eingebauter Autoloader lädt alle Klassen.

= Werden bei Deinstallation Daten gelöscht? =

Beim Deaktivieren passiert nichts. Beim vollständigen Löschen des Plugins (Uninstall) werden nur Optionen mit dem Prefix `depeur_wp_suite_` gelöscht. Keine fremden Daten (z. B. Beiträge oder andere Plugins) werden verändert.

== Changelog ==

= 1.2.0 =
* Modul Autoload Inspector: Anzeige von Autoload- und allen Optionen mit Größe, Filter/Pagination, Einzel-/Bulk-/Prefix-Löschen (mit Preview und Bestätigung), Verdächtig-Tab mit Warnstufen, Plugin-Scan für Prefix-Zuordnung, Regeln & Ignorieren (Prefix-Map, ignorierte Prefixe/Optionen, UI-Einstellungen).

= 1.1.0 =
* Modul BunnyCDN Integration: Pull Zone, Purge All, Post/Page-Trigger, manueller Purge-Button, RunCloud-Sync (optional), Environment-Erkennung.

= 1.0.0 =
* Erste Version: Core, Modul-System, Einstellungen, Support/Systeminfo, optionales Logging, Beispiel-Modul.

== Upgrade-Hinweise ==

Keine.
