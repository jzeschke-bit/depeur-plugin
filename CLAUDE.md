# CLAUDE.md — Depeur Food Suite (Working State)

## Projektziel
Plugin `depeur-food` (modular, Toggle-Pattern wie `depeur-wp-suite`) + Child-Theme `kadence-child`, gemeinsam Basis für die Content-Sites einfachandersessen.de (Standard-`post`) und alkipedia.com (CPT für Cocktails). Architektur post-type-agnostisch, ACF nur als Discovery-Quelle, nicht als Runtime-Dependency.

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
Tasks 1–4 (Bootstrap, Core-Klassen, Beispiel-Modul, Tab-System) sind abgeschlossene Strukturarbeit (§ 12.1-exempt, Core-UI ohne BRIEF). Task 4b (Modul-Toggle-UI) ist optionale UI, **nicht-blockierend für Task 5**. Ab dem ersten geschäftslogik-tragenden Modul (`cache-bridge`, Task 5) zwingt § 12 (Pre-Implementation-Review) jeweils zwei Sub-Tasks: erst BRIEF schreiben + freigeben lassen, dann implementieren.

1. Plugin-Bootstrap (`depeur-food.php` + Konstanten + Autoloader + Helper + Activation/Deactivation/Uninstall + Textdomain + `phpcs.xml.dist`). ✓ DONE
2. Core-Klassen (`Plugin`, `Activation`, `AdminMenu`, `ModuleManager`, `PostTypeRegistry`, `Settings/SettingsRegistry`, `Settings/SettingsPage`, `Helpers/Autoloader`). ✓ DONE
3. Beispiel-Modul `example-module` (Discovery + Lazy-Load + SettingsRegistry-Anmeldung validiert; Modul-Architektur-Kanon eingefroren, s. Handoff). ✓ DONE
4. **Tab-System (SettingsPage-Modul-Tabs).** SettingsPage rendert Core-Tab + je einen Tab pro aktivem Modul aus den `SettingsRegistry`-Schemata, inkl. Modul-Save-Handler (Slug-Whitelist vor Nonce, ADR-1 autoload, Password-Preserve). ✓ DONE (Commit `d41106b`, Smoke grün inkl. echtem HTTP-Roundtrip + Negativ-Test).
4b. **Modul-Aktivierungs-Toggle-UI:** UI, die `depeur_food_modules` schreibt (Master-Liste-Editor, validiert gegen `get_discovered_modules()` — gegenläufige Whitelist zum Tab-Routing). Eigener Feature-Komplex mit Aktivierungs-Semantik, bewusst aus Task 4 ausgeklammert. **Nicht-blockierend für Task 5** — Module bis dahin via `wp option update depeur_food_modules '["slug"]' --format=json` aktivierbar. Kein BRIEF (Core-UI, § 12.1-exempt).
5. Modul `cache-bridge` (erster BRIEF-pflichtiger Task, § 12.1 geschäftslogik-tragend) — **BRIEF APPROVED, CODE-PHASE PENDING** (BRIEF.md v1.0 `8e7dae4`, Session 2026-06-12c). `modules/cache-bridge/BRIEF.md` (871 Z., 18 Sektionen, Schema 1.1) ist frozen Architektur (Sketch entfernt `9704258`). **Code-Reihenfolge:** shared Value-Objects (`Purge_Context`/`Purge_Result`/`Provider_Catalog`) + `Transport` (4. shared File, Executor) → `Provider_Interface` → Listener + `Purge_Runner` + `module.php`/`manifest.php`-Skelett → vier Provider (LogOnly/RunCloud/BunnyCDN/Cloudflare, eigenständig, **kein** Suite-Bridge) → Pause-Trio (Variante C: Controller/Queue/AdminBar) + Assets → Admin/Settings → Smoke + MockedHttpResponses. ~19 Files / ~1.700–1.800 LOC / **5–6 Code-Sessions**. **Smoke-Step-0 ZWINGEND:** Autoloader-`Word_Word`-Auflösung verifizieren VOR Provider-Code.
6. Modul `schema-engine` — 6a) `BRIEF.md` · 6b) implementieren (migriert `category-schema` + `alkipedia/rank-math.php`, post-type-agnostisch, ACF-frei).
7. Modul `favorites` — 7a) `BRIEF.md` · 7b) implementieren (REST-Endpoint mit Nonce, Shortcodes, WPRM-Integration, `register_post_meta`-Like-Counter).
8. Modul `newsletter` — 8a) `BRIEF.md` (klärt OQ-2) · 8b) implementieren (the_content-Inserter, Custom-Meta-Box, Flodesk-Provider).
9. Modul `recipe-extras` — 9a) `BRIEF.md` · 9b) implementieren (Conditional WPRM-Asset-Enqueue, Pinterest-Schema).
10. Theme-Bootstrap — 10a) `BRIEF.md` für Theme-Architektur (analog zu Modul-Brief) · 10b) `themes/kadence-child/` neu anlegen.
11. Theme-Migration — 11a) `BRIEF.md` mit Migrations-Inventar pro `inc/`-File · 11b) Customizations aus `alkipedia` portieren.

## Last Session Handoff
**Stand: 2026-06-12 (fünfte Claude-4.8-Session, „c"). Task 5 (cache-bridge): `BRIEF.md` v1.0 APPROVED + committed (`8e7dae4`), Sketch entfernt (`9704258`). Code-Phase beginnt NÄCHSTE Session. Diese Session KEIN Code (§ 12.3 erfüllt: BRIEF vor Implementierung).**

### Session 2026-06-12 (c) — Task 5 (cache-bridge) BRIEF.md v1.0 FREIGEGEBEN (KEIN Code)
- **Ablauf:** Pre-Decisions-Triage (11 Pre-Decisions + 4 Recon-Caveats + Lazy-vs-Eager-Entscheidung, thematisch in Gruppen A–F bestätigt) → Volltext-`BRIEF.md` in 6 Block-Reviews (B1–B6, je 2–4 Sektionen, pro Block Approval) → Approval → Commit. Per-Block-Approval + Mid-Session-Checks gehalten.
- **Ergebnis:** `modules/cache-bridge/BRIEF.md` (871 Z., 18 Sektionen, Schema 1.1) = frozen Architektur. `BRIEF-SKETCH.md` (TEMPORARY) Lifecycle-Ende → entfernt (`9704258`, via `git log --all` rekonstruierbar).
- **3 Commits heute:** `9cd9131` (Domain-Fix) · `8e7dae4` (BRIEF.md v1.0) · `9704258` (Sketch-Entfernung). Vorsession-Vorarbeit: `74abeca` `0a87e58` `d3ea4be` `c0defad`.
- **Schlüssel-Festschreibungen (in der Triage über die Sketch hinaus entschieden):**
  - **Lazy Provider-Instanziierung** (Frontend-Footprint=null): statischer `Provider_Catalog::get_catalog()` im Admin, Provider-Instanzen erst bei Purge (Config-Injection, **side-effect-free Konstruktoren** = Anti-Pattern in § 14).
  - **`register_providers`-Filter liefert FQCN-Strings** (nicht Instanzen) — Pflicht-Validation `class_exists`+`is_subclass_of` (Code-Injection-Schutz).
  - **Vierter shared File `src/Cache/Transport.php`** (Executor: Retry-Matrix + Timeout-Filter + 429-Cap + Cred-Redaction, DRY) neben Purge_Context/Result/Provider_Catalog.
  - **`Hooks/Purge_Runner.php`** als §-18-Lücken-Fund (Provider-Iteration brauchte eigene Klasse — weder Listener noch Pause/Controller passend; ehrlich beim Schreiben disclosed).
  - **Timeout via Filter** `depeur_food/cache/timeout` (nicht Constant); 429-`RETRY_AFTER_CAP_SECONDS=3` + Debounce-30s + Auto-Resume-24h + Bulk-5×-Multiplikator alle als dokumentierte/filterbare MVP-Magic-Numbers.
  - **`code`/`success`-Dreiteilung** in `Purge_Result` (erreicht&akzeptiert / erreicht&abgelehnt / nie-erreicht).
  - **Full-Purge nur operator-initiiert** (Resume-Modal Option 2); Bulk-Überlauf-Default = skip+loud-log (kein Auto-Nuke); leere `urls` = No-Op (nicht Full-Purge).
  - **URL-Scope post-type-agnostisch** (ADR-4): `get_object_taxonomies()` statt hartem category/post_tag; eigener `purge_urls`-Filter; Default-Set + bewusste Auslassungen (Author/Datum/Pagination) explizit.
- **Side-Quest:** Domain-Drift-Korrektur (`einfachanders.es`→`einfachandersessen.de`, `alkipedia.de`→`alkipedia.com`) in CLAUDE.md ×3 + PLAN.md ×1 (faktische Korrektur im ADR-4-Kontext, kein ADR-Rewrite); Memory-Files geprüft = sauber. Commit `9cd9131`.
- **Nächster Schritt:** Code-Phase Teil 1 — shared Value-Objects + `Transport` + `Provider_Interface` + `module.php`/`manifest.php`-Skelett. **Smoke-Step-0 ZWINGEND VOR Provider-Code:** `class_exists( 'Depeur\\Food\\Modules\\CacheBridge\\Providers\\Provider_LogOnly' ) === true` — sonst Autoloader-`Word_Word`-Auflösung fixen.

### Session 2026-06-12 (b) — Task 5 (cache-bridge) Architektur-Vorarbeit (BRIEF-Skizze, KEIN Code)
- **4 Doku-Commits:** `74abeca` Splitting-Strategie · `0a87e58` ADR-3-Supersede + §2-Layout · `d3ea4be` BRIEF-Skizze (`0a79465` war Vorstand). **Kein Code** — § 12.3 (BRIEF vor Implementierung).
- **Architektur-Updates eingearbeitet (User-Vorgaben):**
  - **`Provider_Suite_Bunny` → `Provider_BunnyCDN` eigenständig** (kein Suite-`class_exists`-Bridge). Grund: `cache-bridge` gehört zum künftigen Plugin **Depeur Speed**; Suite-Dependency würde den Plugin-Split sabotieren. Neu: CLAUDE.md › Architecture Notes › **Plugin-Splitting-Strategie** (food/speed/features) + **ADR-3-Supersede-Banner** in PLAN.md.
  - **Pause-Mechanismus (Variante C):** Queue + Admin-Bar + Resume-Modal + 24h-Auto-Resume — eigene BRIEF-Sektion, **orthogonal** zum Debounce (Debounce im Listener vor `do_action`, Pause in cache-bridge nach `do_action`).
- **Recon Tier 1+2 — Schlüsselfund F1:** RunCloud ist **NICHT** „analog zu Cloudflare/Bunny" (ADR-3-Annahme kippte): lokaler Nginx-`PURGE` (keine Creds, Env-Detection via HTTP-Probe `X-RunCache-Type` + FS-Fallback) vs. externe CDN-APIs (Creds + Zone). Hätte den BRIEF unbemerkt verbogen. Quellen: Suite-`BunnyApi`/`PurgeService` (nur Inspiration, kein Bridge), Vendor bunnycdn/runcloud-hub/wp-rocket.
- **`BRIEF-SKETCH.md`** (`modules/cache-bridge/`, **TEMPORARY** — wird bei BRIEF-Finalize gelöscht/umbenannt): 18-Sektion-Struktur + OPEN-DECISIONS-Tracking + **11 Pre-Decisions** (User-Vorschläge, Claude-approved + **4 Recon-Caveats**: 429-Retry-Cap ≤3s, Archive-URL-Pagination-Bound, Queue-Option-Write-Race, Full-Purge-Fallback destruktiv→opt-in).
- **§ 2-Layout-Korrektur (Recon-Fund):** flache `Provider_*.php` am Modul-Root verletzten FS-Safety → Subordner-Layout `Hooks/` + `Providers/` (PascalCase) + `Pause/`.
- **Nächster Schritt:** Session-Start mit „lies CLAUDE.md + `modules/cache-bridge/BRIEF-SKETCH.md`" → 11 Pre-Decisions + 4 Caveats bestätigen → **Volltext-`BRIEF.md`** nach 18-Sektion-Struktur schreiben → User-Approval → DANN Code (Purge_Context/Purge_Result zuerst).

### Session 2026-06-12 — Task 4 (Tab-System) DONE
- **2 Code-Files** (Commit `d41106b`): `src/Core/Settings/SettingsPage.php` (182 → 673 Z., 7 neue Methoden) + `src/Core/AdminMenu.php` (nur Stale-Kommentar „KEIN Tab-System" korrigiert). CLAUDE.md-Handoff separat (Commit B).
- **Was SettingsPage jetzt kann:** Core-Tab (Post-Types, **verbatim aus Task 2 verschoben** → Backward-Compat trivial) + je ein Tab pro aktivem Modul aus den `SettingsRegistry`-Schemata. Routing über `?tab=` (Default `core`). Neue Methoden: `get_allowed_tabs`, `get_active_tab`, `render_tab_nav`, `render_core_tab`, `render_module_tab`, `render_field`, `handle_module_save`, `redirect_with_flag`.
- **Implementiert in 4 Approval-Blöcken** (B1 Tab-Nav+Routing · B2 Feld-Renderer · B3 Modul-Tab-Render · B4 Modul-Save), pro Block `php -l` + phpcs (Policy ab B1 nachgezogen).
- **KEINE Registry-Erweiterung nötig:** `SettingsRegistry::register($slug,$tab_label,$fields,$desc)` + `get_schemas_for_active_modules()` trugen den Tab-Mechanismus schon (Task 2/3).
- **Zwei gegenläufige Whitelists (bindend):** Tab-Routing (GET) erlaubt `['core'] + get_active_module_slugs()`; Modul-Save (POST) erlaubt `get_active_module_slugs()` **ohne** `core`. `core` hat den eigenen Save-Zweig.
- **Security-Reihenfolge `handle_module_save` (nicht umstellen):** cap → Slug-Read → `sanitize_key` → Whitelist → **DANN** `check_admin_referer` (Nonce-Action slug-gebunden `depeur_food_save_module_{slug}`). Ungültiger Slug fällt VOR der Nonce durch. Defense-in-Depth-`return` im Reject-Zweig (Success-Zweig ohne `return` — phpcs-redundant, dokumentiert).
- **ADR-1 autoload:** Feld mit `autoload=false` (Secret) → ganze Option `autoload=false`, sonst `true`. **Password-Preserve:** leerer/whitespace-Submit behält den gespeicherten Wert (sanitize_field kennt nur den NEW value, die Behalten-Entscheidung lebt in `handle_module_save`).
- **Feld-Renderer `render_field( $field, $value, $name )`:** vier Typen (checkbox/text/select/password), unbekannter Typ → `_doing_it_wrong` + Skip (kein Fatal, kein bogus Key — Save spiegelt den Skip). `$name` vom Aufrufer (Slug-Kontext).
- **Smoke grün:** `php -l` · phpcs Exit 0 (B1-Finding „assoc-Array mehrzeilig" früh gefixt) · `wp plugin check` 0 neue Findings · **Pre/Post-Vergleich** `depeur_food_supported_post_types` `["post"]`→`["post"]` + `depeur_food_modules` `[]`→`[]` **identisch (keine Daten-Migration)** · **Browser-Roundtrip** (example-module Checkbox gespeichert → `{"example_enabled":true}`, autoload=on) · **Negativ** (Hidden-Slug auf `evil-module` manipuliert → Whitelist-Ablehnung vor Nonce → core+`df_error`-Notice, **keine** `depeur_food_evil-module`-Option) · debug.log frei.
- **Zwei latente Funde dokumentiert** (NICHT in Task 4 gefixt → Standards-Patch-Backlog): Item-5 (§ 12.1 Stale-Reference) + Item-6 (numeric-select-Asymmetrie in `sanitize_field`).
- **`depeur_food_modules` zurück auf `[]`** (Test-Cleanup), Env-State = Pre-Snapshot.

**Nächster Schritt:** Task 5 — `cache-bridge` (erster BRIEF-pflichtige Task). Standards-Patch-Backlog (Items 3/5/6) **erledigt 2026-06-12** → Reihenfolge jetzt: **(1) BRIEF.md schreiben + freigeben → (2) Code.** Vorher Task 4b (Modul-Toggle-UI) NICHT nötig — cache-bridge via `wp option` aktivierbar.

---

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

**Nächster Schritt (erledigt 2026-06-12):** Task 4 (Tab-System) abgeschlossen — s. Session 2026-06-12 oben. BRIEF-Frage geklärt: § 12.1-exempt, ohne BRIEF gelaufen. Toggle-UI in Task 4b ausgelagert.

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
- **Item-3:** `wordpress.md` § 2.3 fordert `class-{name}.php` für Klassendateien, die frozen PSR-4-/Suite-Architektur nutzt aber PascalCase (`src/Core/Plugin.php`). Stale-Standard analog zum gelösten § 4.2 ↔ ADR-3. phpcs ist bereits via FileName-Sniff-Exclude (`src/*`, `modules/*`) darauf eingestellt; die Bibel selbst wurde NICHT eigenmächtig editiert. ✓ **ERLEDIGT 2026-06-12** (Commit `7ea65d9`): § 2.3 + § 1.1-Architektur auf PSR-4/PascalCase synchronisiert (Z. 11/13/14/65/68). (Befund: Last Session Handoff 2026-06-08.)
- **Item-4:** Plugin Check (PCP 2.0.0) nur als Dev-Tool im `tests-cli` installiert, NICHT in `.wp-env.json` gemappt. Bei `wp-env destroy`/Neuaufbau nachinstallieren: `wp-env run tests-cli wp plugin install plugin-check --activate`. (Eingeführt 2026-06-08, Task-1d.) *(Operationaler Eintrag — NICHT Teil des Standards-Patch-Backlogs.)*
- **Item-5:** ✓ **ERLEDIGT 2026-06-12** (Commit `45aa3d1`): `wordpress.md` § 12.1 Stale-Reference „ab Task 4 (cache-bridge)" → auf Task 5 korrigiert, renumber-stabil formuliert (semantisches Kriterium „geschäftslogik-tragend" als Hebel, Task-Nummer nur Beispiel) + Task 4 (Tab-System/Core-UI) in die exempt-Liste aufgenommen.
- **Item-6:** ✓ **ERLEDIGT 2026-06-12** (Commit `03b3780`): `SettingsRegistry::sanitize_field()` select-Zweig auf string-symmetrischen Vergleich umgestellt (`array_map( 'strval', … )` + `(string)`-Cast mit `is_scalar`-Guard, Rückgabe des validierten Strings). Smoke: 4 Vektoren grün (numeric `"1"`→`'1'`, string-Regression, invalid→`''`, out-of-range→Default). (Befund: Task 4, 2026-06-12.)
- **Item-7:** `src/Support/SuiteCompat.php` (geplant in PLAN.md § 2 als „graceful Fallback wenn Suite nicht aktiv") wird durch die No-Suite-Dependency-Regel der Plugin-Splitting-Strategie fragwürdig — `cache-bridge` bridged nicht mehr zur Suite. Klärung: SuiteCompat ganz streichen vs. für andere (Nicht-Cache-)Suite-Interop behalten. **Nicht cache-bridge-Scope** (kein Drive-by). (Befund: Task-5-Recon, 2026-06-12.)

*(Reihenfolge-Hinweis für Items 8–11: ALLE sind POST-cache-bridge. Reihenfolge bleibt: 1) cache-bridge Code-Phase (5–6 Sessions) → 2) cache-bridge Smoke + Live-Aktivierung → 3) ACF-Discovery (Item-8) → 4) `meta-registry`-Modul → 5) Schema-Engine → 6) später Theme-Templates (Item-9) vor Task 11. NICHT vor cache-bridge-Code anfangen.)*

- **Item-8: ACF-Discovery-Session (vor Task 6 schema-engine, NICHT vor cache-bridge-Code).** Die ACF-Migration-Note (Architecture Notes) ist abstrakt; jetzt operativ: dedizierte Discovery-Session (~30–60 Min), Bestandsaufnahme aller Live-Felder auf einfachandersessen.de + alkipedia.com → `_references/acf-discovery.md` (Meta-Keys, Typen, Post-Types). Danach `meta-registry`-Modul als eigener Task (vermutlich Task 6; schema-engine rutscht auf Task 7+). **Offene Sub-Entscheidung (blockiert ALLE Feld-Konsumenten — Schema-Engine, Newsletter, …):** Editor-UI interim bei ACF behalten, oder native MetaBoxen/Gutenberg ab `meta-registry`? In/direkt nach Discovery entscheiden. (Befund: Session 2026-06-12c.)
- **Item-9: Theme-Template-Architektur für alkipedia-Portierung (vor Task 11).** Task 11 „alkipedia portieren" ist zu grob. Drei Legacy-Templates brauchen eigene Spezifikation: (1) Rezeptkategorie-Seiten (Multi-Taxonomie-Query + Pagination), (2) Favoriten-Archiv (aktuell Cookie-basiert → Migration zu localStorage/User-Meta), (3) „Was koche ich heute" (AJAX-Filter). Plus übergreifender Plugin-vs-Theme-Schnitt: Schema/Daten = Plugin, Layout/Query = Theme; Favoriten-Daten = Plugin, Favoriten-Seite = Theme oder Shortcode-auf-normaler-Seite? Klärung vor Task 11. (Befund: Session 2026-06-12c.)
- **Item-10: Performance-Themen explizit als Phase-2-deferred.** Critical CSS / JS-Delay / Bloat-Reduction bewusst NICHT im aktuellen Sprint: (a) WP-Rocket-Ersatz NICHT im Scope (Page-Cache via RunCache/Cloudflare), (b) Theme-Performance erst nach Theme-Migration (Task 11+), (c) Critical CSS später ggf. als eigenes **Depeur-Speed**-Modul (passt zur Splitting-Strategie). Dokumentiert, damit die „warum kein Critical CSS?"-Frage in 6 Monaten eine explizite Antwort hat. (Befund: Session 2026-06-12c.)
- **Item-11: Asset-Convention plugin-weit hochziehen.** Im cache-bridge-BRIEF (§ 8.4) als modul-lokale Convention festgehalten: **Admin-Assets** jQuery erlaubt (nicht Pflicht, Heuristik: nur wenn >30 % Code-Reduktion); **Frontend-Assets Vanilla strikt** (Kadence ist Frontend-jQuery-frei — ~30 KB Bloat/Page-Load vermeiden); Resume-Modal = Vanilla ohne Build-Step. Sollte als plugin-weite Konvention nach „Konventionen kompakt" (o. ä.) hochgezogen werden, nicht cache-bridge-lokal bleiben. (Befund: Session 2026-06-12c.)

### Standards-Patch-Session Backlog — ✓ ABGESCHLOSSEN (2026-06-12)
Alle drei Items in einer Standards-Patch-Session vor Task 5 erledigt (Reihenfolge klein→groß, Code zuletzt):
1. **Item-3** ✓ — § 2.3 + § 1.1 PSR-4-Sync (Commit `7ea65d9`).
2. **Item-5** ✓ — § 12.1 Renumber-Fix (Commit `45aa3d1`).
3. **Item-6** ✓ — `sanitize_field` string-Symmetrie + Smoke (Commit `03b3780`).

## Architecture Notes for Future Sessions
Vorausschauende Architektur-Hinweise (kein Open-Item-Backlog — werden zur richtigen Zeit sichtbar):
- **`SettingsPage::render_field()` → `Field_Renderer`-Extraktion:** Der natürliche Split-Kandidat, sobald Feldtypen jenseits der heutigen vier (checkbox/text/select/password) dazukommen ODER der Core-Tab eigene Custom-Renderer braucht. Heute (Task 4) nicht nötig — eine Switch-Case-Methode reicht. Aber beim **nächsten Modul mit neuem Feldtyp** (z. B. textarea, multiselect, color-picker) zuerst die `Field_Renderer`-Extraktion erwägen, bevor `render_field()` mit weiteren Switch-Cases wächst. (Kontext: SettingsPage ist nach Task 4 bei 673 Z. — kein Bloat, aber render_field ist die Wachstumsfuge.)

### Plugin-Splitting-Strategie (langfristig — ab cache-bridge/Task 5 bindend)

Aktuell: **ein** Plugin (`depeur-food` / Depeur Food Suite). Die Depeur Suite wird langfristig durch die Depeur Food Suite ersetzt.

**Mittelfristige Zielarchitektur — Split in drei Plugins:**
- **Depeur Food** — Content/Schema/Newsletter/Favoriten (food-spezifisch).
- **Depeur Speed** — `cache-bridge`, Performance, Edge-Integration (BunnyCDN, Cloudflare, RunCloud).
- **Depeur Features** — nicht-food-spezifische Features.

**Konsequenzen für die aktuelle Architektur (jetzt schon einzuhalten):**
- **Module dürfen KEINE direkten Cross-Module-Dependencies haben.** Cross-Module-Kommunikation ausschließlich via Hook-Fassade (Action/Filter). Direkter Klassen-Import von Modul A → Modul B sabotiert die Splitting-Strategie.
- **Settings-Namespacing bleibt pro Modul** (`depeur_food_{slug}` via ADR-1). Beim Split werden Optionen umbenannt — klare Mapping-Strategie bleibt möglich.
- **Modul-Manifest könnte zukünftig dokumentieren**, zu welchem Plugin-Cluster ein Modul gehört (food/speed/features). Heute nicht relevant, aber Vorbereitung.
- **Shared Core** (`SettingsRegistry`, `ModuleManager`, `AdminMenu`, `Plugin`-Klasse, `Purge_Context`, Helpers) wird beim Split entweder dupliziert oder als gemeinsame Library extrahiert. Empfohlene Lösung: ein `depeur-core`-Library-Plugin oder Composer-Package, das alle drei nutzen. Heute **KEIN** Action-Item, nur Future-Note.
- **Konkretes Beispiel (bindend ab Task 5):** `cache-bridge` gehört semantisch zu Depeur Speed. Der BunnyCDN-Provider darf **NICHT** die Suite-`BunnyApi` referenzieren (auch nicht `class_exists`-gegated) — sonst hätte Depeur Speed beim Split eine Suite-Abhängigkeit. Daher: eigenständiger `Provider_BunnyCDN` mit eigenen Credentials statt des ursprünglich in ADR-3 geplanten `Provider_Suite_Bunny`.

**Merksatz:** Bei jeder neuen Modul-Implementierung Cross-Modul-Kontakte explizit hinterfragen — muss die Verbindung direkt sein, oder kann sie via Hook laufen?

### ACF-Migration-Strategie (vor Schema-Engine relevant)

ACF erfüllt heute zwei getrennte Funktionen:
1. **Datenschicht:** Definiert Custom Fields, schreibt sie in `wp_postmeta`.
2. **Editor-UI:** Rendert Editier-Felder im wp-admin (MetaBoxes/Sidebar).

ADR-4 (`register_post_meta` statt ACF) löst nur Schicht 1. **Schicht 2 ist eine separate, noch offene Architektur-Entscheidung.**

**Migrations-Schritte (in Reihenfolge):**
- **Discovery-Session** (Vorarbeit, vor erstem Konsumenten-Task): Bestandsaufnahme aktueller ACF-Field-Groups auf einfachandersessen.de und alkipedia.com. Ergebnis: `_references/acf-discovery.md` mit Field-Definitions (Meta-Keys, Typen, Post-Types).
- **Modul `meta-registry`** (eigener Task, BRIEF-pflichtig): ruft `register_post_meta` für jede Field-Definition aus Discovery auf, mit `show_in_rest => true`. **Koexistiert zunächst parallel zu aktivem ACF** (selbe `wp_postmeta`-Tabelle, kein Daten-Konflikt).
- **Editor-UI-Modul** (eigener Task, Option-Entscheidung offen):
  - Option 1: ACF als Editor-Tool aktiv lassen (ADR-4 teilerfüllt).
  - Option 2: Klassische MetaBoxes nachbauen (vmtl. für Content-Pipeline-Workflow ausreichend).
  - Option 3: Block-Editor-Sidebar mit Gutenberg `useEntityProp` (modernster Ansatz).
- **ACF-Deaktivierung** (Deployment-Schritt, kein Sprint-Task): erst NACH meta-registry + Editor-UI live + Smoke-Verifikation aller Konsumenten + Backup.

**Field-Namen-Identität (kritisch):**
- meta-registry verwendet EXAKT die Meta-Keys, die ACF heute nutzt (aus Discovery).
- Daten in `wp_postmeta` werden NICHT migriert — bleiben identisch.
- Konsumenten lesen weiterhin via `get_post_meta()` oder REST-API.
- ACF-interne Tracking-Meta-Keys (z. B. `_field_xyz`) werden nach ACF-Deaktivierung obsolet, schaden aber nicht.

**Sprint-Position-Vorschlag** (konkrete Task-Nummern offen, bis Vor-Reihenfolge geklärt): Discovery-Session (zwischen Task 5 und meta-registry-Task) → Task: meta-registry-Modul → Task: Editor-UI-Modul (Option 1/2/3 in dedizierter Architektur-Session entschieden) → erst danach Schema-Engine und andere Konsumenten.

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
- **Lokal:** wp-env auf `localhost:8888`, PHP 8.2. Premium-Plugins in `_premium/` gemappt (Kadence Pro, Kadence Blocks Pro, WPRM Premium, Rank Math Pro, Smush Pro, ACF, kadence-blocks). Test-Inhalt: einfachandersessen.de-Posts (Standard-`post`, ACF-Felder nicht überall befüllt → Graceful-Degradation explizit testbar).
- **Remote (Test-Server):** SSH-Alias `runcloud-test` (Linux testserver, PHP 8.4.20, User `runcloud`). Test-WebApp: `/home/runcloud/webapps/Food-Blog_Template/`. Lese-Operationen jederzeit (`ssh runcloud-test "wp ... --path=/home/runcloud/webapps/Food-Blog_Template"`); Schreibe-/Push-Operationen (rsync, `wp plugin activate`, etc.) erst nach explizitem Push-Approval pro Feature.
- **PHP-Versions-Diff:** Lokal 8.2, Remote 8.4. Bei Remote-Tests `tail -f` auf das PHP-Error-Log laufen lassen, um 8.4-Deprecations zu erwischen, die lokal nicht auftauchen. Beispiel-Befehle siehe PLAN.md § 5.

## Operational Notes
- **Plugin-Updates NIE über wp-admin** für Plugins, die in `.wp-env.json` als lokale Pfade (`./_premium/...` oder `./plugins/...`) referenziert sind. Stattdessen Terminal-Workflow: `wp-env stop` → `rm -rf _premium/{plugin}` → `curl`/`unzip` der frischen Version → `wp-env start --update`. Sonst landet der Ordner in einem Halb-Zustand und WordPress kann das Plugin nicht mehr finden.
- **Working Environment ist `localhost:8889` (tests-environment), nicht `localhost:8888`.** Aller importierter Content liegt in der tests-DB. Bei wp-cli-Befehlen explizit `wp-env run tests-cli wp ...` verwenden, nicht `wp-env run cli wp ...`. Die tests-Environment in wp-env ist offiziell deprecated und wird irgendwann entfernt — Migration auf `localhost:8888` (development) ist mittelfristig nötig, aber kein aktueller Blocker. Migration = `wp db export` aus `tests-cli` + `wp db import` in `cli` + Such-/Ersetzen der Site-URLs.
