# Depeur WP Suite

Modulare WordPress-Plugin-Suite (Core + Modul-System). Entwickler-Dokumentation.

## Anforderungen

- WordPress 6.5+
- PHP 8.0+
- Kein Composer zur Laufzeit nötig

## Struktur

```
depeur-wp-suite/
├── depeur-wp-suite.php   # Bootstrap, Konstanten, Autoloader
├── uninstall.php
├── readme.txt / README.md
├── phpcs.xml.dist
├── src/
│   ├── Core/             # Plugin, AdminMenu, ModuleManager, Settings
│   ├── Support/          # SystemInfo, Logger
│   └── Helpers/          # Autoloader
├── modules/
│   ├── _ExampleModule/   # Dummy-Modul als Vorlage
│   ├── autoload-cleanup/ # Autoload Inspector: Optionen anzeigen/bereinigen, Verdächtig-Filter, Regeln
│   └── bunny-cdn/        # BunnyCDN Integration
└── assets/admin/
```

## Autoloader

Namespace `Depeur\WPSuite` wird auf `src/` gemappt. Klassen unter `Depeur\WPSuite\Core\*` liegen in `src/Core/`. Module werden per `require_once` aus `modules/{Ordner}/module.php` geladen; Modul-Klassen können eigenen Namespace nutzen (z. B. `Depeur\WPSuite\Modules\ExampleModule\Admin`) und werden über die Modul-Datei eingebunden.

## Modul anlegen

1. Ordner unter `modules/` anlegen (z. B. `MeinModul`).
2. `manifest.php` mit Rückgabe-Array: `slug` (Ordnername), `name`, `description`, `version`.
3. `module.php`: bei Bedarf weitere Dateien einbinden und Modul-Bootstrap aufrufen (z. B. Settings registrieren).
4. Unter „Depeur Suite → Module“ das Modul aktivieren.

## Einstellungen

Module registrieren ihre Felder bei `Depeur\WPSuite\Core\Settings\SettingsRegistry::register( $module_slug, $tab_label, $fields, $description = '' )`. Feldtypen: `checkbox`, `text`, `select`. Optionen werden als ein Array pro Modul gespeichert: `depeur_wp_suite_{module_slug}`. Für API-Keys etc. kann pro Feld `'autoload' => false` gesetzt werden (Option wird dann mit `autoload=no` gespeichert).

## Konventionen

- Alle UI-Texte und Kommentare auf Deutsch.
- Prefix für Optionen: `depeur_wp_suite_`.
- Hooks: `depeur_wp_suite/...` bzw. `depeur_wp_suite/module/{module_slug}/...`.
- Admin-Aktionen: Capability-Check + Nonce; Input sanitizen, Output escapen.

## Entwicklung

- PHPCS: WordPress Coding Standards (phpcs.xml.dist).
- Keine anonymen Funktionen für Hooks im Produktionscode.
