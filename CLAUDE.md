# CLAUDE.md — Depeur Food Suite (Working State)

## Projektziel
Plugin `depeur-food` (modular, Toggle-Pattern wie `depeur-wp-suite`) + Child-Theme `kadence-child`, gemeinsam Basis für die Content-Sites einfachanders.es (Standard-`post`) und alkipedia.de (CPT für Cocktails). Architektur post-type-agnostisch, ACF nur als Discovery-Quelle, nicht als Runtime-Dependency.

## Standards & Wissens-Datenbank
`wordpress.md` ist die Standards-Bibel — vor jedem Commit gegen § 11 Self-Review-Hook prüfen. Besonders nicht-verhandelbar: § 2.5 (Code-Lesbarkeit für Wartung in 6+ Monaten — Why-Kommentare, Magic-Number-Erklärungen, Pattern-Klassen-Header), § 6.2 (Admin-UI Documentation — Modul-Intro, Field-Description, Tutorial-Sektion bei komplexen Modulen) und § 12 (Pre-Implementation-Review mit `BRIEF.md` ab Task 4).
`PLAN.md` hält Inventar, Architektur und ADRs (frozen). Beide am Session-Start lesen.

## Architecture Decisions (Stichworte → Detail in PLAN.md § 4)
- ADR-1: Multi-Option-Settings (`depeur_food_{slug}` + `depeur_food_modules`) → siehe PLAN.md § 4.
- ADR-2: PHP-Minimum 8.2 → siehe PLAN.md § 4.
- ADR-3: Cache-Purge Hook-First mit Provider-Pattern (`depeur_food/cache/purge`, `Purge_Context`-Value-Object, vier Provider) → siehe PLAN.md § 4.
- ADR-4: Post-Type-Agnostik via `depeur_food()->get_supported_post_types()` → siehe PLAN.md § 4.
- ADR-5: Custom Fields via `register_post_meta`, kein ACF zur Laufzeit → siehe PLAN.md § 4.

## Aktueller Sprint (TodoWrite-Mirror — wird in Phase B befüllt)
Tasks 1–3 sind reine Strukturarbeit (kein BRIEF.md erforderlich). Ab Task 4 zwingt § 12 (Pre-Implementation-Review) jeweils zwei Sub-Tasks: erst BRIEF schreiben + freigeben lassen, dann erst implementieren.

1. Plugin-Bootstrap (`depeur-food.php` + Konstanten + Autoloader + Helper + Activation/Deactivation/Uninstall + Textdomain + `phpcs.xml.dist`).
2. Core-Klassen (`Plugin`, `Activation`, `AdminMenu`, `ModuleManager`, `PostTypeRegistry`, `Settings/SettingsRegistry`, `Settings/SettingsPage`, `Helpers/Autoloader`).
3. Beispiel-Modul `_ExampleModule` (Discovery + Settings-Render + Lazy-Load validieren).
4. Modul `cache-bridge` — 4a) `BRIEF.md` schreiben + freigeben lassen · 4b) implementieren (Purge_Context, Listener, vier Provider mit Log_Only Always-on).
5. Modul `schema-engine` — 5a) `BRIEF.md` · 5b) implementieren (migriert `category-schema` + `alkipedia/rank-math.php`, post-type-agnostisch, ACF-frei).
6. Modul `favorites` — 6a) `BRIEF.md` · 6b) implementieren (REST-Endpoint mit Nonce, Shortcodes, WPRM-Integration, `register_post_meta`-Like-Counter).
7. Modul `newsletter` — 7a) `BRIEF.md` (klärt OQ-2) · 7b) implementieren (the_content-Inserter, Custom-Meta-Box, Flodesk-Provider).
8. Modul `recipe-extras` — 8a) `BRIEF.md` · 8b) implementieren (Conditional WPRM-Asset-Enqueue, Pinterest-Schema).
9. Theme-Bootstrap — 9a) `BRIEF.md` für Theme-Architektur (analog zu Modul-Brief) · 9b) `themes/kadence-child/` neu anlegen.
10. Theme-Migration — 10a) `BRIEF.md` mit Migrations-Inventar pro `inc/`-File · 10b) Customizations aus `alkipedia` portieren.

## Last Session Handoff
**Stand: 2026-06-08 (erste Claude-4.8-Session). Task 1 abgeschlossen, Smoke grün.**

### Erledigt diese Session
- **Hygiene:** `.DS_Store` (4 Instanzen) untrackt + `.gitignore`-Eintrag (`e943835`). `.wp-env.json`: `seo-by-rank-math-pro` aus Plugin-Mapping entfernt (Verzeichnis war physisch bereits weg), Indent normalisiert (`5652293`).
- **Standards-Fix (`df09c5c`):** wordpress.md § 4.2 ↔ ADR-3-Widerspruch aufgelöst (Edge-/CDN-Purges laufen über die plugin-eigene Hook-First-Fassade `depeur_food/cache/purge`, NICHT über die Suite-API) + Markdown-Glitch in § 1.1 (Z. 21) gefixt.
- **Task 1 (Plugin-Bootstrap) — DONE.** 7 Files unter `plugins/depeur-food/`: `depeur-food.php` (Header `Requires PHP: 8.2`, 5 Konstanten, Autoloader-Registrierung, globaler `depeur_food()`-Helper, Activation/Deactivation/Textdomain/init-Hooks), `src/Helpers/Autoloader.php` (PSR-4: `src/` + `modules/`), `src/Core/Plugin.php` (Singleton + `get_supported_post_types()` ADR-4), `src/Core/Activation.php` (seedet Default-Optionen idempotent), `uninstall.php` (LIKE-Purge `depeur_food_*`), `phpcs.xml.dist` (WPCS, `testVersion 8.2-`, FileName-Sniff für PSR-4 abgeschaltet + Slash-Hooks erlaubt), `.editorconfig`.

### Smoke-Ergebnis (tests-cli / localhost:8889, PHP 8.2) — alle grün
php -l clean · phpcs Exit 0 · Aktivierung ohne PHP-Fehler · `depeur_food()` Singleton (instanceof + Identität) · Autoloader lädt `Activation` · `get_supported_post_types()` → `["post"]` · Activation-Hook seedet `depeur_food_modules=[]` + `depeur_food_supported_post_types=["post"]` · debug.log frei von depeur-Zeilen.

### Bewusst NICHT gemacht / deferred
- **Task 2 (Core-Klassen) offen** — `Plugin::init()` hat nur einen Erweiterungspunkt-Kommentar, keine ModuleManager/AdminMenu/Settings-Verdrahtung.
- **`wp plugin check`**, Post-Type/CPT-Tests, Lighthouse: heute aus Scope genommen (User-Vorgabe), vor den Modul-Tasks nachholen.
- `README.md`/`CHANGELOG.md`/`HOOKS.md`/`languages/` noch nicht angelegt (§ 10, spätere Tasks).

### Offene Doku-Funde (wordpress.md ist User-owned — NICHT von Claude editiert)
- **§ 2.3** fordert `class-{name}.php`, die frozen PSR-4-/Suite-Architektur nutzt aber PascalCase (`Plugin.php`). Stale-Standard wie der § 4.2-Fall; phpcs ist bereits via FileName-Exclude darauf eingestellt — wordpress.md vor weiteren Modulen angleichen.
- **§ 11.4 / § 8.3.6** „post UND einen CPT" vs. ADR-4 „mind. zwei konfigurierte Types" (kosmetisch).
- **§ 1.2** nennt `inc/schema.php` im Theme, PLAN sagt Schema → Plugin (latent).

### Lose Enden
- `_references/ROADMAP-ANALYSIS-NOTES.md` ist untracked (nicht von Claude angelegt) — tracken oder ignorieren? User entscheidet.

### Nächster Schritt
**Task 2 — Core-Klassen** (`ModuleManager`, `AdminMenu`, `PostTypeRegistry`, `Settings/SettingsRegistry`, `Settings/SettingsPage`). Strukturarbeit, kein BRIEF.md nötig (§ 12.1). Davor `wp plugin check` nachziehen.

## Open Questions / Open Items
- **OQ-1:** Live-Konsumenten der Legacy-REST-Routes `wl/v1/posts` / `wrm/v1/rating/*`? → klären vor Task 11+.
- **OQ-2:** Newsletter-Provider-Scope (nur Flodesk vs. Multi-Provider von Tag eins)? → klären vor Task 7.
- **OQ-3:** Verwendung von `mu-plugins/` (aktuell leer)?
- **Item-1:** SSH-Alias `runcloud-test` verfügbar (Linux Testserver, PHP 8.4.20, User `runcloud`, verifiziert in dieser Session). Test-WebApp ist `/home/runcloud/webapps/Food-Blog_Template/` — bestehendes Test-WordPress, freigegeben für Phase-B-Remote-Tests. Lese-Operationen sind jederzeit zulässig; Schreibe-/Push-Operationen erst nach explizitem Push-Approval pro Feature.
- **Item-2 (erledigt 2026-06-08):** Beide Pflicht-Edits aus PLAN.md § 6 sind in `wordpress.md` umgesetzt (§ 1.1 Multi-Option, § 4.5 Autoload); zusätzlich § 4.2 ↔ ADR-3 + Z.21-Glitch gefixt (`df09c5c`). Phase B entsperrt. Siehe „Last Session Handoff".

## Session-Start-Routine
1. `wordpress.md` neu lesen (kann sich geändert haben). Insbesondere § 2.5, § 6.2, § 12 sind frisch und für die Implementierung verbindlich.
2. Dieses CLAUDE.md lesen.
3. `PLAN.md` § 4 (ADRs) querlesen, falls Architektur-Entscheidung berührt. Prüfen, ob für den aktuellen Task (ab Nr. 4) bereits ein vom User freigegebener `BRIEF.md` im Modul-Verzeichnis vorliegt — ohne Freigabe **kein** Code-Schreiben.
4. `git log --oneline -20` für Recent Activity.
5. TodoWrite-Liste hydratisieren aus § "Aktueller Sprint".

## Konventionen kompakt
- Namespace: `Depeur\Food\` — PSR-4 unter `src/`, Module unter `Depeur\Food\Modules\{Slug}\`.
- Hook/Option/Meta-Prefix: `depeur_food_` (snake_case) bzw. `depeur_food/` (Action/Filter-Pfade).
- Frontend-CSS/JS/Body-Class/Shortcode-Tag-Prefix: `df_`.
- Konstanten: `DEPEUR_FOOD_VERSION|FILE|PATH|URL|BASENAME`.
- Plugin-Header: `Requires PHP: 8.2`, `Requires at least: 6.5`, Text Domain `depeur-food`.

## Test-Konfiguration
- **Lokal:** wp-env auf `localhost:8888`, PHP 8.2. Premium-Plugins in `_premium/` gemappt (Kadence Pro, Kadence Blocks Pro, WPRM Premium, Rank Math Pro, Smush Pro, ACF, kadence-blocks). Test-Inhalt: einfachanders.es-Posts (Standard-`post`, ACF-Felder nicht überall befüllt → Graceful-Degradation explizit testbar).
- **Remote (Test-Server):** SSH-Alias `runcloud-test` (Linux testserver, PHP 8.4.20, User `runcloud`). Test-WebApp: `/home/runcloud/webapps/Food-Blog_Template/`. Lese-Operationen jederzeit (`ssh runcloud-test "wp ... --path=/home/runcloud/webapps/Food-Blog_Template"`); Schreibe-/Push-Operationen (rsync, `wp plugin activate`, etc.) erst nach explizitem Push-Approval pro Feature.
- **PHP-Versions-Diff:** Lokal 8.2, Remote 8.4. Bei Remote-Tests `tail -f` auf das PHP-Error-Log laufen lassen, um 8.4-Deprecations zu erwischen, die lokal nicht auftauchen. Beispiel-Befehle siehe PLAN.md § 5.

## Operational Notes
- **Plugin-Updates NIE über wp-admin** für Plugins, die in `.wp-env.json` als lokale Pfade (`./_premium/...` oder `./plugins/...`) referenziert sind. Stattdessen Terminal-Workflow: `wp-env stop` → `rm -rf _premium/{plugin}` → `curl`/`unzip` der frischen Version → `wp-env start --update`. Sonst landet der Ordner in einem Halb-Zustand und WordPress kann das Plugin nicht mehr finden.
- **Working Environment ist `localhost:8889` (tests-environment), nicht `localhost:8888`.** Aller importierter Content liegt in der tests-DB. Bei wp-cli-Befehlen explizit `wp-env run tests-cli wp ...` verwenden, nicht `wp-env run cli wp ...`. Die tests-Environment in wp-env ist offiziell deprecated und wird irgendwann entfernt — Migration auf `localhost:8888` (development) ist mittelfristig nötig, aber kein aktueller Blocker. Migration = `wp db export` aus `tests-cli` + `wp db import` in `cli` + Such-/Ersetzen der Site-URLs.
