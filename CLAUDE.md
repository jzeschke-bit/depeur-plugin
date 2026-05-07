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

## Open Questions / Open Items
- **OQ-1:** Live-Konsumenten der Legacy-REST-Routes `wl/v1/posts` / `wrm/v1/rating/*`? → klären vor Task 11+.
- **OQ-2:** Newsletter-Provider-Scope (nur Flodesk vs. Multi-Provider von Tag eins)? → klären vor Task 7.
- **OQ-3:** Verwendung von `mu-plugins/` (aktuell leer)?
- **Item-1:** SSH-Alias `runcloud-test` verfügbar (Linux Testserver, PHP 8.4.20, User `runcloud`, verifiziert in dieser Session). Test-WebApp ist `/home/runcloud/webapps/Food-Blog_Template/` — bestehendes Test-WordPress, freigegeben für Phase-B-Remote-Tests. Lese-Operationen sind jederzeit zulässig; Schreibe-/Push-Operationen erst nach explizitem Push-Approval pro Feature.
- **Item-2:** `wordpress.md`-Updates aus PLAN.md § 6 vom User einpflegen, bevor Phase-B-Implementierung startet.

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
