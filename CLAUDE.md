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
Tasks 1–3 + Task 4 (Tab-System, Core-UI-Infrastruktur) sind Strukturarbeit. Ab dem ersten geschäftslogik-tragenden Modul (`cache-bridge`, Task 5) zwingt § 12 (Pre-Implementation-Review) jeweils zwei Sub-Tasks: erst BRIEF schreiben + freigeben lassen, dann implementieren. (Offen: ob Task 4 Tab-System einen BRIEF braucht — Core-UI wie Task 2, vmtl. § 12.1-exempt; bei Task-4-Start bestätigen.)

1. Plugin-Bootstrap (`depeur-food.php` + Konstanten + Autoloader + Helper + Activation/Deactivation/Uninstall + Textdomain + `phpcs.xml.dist`). ✓ DONE
2. Core-Klassen (`Plugin`, `Activation`, `AdminMenu`, `ModuleManager`, `PostTypeRegistry`, `Settings/SettingsRegistry`, `Settings/SettingsPage`, `Helpers/Autoloader`). ✓ DONE
3. Beispiel-Modul `example-module` (Discovery + Lazy-Load + SettingsRegistry-Anmeldung validiert; Modul-Architektur-Kanon eingefroren, s. Handoff). ✓ DONE
4. **Tab-System / Modul-Verwaltung (NEU — Voraussetzung für `cache-bridge`):** SettingsPage um Modul-Tabs erweitern (rendert die via `SettingsRegistry` angemeldeten Schemata) + Modul-Aktivierungs-Toggle-UI (schreibt `depeur_food_modules`). Ohne dies kein Modul-Settings-UI. (In Task 2 + 3 bewusst deferred.)
5. Modul `cache-bridge` — 5a) `BRIEF.md` schreiben + freigeben lassen · 5b) implementieren (Purge_Context, Listener, vier Provider mit Log_Only Always-on).
6. Modul `schema-engine` — 6a) `BRIEF.md` · 6b) implementieren (migriert `category-schema` + `alkipedia/rank-math.php`, post-type-agnostisch, ACF-frei).
7. Modul `favorites` — 7a) `BRIEF.md` · 7b) implementieren (REST-Endpoint mit Nonce, Shortcodes, WPRM-Integration, `register_post_meta`-Like-Counter).
8. Modul `newsletter` — 8a) `BRIEF.md` (klärt OQ-2) · 8b) implementieren (the_content-Inserter, Custom-Meta-Box, Flodesk-Provider).
9. Modul `recipe-extras` — 9a) `BRIEF.md` · 9b) implementieren (Conditional WPRM-Asset-Enqueue, Pinterest-Schema).
10. Theme-Bootstrap — 10a) `BRIEF.md` für Theme-Architektur (analog zu Modul-Brief) · 10b) `themes/kadence-child/` neu anlegen.
11. Theme-Migration — 11a) `BRIEF.md` mit Migrations-Inventar pro `inc/`-File · 11b) Customizations aus `alkipedia` portieren.

## Last Session Handoff
**Stand: 2026-06-10 (zweite Claude-4.8-Session, Forts.). Task 3 (`example-module`) abgeschlossen, Smoke + 8-Punkte-Konsistenz-Check grün.**

### Session 2026-06-10 (Forts.) — Task 3 (`example-module`) DONE
- **4 Files** unter `plugins/depeur-food/modules/example-module/`: `manifest.php`, `module.php` (Bootstrap), `Admin/Settings.php` (Bootstrap-Klasse: Settings-Anmeldung + Demo-Filter `depeur_food/example/greeting`), `BRIEF.md` (Architektur-Snapshot, lebt mit dem Modul, § 12.4) — plus Minor-Docblock in `src/Core/Settings/SettingsRegistry.php`.
- **Edge-Case durch Approval-Gate gefangen (vor dem ersten Klassen-Body):** macOS case-insensitive FS → `module.php` ≡ `Module.php` kollidiert. Gelöst durch Struktur (Klasse in `Admin/`-Subordner), **ohne ModuleManager-Änderung** (kein Drive-by-Fix). Daraus die FS-Safety-Konvention (Kanon-Punkt 3).
- **Smoke grün:** phpcs Exit 0 · `php -l` (4 Files) · WP-Mechanik aktiv/inaktiv-Toggle (Schema registriert/weg, Klasse geladen/nicht, Filter feuert/Default) · debug.log frei · `wp plugin check` 0 neue Findings.
- **8-Punkte-BRIEF-vs-Code-Konsistenz-Check** alle ✓ (6 BRIEF-vs-Code + FS-Safety + Autoloader-only). Code = BRIEF, kein Drift.
- **`depeur_food_modules` steht auf `[]`** (Default/inaktiv) — `example-module` via Option aktivieren zum Testen.

#### Modul-Architektur-Kanon (Task 3 — bindend für alle Folge-Module, demonstriert im `example-module`)
Schnellreferenz, ohne den vollen BRIEF (`modules/example-module/BRIEF.md`) lesen zu müssen:
1. **Ordner-Naming:** kebab-case am Modul-Root, PascalCase ab Subordner.
2. **Pflicht-Files am Root:** `manifest.php` + `module.php` (beide lowercase).
3. **KEINE `*.php`-Klassen am Modul-Root** (FS-Safety: vermeidet `module.php`/`Module.php`-Kollision auf macOS/Windows).
4. **`manifest.php` ohne `slug`-Key** (Discovery keyt nach Ordnername — keine zweite Quelle der Wahrheit).
5. **Slug-Pass via Konstruktor-Argument** aus `module.php` (`basename( __DIR__ )`), nicht hartkodiert.
6. **Klassen-Load via PSR-4-Autoloader**, KEIN Hand-Require.
7. **Hook-/Settings-Wiring im Konstruktor** (wordpress.md § 1.1).
8. **Anmeldung nur via `SettingsRegistry`-API** (ADR-1).
9. **„loaded ⟺ active":** ModuleManager lädt `module.php` nur für aktive Module; das Modul prüft die Master-Liste nicht selbst nach.

**Nächster Schritt:** Task 4 (NEU) — **Tab-System / Modul-Verwaltung** (SettingsPage-Modul-Tabs, die registrierte Schemata rendern, + Aktivierungs-Toggle-UI). Voraussetzung für `cache-bridge` (jetzt Task 5). Sprint-Liste oben ist bereits renumbert (alt 4→5 … 10→11). **Offen:** ob Task 4 einen BRIEF braucht (Core-UI wie Task 2, vmtl. § 12.1-exempt) — bei Session-Start klären.

---

#### Historie — vorherige Sessions

### Session 2026-06-10 — Task 2 (Core-Klassen) DONE
- **6 Files geliefert** unter `plugins/depeur-food/src/Core/`: `Settings/SettingsRegistry.php` (ADR-1 Multi-Option-Registry, statisch, port-nah zur Suite), `PostTypeRegistry.php` (ADR-4 Resolver), `Settings/SettingsPage.php` (Core-Settings, manueller Self-POST mit Nonce/PRG), `AdminMenu.php` (Top-Level `depeur-food-settings` + Einstellungs-Submenu), `ModuleManager.php` (Discovery) — **plus Refactor von `Plugin.php`** (Verdrahtung + Delegator).
- **`Plugin::get_supported_post_types()` ist jetzt reiner Delegator** an `PostTypeRegistry::get_instance()->get_supported()`. Der öffentliche Vertrag aus ADR-4 hält unverändert; intern hat sich nur der Owner geändert (Option-Read + Filter + Normalisierung + Memo umgezogen).
- **Filter `depeur_food/post_types` lebt jetzt in `PostTypeRegistry`**, nicht mehr in `Plugin` — mit Provenance-Kommentar („Task 2 umgezogen, kein neuer Hook"), `@since 0.1.0` erhalten.
- **`attachment`** ist im UI versteckt (`get_available()` per `unset`), auf der Daten-Ebene aber erlaubt (`get_supported()` filtert nichts — ADR-4-Treue). Künftiger UI-Bedarf = Filter auf `get_available()`, NICHT das `unset` entfernen.
- **Memo-Ownership:** statischer Memo allein in `PostTypeRegistry`; `flush()` resettet ihn. `SettingsPage::maybe_handle_save()` ruft `flush()` direkt nach `update_option()` (Belt-and-Suspenders, auch bei PRG-Redirect).
- **Smoke grün:** phpcs Exit 0 · `php -l` clean (6 Files) · Activation fehlerfrei · Admin-Menü rendert · debug.log frei von depeur-Zeilen · **Setting-Roundtrip** über die echte SettingsPage (UI-Save „Cocktails" → Option `["post","cocktails"]` → `get_supported()` liest's) · **SMOKE3b** (`get_supported_post_types()` = `["post"]`, Backward-Compat nach Refactor) · Graceful-Default (Option gelöscht → `["post"]`).
- **`wp plugin check` (PCP 2.0.0):** 0 neue Findings aus Task-2-Code; nur bekannte Bucket-1 (.org-Repo-spezifisch: `.editorconfig`, `phpcs.xml.dist`, `load_plugin_textdomain`) und Bucket-3 (`readme.txt`, deferred).
- **ModuleManager:** Discovery-Logik (Konvention `manifest.php` + `module.php`) implementiert, lädt heute aber nichts (`modules/` ist physisch leer) — wartet auf Task 3.

**Nächster Schritt:** Task 3 — `_ExampleModule`, um die ModuleManager-Discovery scharf zu testen (Discovery + Settings-Render via SettingsRegistry + Lazy-Load validieren).

**Offene Frage zu Task 3 / BRIEF.md — ERLEDIGT:** § 12.1 exemptiert „Beispiel-Modul (Tasks 1–3)"; Task 3 lief ohne BRIEF-Pflicht. Der freiwillige Mini-BRIEF wurde dennoch geschrieben, weil das `example-module` die Modul-Vorlage einfriert (cache-bridge erbt sie).

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

### Mid-Session Pause (2026-06-08, vor Task 2 Implementierung)
**Eingefroren VOR Task-2-Code. Nächste Session: direkt mit `SettingsRegistry` starten — diese Sektion ist self-contained.**

**Start-Pointer (Mechanik):** Dateibaum/Pfade = PLAN.md § 2 (`src/Core/`, `src/Core/Settings/`). Pattern-Vorlagen = `plugins/depeur-wp-suite/src/Core/Settings/SettingsRegistry.php` (109 Z., port-nah), `.../SettingsPage.php` (454 Z. — NUR als Muster, schlank nachbauen), `.../AdminMenu.php`, `.../ModuleManager.php`. Konventionen = CLAUDE.md „Konventionen kompakt" + wordpress.md: **echte Tabs**, PSR-4-PascalCase-Dateien, Yoda-Bedingungen, `array()`-Langform, `@since 0.1.0`, Why-Kommentare (§ 2.5), Admin-UI-Doku (§ 6.2).

#### a) Erledigt seit dem Handoff oben
- **Item-3 eingetragen** (wordpress.md § 2.3-Doc-Bug als Open Item): Commit `4c6bac7`.
- **Plugin Check (PCP 2.0.0)** lokal in `tests-cli` installiert + aktiviert (Dev-Tool, NICHT in `.wp-env.json` → siehe Item-4).
- **Bucket-2-Findings in `uninstall.php` gefixt**: globale Vars mit `depeur_food_` geprefixt; begründeter `phpcs:ignore` für `WordPress.DB.DirectDatabaseQuery.DirectQuery` + `NoCaching` (Uninstall = Einmalaufruf, kein Cache-Kontext). Commit `0637348`.
- **`phpcs.xml.dist` gehärtet**: `PrefixAllGlobals` mit Prefixen `depeur_food`, `DEPEUR_FOOD` scharfgeschaltet. `df_` vom Sniff als zu kurz abgelehnt → bewusst nicht gelistet, mit Begründung im File dokumentiert (df_-Shortcodes ab Modul favorites separat). Commit `0637348`.
- **Bucket 1** (.org-Repo-Findings: `hidden_files` .editorconfig, `application_detected` phpcs.xml.dist, `load_plugin_textdomain` discouraged) = akzeptiert, kein Fix (privates Plugin). **Bucket 3** (`readme.txt`/`no_plugin_readme`) = **wird NIE gemacht** (Plugin geht nicht auf wordpress.org).

#### b) Task-2-Scope (approved + ack-bestätigt 2026-06-08)
**Implementierungs-Reihenfolge (verbindlich):**
`SettingsRegistry` → `PostTypeRegistry` → `SettingsPage` → `AdminMenu` → `ModuleManager` → `Plugin::init()`-Verdrahtung (finaler Schritt).

**Vier Klarstellungen (der genaue Vertrag):**
1. **PostTypeRegistry vs `Plugin::get_supported_post_types()`:** PostTypeRegistry wird die kanonische Quelle (liest Option `depeur_food_supported_post_types`, Default `array( 'post' )`, Filter `depeur_food/post_types`, liefert die verfügbaren Public-Post-Types fürs Multi-Select). `Plugin::get_supported_post_types()` delegiert künftig an PostTypeRegistry, statt die Option selbst zu lesen.
2. **ADR-1 Multi-Option-Pattern in SettingsRegistry:** pro Modul eigene Option `depeur_food_{slug}` (autoload=no für Secrets), Master-Liste aktiver Module `depeur_food_modules`. `OPTION_PREFIX = 'depeur_food_'`. Registrierungs-API für Sektionen/Felder pro Tab/Modul.
3. **AdminMenu „schlank" = nur Top-Level + Submenu, KEIN Tab-System heute.** Top-Level-Menü `depeur-food-settings` + ein Submenu, das die SettingsPage rendert. Kein Tab-Routing in dieser Session.
4. **SettingsPage Bare-Minimum = Core-Settings, EIN Setting:** nur „Supported Post Types" als Multi-Checkbox (Optionen aus `get_post_types( array( 'public' => true ) )`), mit Nonce, Save-Handling und Erfolgs-Notice. Kein Multi-Tab, keine weiteren Feldtypen heute.

**LOC-Schätzung:** 440–630 gesamt (5 Klassen + `Plugin.php`-Edit ~20). **SettingsPage ist das Zeit-Risiko** (Suite-Pendant 454 Z. — wir bauen schlank, nicht 1:1).

**`Plugin::init()`-Runtime-Reihenfolge (finaler Schritt):** `PostTypeRegistry` → `ModuleManager::init()` → `if is_admin`: `AdminMenu::register()` + `admin_init`→SettingsPage-Registrierung.

**Fallback-Stufen (bei Zeitdruck):**
- Stufe 1: `ModuleManager` schon als Stub (modules/ ist leer, lädt eh nichts).
- Stufe 2: `SettingsPage` weiter reduzieren.
- Stufe 3: Smoke-Test auf phpcs + activation reduzieren.

#### c) Smoke-Test-Definition Task 2 (unverändert)
- `phpcs --standard=phpcs.xml.dist .` → Exit 0
- `php -l` clean auf allen neuen Files
- `wp plugin check depeur-food` (post-implementation; Bucket 1 + readme.txt-Finding bleiben erwartet)
- WP-Activation ohne PHP-Fehler
- Admin-Menü sichtbar im wp-admin
- `debug.log` frei von depeur-Zeilen
- **Setting-Roundtrip:** Supported Post Types via SettingsPage ändern → `wp option get depeur_food_supported_post_types` liefert die geänderten Werte

#### d) Workflow-Constraints (für nächste Session wiederholen)
- Single-Agent only, KEINE fan-out Sub-Agents.
- Manual approval pro File-Write.
- Bei „Minute 45" ohne 3 von 5 Klassen → Scope-Cut (Fallback-Stufe).
- Bei „Minute 60" muss der Smoke-Test beginnen.
- Test-Env = `tests-cli` (localhost:8889), PHP 8.2.

#### e) Nicht-blockierende Open Items
- **Item-3:** wordpress.md § 2.3 (PSR-4 vs. `class-{name}.php`) → dedizierte Standards-Patch-Session **vor Task 4** (nicht jetzt fixen).
- **Item-4:** Plugin Check (PCP 2.0.0) nur als Dev-Tool im `tests-cli` installiert, NICHT in `.wp-env.json`. Bei `wp-env destroy`/Neuaufbau nachinstallieren: `wp-env run tests-cli wp plugin install plugin-check --activate`.

## Open Questions / Open Items
- **OQ-1:** Live-Konsumenten der Legacy-REST-Routes `wl/v1/posts` / `wrm/v1/rating/*`? → klären vor Task 11+.
- **OQ-2:** Newsletter-Provider-Scope (nur Flodesk vs. Multi-Provider von Tag eins)? → klären vor Task 7.
- **OQ-3:** Verwendung von `mu-plugins/` (aktuell leer)?
- **Item-1:** SSH-Alias `runcloud-test` verfügbar (Linux Testserver, PHP 8.4.20, User `runcloud`, verifiziert in dieser Session). Test-WebApp ist `/home/runcloud/webapps/Food-Blog_Template/` — bestehendes Test-WordPress, freigegeben für Phase-B-Remote-Tests. Lese-Operationen sind jederzeit zulässig; Schreibe-/Push-Operationen erst nach explizitem Push-Approval pro Feature.
- **Item-2 (erledigt 2026-06-08):** Beide Pflicht-Edits aus PLAN.md § 6 sind in `wordpress.md` umgesetzt (§ 1.1 Multi-Option, § 4.5 Autoload); zusätzlich § 4.2 ↔ ADR-3 + Z.21-Glitch gefixt (`df09c5c`). Phase B entsperrt. Siehe „Last Session Handoff".
- **Item-3:** `wordpress.md` § 2.3 fordert `class-{name}.php` für Klassendateien, die frozen PSR-4-/Suite-Architektur nutzt aber PascalCase (`src/Core/Plugin.php`). Stale-Standard analog zum gelösten § 4.2 ↔ ADR-3. phpcs ist bereits via FileName-Sniff-Exclude (`src/*`, `modules/*`) darauf eingestellt; die Bibel selbst wurde NICHT eigenmächtig editiert. Fix in dedizierter Standards-Patch-Session **vor Task 4** (Befund: Last Session Handoff 2026-06-08).
- **Item-4:** Plugin Check (PCP 2.0.0) nur als Dev-Tool im `tests-cli` installiert, NICHT in `.wp-env.json` gemappt. Bei `wp-env destroy`/Neuaufbau nachinstallieren: `wp-env run tests-cli wp plugin install plugin-check --activate`. (Eingeführt 2026-06-08, Task-1d.)

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
