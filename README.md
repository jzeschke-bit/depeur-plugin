# Depeur Food Suite

Arbeits-Repository für das WordPress-Plugin **`depeur-food`** und das (geplante) Kadence-Child-Theme **`kadence-child`** — zusammen die gemeinsame Basis für die Depeur-Content-Sites:

- **einfachandersessen.de** — Standard-`post`-Typ (Food/Rezepte)
- **alkipedia.com** — Custom Post Type für Cocktails

Das Plugin konsolidiert diverse über die Jahre gewachsene Mini-Plugins in **eine modulare Suite** mit Toggle-System pro Feature — architektonisch nach dem Vorbild von **Depeur WP Suite**. Kernprinzipien: **post-type-agnostisch** (funktioniert mit Standard-Posts *und* CPTs), **ACF nur als Discovery-Quelle** (keine ACF-Abhängigkeit zur Laufzeit für die Datenstruktur) und **kein Composer zur Laufzeit** (eigener PSR-4-Autoloader).

> **Status:** In aktiver Entwicklung. Plugin-Version `0.1.0`. Fundament + erste Feature-Module (`meta-registry`) stehen; nächster Schritt ist das `content-types`-Modul (siehe `CLAUDE.md`).

---

## Anforderungen

- **WordPress** 6.5+
- **PHP** 8.2+
- **Advanced Custom Fields** (Free oder Pro) — Hard-Dependency zur Laufzeit: fehlt ACF, bleibt das Plugin dormant und zeigt nur eine Admin-Notice (kein Datenverlust).
- Für die lokale Entwicklung: **Docker** + **[`@wordpress/env`](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-env/)** (`wp-env`)

---

## Repository-Struktur

```
depeur-food-suite/
├── plugins/
│   ├── depeur-food/          ← das neue Plugin, das hier gebaut wird
│   │   ├── depeur-food.php   # Bootstrap, Konstanten, Autoloader, ACF-Dependency-Check
│   │   ├── src/
│   │   │   ├── Core/         # Plugin, ModuleManager, PostTypeRegistry, Settings, AdminMenu, Activation
│   │   │   └── Helpers/      # Autoloader (PSR-4, ohne Composer)
│   │   ├── modules/          # cache-bridge, content-types, example-module, meta-registry
│   │   ├── phpcs.xml.dist
│   │   └── uninstall.php
│   └── depeur-wp-suite/      ← bestehendes Utility-Plugin: Architektur-Vorlage (Referenz, read-only)
├── _references/              # Legacy-Plugins & -Themes als Migrations-/Studien-Quelle (read-only)
├── _premium/                 # Premium-Drittanbieter-Plugins für die lokale wp-env-Testumgebung
├── .wp-env.json              # lokale Test-Umgebung (PHP 8.2, alle Plugins + Kadence-Theme)
├── wordpress.md              # verbindliche Coding- & Architektur-Standards ("die Bibel")
├── PLAN.md                   # Inventar, Architektur-Entscheidungen (ADRs), frozen
├── CLAUDE.md                 # aktueller Arbeitsstand, Sprint-Status, Session-Handoffs
└── initial-prompt.md         # ursprünglicher Projekt-Auftrag
```

Das **Child-Theme `themes/kadence-child/`** wird in einer späteren Phase gebaut und existiert noch nicht.

---

## Lokale Entwicklung (wp-env)

Die komplette Testumgebung ist in `.wp-env.json` definiert (WordPress + Kadence-Theme + alle Plugins, PHP 8.2, `WP_DEBUG` an).

```bash
# Umgebung starten (Docker muss laufen)
npx wp-env start

# WP-CLI im Container
npx wp-env run cli wp plugin list

# Depeur Food aktivieren
npx wp-env run cli wp plugin activate depeur-food

# Umgebung stoppen
npx wp-env stop
```

Die Site läuft danach unter **http://localhost:8888** (Standard-Login `admin` / `password`).

> **Hinweis zu `_premium/`:** Der Ordner enthält lizenzpflichtige Premium-Plugins (WP Recipe Maker Premium, Rank Math Pro, Kadence Pro/Blocks Pro, Smush Pro, ACF), die `.wp-env.json` für die lokale Testumgebung erwartet. Diese sind **nicht zur Weiterverteilung** bestimmt.

---

## Die beiden Plugins

### `depeur-food` (Neubau)

Modulares Plugin nach dem Depeur-Toggle-Pattern. Wesentliche Architektur:

- **Singleton** `Depeur\Food\Core\Plugin` mit globalem Accessor `depeur_food()` (u. a. `->get_supported_post_types()`).
- **Module** liegen unter `modules/{slug}/` mit je `manifest.php` + `module.php`; aktiv geschaltete Module werden lazy geladen. Aktive Module stehen in der Option `depeur_food_modules`.
- **Settings** pro Modul als eigene Option `depeur_food_{slug}` (tabbed Admin-UI); Feldtypen `checkbox`, `text`, `select`, `password`, `html`.
- **Post-Type-Agnostik** (ADR-4): keine hartkodierten Post-Types; Custom Fields via `register_post_meta`/`register_term_meta`/`register_meta('user', …)` mit `show_in_rest`, statt ACF zur Laufzeit (ADR-5).

Geplante/teilweise gebaute Module: `meta-registry` (Feld-Schema, implementiert), `content-types` (CPT-/Taxonomie-Registrierung), `favorites`, `newsletter`, `schema-engine`, `language-selector`, `cache-bridge` u. a. — Details und Reihenfolge in `PLAN.md` / `CLAUDE.md`.

### `depeur-wp-suite` (Referenz)

Bestehende Utility-Suite (Cache-Management, Security). Dient ausschließlich als **Architektur-Vorlage** für `depeur-food` — gleiches Toggle-Pattern, gleiche Coding-Standards. Wird hier nicht weiterentwickelt.

---

## Dokumentation

| Datei | Zweck |
|---|---|
| **`wordpress.md`** | Verbindliche Coding- & Architektur-Standards. Vor jedem Commit gegen die Self-Review-Checkliste (§ 11) prüfen. |
| **`PLAN.md`** | Inventar, Architektur-Entscheidungen (ADR-1…5), Konsolidierungs-Mapping der Legacy-Plugins. Ändert sich nur bei Architektur-Revisionen. |
| **`CLAUDE.md`** | Lebendiger Arbeitsstand: aktueller Sprint, erledigte Tasks, Session-Handoffs, offene Fragen. |
| **`initial-prompt.md`** | Ursprünglicher Projekt-Auftrag / Kontext. |

---

## Entwicklungs-Konventionen

- **Sprache:** Alle UI-Texte, Kommentare und Doku auf **Deutsch**.
- **Coding-Standards:** WordPress Coding Standards via PHPCS (`phpcs.xml.dist`), zusätzlich `php -l` und wo möglich `wp plugin check`.
- **Prefixe:** Optionen `depeur_food_`, Hooks `depeur_food/...`.
- **Sicherheit:** Admin-Aktionen immer mit Capability-Check + Nonce; Input sanitizen, Output escapen.
- **Commits:** Conventional-Commit-Stil (`feat(...)`, `docs(...)`, `fix(...)`).
- **Feature-Module:** BRIEF-getrieben — vor der Implementierung eines geschäftslogik-tragenden Moduls wird ein `BRIEF.md` geschrieben und freigegeben (§ 12).
- **Keine** anonymen Funktionen für Hooks im Produktionscode, **keine** externen Runtime-Dependencies ohne Rückfrage.

---

## Lizenz

`depeur-food` und `depeur-wp-suite`: GPL-2.0-or-later.
