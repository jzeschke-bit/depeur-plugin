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

## Aktueller Sprint — Live-First-Strategie (final, P0 abgeschlossen 2026-06-13)

**Strategie-Wechsel 2026-06-12 (Session d):** Nach Recon-Re-Validation der Legacy-Funktionalität (→ `_references/legacy-inventory.md`) Umstellung von „Architektur-Sauberkeit zuerst" auf **Live-First** (sichtbare/sofort-testbare Features zuerst). `cache-bridge` ist Infrastruktur-Sahne (unsichtbar, nur live testbar) → von Position 5 auf **Position 11** verschoben; BRIEF v1.0 liegt bereits (`8e7dae4`), Code deferred. Migrations-Phase kommt zuerst.

**Fundament-Struktur ✓ DONE (§ 12.1-exempt, ohne BRIEF):** Tasks 1–4 — Plugin-Bootstrap · Core-Klassen (`Plugin`/`ModuleManager`/`PostTypeRegistry`/`SettingsRegistry`/`SettingsPage`/`AdminMenu`/`Activation`/`Autoloader`) · Beispiel-Modul `example-module` (Modul-Kanon eingefroren) · Tab-System (Commit `d41106b`). Task 4b (Modul-Toggle-UI) optional, nicht-blockierend — Module via `wp option update depeur_food_modules '["slug"]' --format=json` aktivierbar.

Ab dem ersten geschäftslogik-tragenden Modul gilt § 12 (Pre-Implementation-Review): erst `BRIEF.md` schreiben + freigeben, dann implementieren.

**Live-First-Sprint P0–P11 — final (P0 abgeschlossen 2026-06-13):**

- **P0 · Recon-Lücken schließen** ✓ **ABGESCHLOSSEN** (2026-06-13, außerhalb Code-Session geklärt): **CPT-/Taxonomie-Registrierungs-Quelle = ACF (free, nicht Pro)** — wird vom `post-type-registry`-Modul (P3) aus ACF ausgelesen + ins Plugin repliziert. **OQ-1 obsolet** durch E8 (Legacy-REST-Routen 1:1 inkl. Bugs → `rest-legacy`/P10; kein externer App-Audit mehr nötig). Entsperrt P3 + P10.
- **P1 · ACF-Discovery** ✓ **ABGESCHLOSSEN** (2026-06-13): `_references/acf-discovery.md` — 35 Kandidaten-Felder (30 UI + 4 Code + 1 Orphan), empirisch via wp-cli validiert. Fundament-Recon für meta-registry steht.
- **P2 · `meta-registry`-Modul** (BRIEF-pflichtig) ✓ **IMPLEMENTIERT** (2026-06-14, `704d3c6`/`3b83b4d`/`56af7ea`): 34 Discovery-Felder via `register_post_meta`/`register_meta('user')`/`register_term_meta` + `acf_add_local_field_group` (Key-Reuse-Override), `show_in_rest`, Doppel-Owner-Pattern. ACF free Hard-Dependency + read-only `html`-Feldtyp als Core-Vorarbeit. Smoke grün. **Entsperrt ALLE Feature-Module.** *(Deployment-TODO pro Site: ACF-UI-Field-Groups manuell löschen NACH Render-Verifikation, BRIEF § 3.6.)*
- **P3 · `post-type-registry`-Modul** (BRIEF-pflichtig, NEU aus E7): CPT-/Taxonomie-Registrierung ins Plugin + Settings-UI für generische CPT-Konfiguration. CPT UI später deaktivieren. Voraussetzung P0.
- **P4 · `favorites`-Modul** (BRIEF-pflichtig): höchste Live-Sichtbarkeit. REST+Nonce, **Cookie→localStorage-Migration** (Like-Counter `register_post_meta`), CPT filterbar `depeur_food/favorites/post_types`. WPRM soft-dep (E2). Parallel-Migration (E5).
- **P5 · `newsletter`-Modul** (BRIEF-pflichtig): the_content-Inserter + Custom-Meta-Box, **Flodesk-only mit dünner Provider-Naht** (`Providers/Flodesk.php`-Klassen-Trennung, E4), Nonce nachziehen. Big-Bang-Migration (E5).
- **P6 · `schema-engine`-Modul** (BRIEF-pflichtig): migriert `category-schema` + `alkipedia/rank-math.php`. **Rank Math hard-dep (E1)**, WPRM soft-dep (E2). Big-Bang (E5).
- **P7 · `language-selector`-Modul** (BRIEF-pflichtig): manuelles Cross-Domain-hreflang (E3), `link_en`/`link_de` via register_post_meta. Vor englischer alkipedia.com.
- **P8 · Theme-Bootstrap + Migration** (BRIEF-pflichtig): `themes/kadence-child/` + 3 komplexe Templates (favorite-archive, rezeptkategorie, was-koche-ich-heute) + Plugin-vs-Theme-Schnitt pro Funktion. **Realistisch 5–8 Sessions** (nicht 2–3).
- **P9 · Kategorie-Seiten-Automatisierung** (BRIEF-pflichtig, **NEU-Feature, kein Legacy-Pendant**): Voraussetzung P3 + P8.
- **P10 · `rest-legacy`-Modul** (BRIEF-pflichtig, Klassifikation „legacy"): Legacy-Routen **1:1 inkl. Bugs** (E8). Voraussetzung OQ-1 (P0). Bugs in BRIEF „Bekannte Tech-Debt", nicht gefixt; Code-Standards eingeschränkt.
- **P11 · `cache-bridge` (Code-Phase)**: BRIEF v1.0 liegt (`8e7dae4`). ~19 Files / ~1.700–1.800 LOC / 5–6 Sessions. Infrastruktur, bewusst zuletzt. **Smoke-Step-0:** Autoloader-`Word_Word`-Auflösung verifizieren VOR Provider-Code.

## Last Session Handoff
**Stand: 2026-06-14 (neunte Claude-4.8-Session, „g"). P2 `meta-registry` CODE IMPLEMENTIERT + Smoke grün — erstes echtes Feature-Modul der Live-First-Phase. Zwei Core-Mini-Tasks (ACF-Hard-Dependency, `html`-Feldtyp) + Modul. NÄCHSTER: P3 `post-type-registry` (BRIEF-pflichtig). ERSTER CODE der Migrations-Phase abgeschlossen.**

### Session 2026-06-14 (g) — P2 meta-registry CODE + Smoke (erstes Feature-Modul)
- **Reihenfolge wie BRIEF § 13:** Core-Mini-Task A (ACF-Hard-Dependency) → Core-Mini-Task B (`html`-Feldtyp) → Modul (config → Registrars → Admin) → Smoke.
- **Commits (g):** `704d3c6` Core-ACF-Dependency (Activation-Block + `plugins_loaded` prio 20 Dormancy + Notice) · `3b83b4d` read-only `html`-Feldtyp in `SettingsPage::render_field` + Submit-Unterdrückung bei Read-only-Tabs · `56af7ea` Modul (7 Files) · `<dieser>` BRIEF-Living-Doc-Update + dieser Handoff.
- **Modul-Architektur (wie gebaut):** `config/fields.php` (34 Felder, Single Source) + `config/groups.php` (8 ACF-Group-Metadaten) → `Registry/Field_Registrar` (register_*_meta, init, did_action-Guard) + `Registry/Group_Registrar` (acf_add_local_field_group, acf/init, did_action-Guard, Key-Reuse-Override) + `Admin/Settings` (Diagnose-Tab via `html`-Feld). module.php = direkte Multi-Instanziierung (kein Root-Wrapper, FS-Safety).
- **Zwei empirische Build-Korrekturen (Bug-Demo vor Fix, in BRIEF § 9.11/§ 9.12 + § 2/§ 13 eingepflegt):**
  - **`register_user_meta()` existiert in WP NICHT** → User-Meta via `register_meta('user', …)`. (BRIEF-§-2-Begriff korrigiert.)
  - **`register_meta` lehnt typ-inkonsistente Defaults ab** (verifiziert: `integer` + `default ''` → Registrierung schlägt fehl) → Field_Registrar lässt Default bei Typ-Mismatch weg / normalisiert.
- **Smoke (tests-cli, ACF aktiv) — alle grün:** 34/34 Meta-Keys (post/user/term + mixed `link_*`), Orphan `rezept_tag` meta-only, 5 Dead-Code-Keys abwesend, `_my_favorite_post_likes` korrekt NICHT hier (→ P4), REST exponiert `link_de`(object)/`reviewed_by`, **8 ACF-Groups je 1× mit `local='php'` trotz 6 DB-Groups → kein Doppel-Render** (Override bestätigt), Diagnose-Tab rendert (7733 Z. HTML), Inaktiv-Restore entfernt Keys/Groups bei intakten `wp_postmeta`-Daten. phpcs Exit 0, `wp plugin check` 0 Findings, debug.log sauber. Autoloader löst `Word_Word`-Klassen auf.
- **Env-State:** `depeur_food_modules` zurück auf `[]` (Pre-Snapshot). Test-Content (217 cocktails/23 trinkspiele/19 bar-equipment) bleibt in der tests-DB.
- **Nächster Schritt:** **P3 `post-type-registry`** (BRIEF-pflichtig, E7): CPT-/Taxonomie-Registrierung aus ACF ins Plugin replizieren + generische CPT-Settings-UI; danach ACF-CPT-Registrierung pro Site deaktivieren. Davor BRIEF schreiben + freigeben (§ 12.3). Beobachtung aus P1/P2: CPT heißt `trinkspiele` (plural), `blog`/`tests` lokal nicht als CPT registriert — P3 enumeriert die echte Liste aus ACF (`acf-post-type`-Posts).

### Session 2026-06-14 (f) — P2 meta-registry BRIEF v1.0 FREIGEGEBEN (KEIN Code)
- **Ablauf:** Block-Review (4 Blöcke à 3–4 Sektionen, je schnelle Approval) → konsolidierter Write → Commit `a39c1c7` (`modules/meta-registry/BRIEF.md`, 402 Z., Schema 1.1). § 12.3-konform: Code erst nach Freigabe.
- **Schlüssel-Festschreibungen:**
  - **Doppel-Owner-Pattern:** Plugin = Schema-Definition (`register_*_meta`) **+** Field-Group-Definition (`acf_add_local_field_group`); ACF = Editor-UI-Renderer; Konsumenten-Module = Logik-Schreiber. Löst ADR-5 **und** E6 gleichzeitig (Konsumenten hängen nur am Meta-Key).
  - **Field-Registry = Single-Source** (config-as-code `config/fields.php`+`groups.php`): 1 Eintrag → BEIDE Registrierungen (kein Schema-/Editor-Drift).
  - **34 Meta-Keys** (User/Term/Post + mixed link_*); `_my_favorite_post_likes` → **P4** ausgelagert (protected key + Favoriten-Logik, full-gate-Entscheidung 3.8).
  - **ACF-Key-Reuse → local-Override** (4.5): Code-Groups nutzen exakte Discovery-Keys → ACF überschattet die DB-/UI-Groups (kein Doppel-Render); nach manueller UI-Group-Löschung pro Site ist die ACF-Feldgruppen-Verwaltung **leer** (Struktur im Plugin geschützt, Werte weiter via ACF editierbar — Jonas-Anforderung). Trade-off: Struktur danach nur per Code änderbar.
  - **ACF free = Hard-Dependency** (Plugin-Core, nicht Modul): Aktivierungs-Block (`class_exists('ACF')`) + Runtime-Dormancy + `plugins_loaded` prio 20. Eigener Core-Mini-Task VOR Modul-Code.
  - **`acf/init`-Timing-Fund (9.10):** `did_action('acf/init')`-Guard im Konstruktor (sonst Field-Groups verpasst je nach ModuleManager-Init-Prio) — Smoke verifiziert Editor-Render.
  - **Diagnose-Tab** braucht kleinen Core-`html`-Feldtyp (Field_Renderer-Wachstumsfuge, pre-blessed) → zweiter Core-Mini-Task.
  - **Empirische P1-Korrekturen eingearbeitet:** 5 Dead-Code-`rezept_*` raus, `rezept_tag`-Orphan (meta-only, `editor_ui=false`) rein, `reviewed_by` (67 Werte) aktiv.
- **Commit (Session f):** `a39c1c7` (BRIEF v1.0).
- **Nächster Schritt:** Code-Phase. **Reihenfolge:** (1) Core-Mini-Task ACF-Hard-Dependency (`Activation.php` + `depeur-food.php`) → (2) Core-Mini-Task `html`-Feldtyp (`SettingsPage::render_field`) → (3) Modul `config/` → `Registry/` → `Admin/` → Smoke (§ 12). Datei-Liste: BRIEF § 13. **Smoke-Schwerpunkte:** kein Doppel-Render (Key-Override), `acf/init`-Timing, REST-Sichtbarkeit, Management-Liste-leer nach UI-Group-Löschung.

### Session 2026-06-13 (e) — P0-Abschluss + P1 ACF-Discovery DONE (KEIN Plugin-Code)
- **Phase 1 (Doku):** P0 als abgeschlossen festgeschrieben (CPT-/Taxonomie-Quelle = ACF free; OQ-1 obsolet via E8). ACF-free-Constraint in E6 ergänzt (keine Pro-Features voraussetzen — sonst OPEN-DECISION im BRIEF). E7 um ACF-Quelle + P3-Code-Verfahren erweitert. Sprint-Liste „VORLÄUFIG" → final. Commit `3fd75e5`.
- **Phase 2/3 (Discovery):** `_references/acf-discovery.md` (357 Z.) — 6 UI-Field-Groups (30 Felder, aus `acf-export-2026-06-12.json`) + 3 Code-Registrierungen via `acf_add_local_field_group()` (Spotlight-Newsletter-Overrides `show_newsletter_form`/`newsletter_position`/`show_app_promo` aus spotlight-subscribe; `tag_group` aus alkipedia/functions.php) + Cross-Reference gegen legacy-inventory.md + Field-Key-vs-Name + Pro-Features-Check (sauber/free) + Meta-Box-Komplexitäts-Schätzung. Commit `348be8b` (mit Roh-Export).
- **Empirische wp-cli-Verifikation** (localhost:8889, nach Jonas' Test-Content-Import: 217 cocktails/23 trinkspiele/19 bar-equipment): Post-Meta-Counts bestätigen alle Felder; **§ 4.3 aufgelöst** — 5 `rezept_*`-Code-Referenzen = Dead-Code (0 Werte), `rezept_tag` (singular, 9 Werte) = lebender Orphan, **kein zweiter Export nötig**; **§ 4.4 korrigiert** — `reviewed_by` hat 67 Werte (genutzt, Reader im Produktiv-Theme). Term-/User-Meta lokal leer (Test-Daten-Lücke, nicht Beweis für Nichtnutzung).
- **Doku-Hygiene (P0-Folge, vorab disclosed):** RECON-LÜCKEN in legacy-inventory.md auf P0/P1-Abschluss aktualisiert; OQ-1 in CLAUDE.md Open Questions als obsolet markiert.
- **Commits (Session e):** `3fd75e5` (P0-close) · `348be8b` (acf-discovery + Roh-Export) · dieser (Handoff + Doku-Hygiene).

#### Beobachtungen für den P2-BRIEF (meta-registry) — verbindlich
1. **Scope geklärt:** Export ist global/vollständig. 5 `rezept_{post_tags,categories,cocktail_tags,trinkspiel_tags,equipment_tags}` = Dead-Code → **NICHT** registrieren. `rezept_tag` (singular, lebender Orphan, 9 Werte) → **mit-registrieren** trotz Fehlens im JSON-Export.
2. **`_my_favorite_post_likes` gehört zu P4 (favorites), nicht P2** — protected Key (führender `_` → `auth_callback` nötig) **und** Favoriten-Geschäftslogik. P2/P4-Owner-Abgrenzung im P2-BRIEF festlegen.
3. **Drei Registrierungs-Ziele:** `register_post_meta` + `register_user_meta` + `register_term_meta` (Übersetzungen `link_de`/`link_en` brauchen post **und** term; Author-Felder = user; tag_group/WPRM/static_page = term).
4. **`link`-Feldtyp speichert ein Array** (`{title,url,target}`) auch bei `return_format=url` → Schema-Typ in register_*_meta beachten (P7).
5. **`show_in_rest => true` für alle** — ACF setzt es heute nirgends (alle Groups show_in_rest=0/false); das ist der Kern-Mehrwert von P2.
6. **Namens-Identität strikt** (ADR-5/E5): exakt der ACF-Field-**Name** als Meta-Key.
7. **Newsletter-Overrides NICHT auf Standard-`post`** registriert (nur page/blog/tests + rezeptkategorie-Template) — P5-BRIEF klärt, ob `post` ergänzt wird.
8. **Term-/User-Meta lokal unbefüllt** — echte Nutzung der reinen Social-Profile-Felder + Term-Felder erst in P6/P7/P8 auf Live verifizieren (P2 registriert vorsorglich, billig).
9. **CPT `trinkspiele` (plural)** ≠ `trinkspiel` (Legacy-Naming) — für P3 relevant.

### Session 2026-06-12 (d) — Strategie-Refactor (Live-First) + Legacy-Recon (KEIN Code)
- **Auslöser:** Jonas' Einsicht — (1) cache-bridge ist Infrastruktur-Sahne (unsichtbar, nur live testbar), nicht launch-blocking; (2) die gewünschte Kern-Funktionalität ist **Migration aus existierendem System**, nicht Neubau.
- **Recon-Session:** alle 4 Legacy-Plugins (`category-schema`, `my-favorite-posts-plugin`, `rest-api-wprm`, `spotlight-subscribe`) + Theme `alkipedia` (`functions.php` 928 Z., `rank-math.php`, 3 Templates) vollständig gelesen → 7-Funktionen-Inventar in **`_references/legacy-inventory.md`** (persistente Migrations-Referenz). Denkfehler-Aufdeckung (D1–D8) + strategische Synthese.
- **Ergebnis:** Neue Live-First-Sprint-Liste P0–P11 (s. „Aktueller Sprint", **VORLÄUFIG** bis P0). cache-bridge → P11. Neue Module aus E7/E8: `post-type-registry`, `rest-legacy`.
- **E1–E8 final entschieden** (→ Architecture Notes › „Architektur-Entscheidungen E1–E8").
- **Commits heute (Session d):** dieser Commit (Sprint-Refactor + `legacy-inventory.md`). Session-c-Vorlauf: `9cd9131` (Domain-Fix) · `8e7dae4` (BRIEF v1.0) · `9704258` (Sketch-Entfernung) · `8f39b96` (alter Handoff, durch diesen ersetzt). Vorsession: `74abeca` `0a87e58` `d3ea4be` `c0defad`.
- **Jonas-Beobachtungen (für künftige BRIEFs verbindlich):**
  - **Externe App = Black-Box:** OQ-1 muss neben Endpoint-Nutzung auch Plattform (iOS/Android/Web), Versionierungs-Bedarf und Auth-Mechanismus klären (→ P0).
  - **Cookie→localStorage-Migration nicht trivial:** User mit bestehenden Favoriten dürfen sie nicht verlieren — Migrations-Pfad (Cookie lesen → localStorage übertragen → Cookie löschen) muss im Favoriten-BRIEF (P4) explizit sein.
  - **Theme-Bootstrap (P8) unterschätzt:** 3 komplexe Templates + Plugin-vs-Theme-Aufteilung pro Funktion → realistisch **5–8 Sessions**, nicht 2–3.
- **Recon-Lücken (P0, blockierend):** CPT-/Taxonomie-Registrierungs-Quelle (extern, nicht in `_references/`) + OQ-1. Details in `legacy-inventory.md`.
- **Nächste Session:** P0 (Recon-Lücken) → Sprint final (VORLÄUFIG-Markierung entfernen) → P1 ACF-Discovery starten.

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
- **OQ-1:** ✓ **OBSOLET 2026-06-13** (durch E8): Live-Konsumenten der Legacy-REST-Routes `wl/v1/posts` / `wrm/v1/rating/*` müssen nicht geklärt werden — alle Routen werden 1:1 inkl. Bugs ins `rest-legacy`-Modul (P10) übernommen, unabhängig von der konkreten App-Nutzung. (Befund: P0-Abschluss.)
- **OQ-2:** Newsletter-Provider-Scope (nur Flodesk vs. Multi-Provider von Tag eins)? → klären vor Task 7.
- **OQ-3:** Verwendung von `mu-plugins/` (aktuell leer)?
- **Item-1:** SSH-Alias `runcloud-test` verfügbar (Linux Testserver, PHP 8.4.20, User `runcloud`, verifiziert in dieser Session). Test-WebApp ist `/home/runcloud/webapps/Food-Blog_Template/` — bestehendes Test-WordPress, freigegeben für Phase-B-Remote-Tests. Lese-Operationen sind jederzeit zulässig; Schreibe-/Push-Operationen erst nach explizitem Push-Approval pro Feature.
- **Item-2 (erledigt 2026-06-08):** Beide Pflicht-Edits aus PLAN.md § 6 sind in `wordpress.md` umgesetzt (§ 1.1 Multi-Option, § 4.5 Autoload); zusätzlich § 4.2 ↔ ADR-3 + Z.21-Glitch gefixt (`df09c5c`). Phase B entsperrt. Siehe „Last Session Handoff".
- **Item-3:** `wordpress.md` § 2.3 fordert `class-{name}.php` für Klassendateien, die frozen PSR-4-/Suite-Architektur nutzt aber PascalCase (`src/Core/Plugin.php`). Stale-Standard analog zum gelösten § 4.2 ↔ ADR-3. phpcs ist bereits via FileName-Sniff-Exclude (`src/*`, `modules/*`) darauf eingestellt; die Bibel selbst wurde NICHT eigenmächtig editiert. ✓ **ERLEDIGT 2026-06-12** (Commit `7ea65d9`): § 2.3 + § 1.1-Architektur auf PSR-4/PascalCase synchronisiert (Z. 11/13/14/65/68). (Befund: Last Session Handoff 2026-06-08.)
- **Item-4:** Plugin Check (PCP 2.0.0) nur als Dev-Tool im `tests-cli` installiert, NICHT in `.wp-env.json` gemappt. Bei `wp-env destroy`/Neuaufbau nachinstallieren: `wp-env run tests-cli wp plugin install plugin-check --activate`. (Eingeführt 2026-06-08, Task-1d.) *(Operationaler Eintrag — NICHT Teil des Standards-Patch-Backlogs.)*
- **Item-5:** ✓ **ERLEDIGT 2026-06-12** (Commit `45aa3d1`): `wordpress.md` § 12.1 Stale-Reference „ab Task 4 (cache-bridge)" → auf Task 5 korrigiert, renumber-stabil formuliert (semantisches Kriterium „geschäftslogik-tragend" als Hebel, Task-Nummer nur Beispiel) + Task 4 (Tab-System/Core-UI) in die exempt-Liste aufgenommen.
- **Item-6:** ✓ **ERLEDIGT 2026-06-12** (Commit `03b3780`): `SettingsRegistry::sanitize_field()` select-Zweig auf string-symmetrischen Vergleich umgestellt (`array_map( 'strval', … )` + `(string)`-Cast mit `is_scalar`-Guard, Rückgabe des validierten Strings). Smoke: 4 Vektoren grün (numeric `"1"`→`'1'`, string-Regression, invalid→`''`, out-of-range→Default). (Befund: Task 4, 2026-06-12.)
- **Item-7:** `src/Support/SuiteCompat.php` (geplant in PLAN.md § 2 als „graceful Fallback wenn Suite nicht aktiv") wird durch die No-Suite-Dependency-Regel der Plugin-Splitting-Strategie fragwürdig — `cache-bridge` bridged nicht mehr zur Suite. Klärung: SuiteCompat ganz streichen vs. für andere (Nicht-Cache-)Suite-Interop behalten. **Nicht cache-bridge-Scope** (kein Drive-by). (Befund: Task-5-Recon, 2026-06-12.)

*(Items 8/9/10/11 wurden 2026-06-12d aufgelöst: **Item-8** (ACF-Discovery) → Sprint **P1**; **Item-9** (Theme-Templates) → Sprint **P8/P9**; **Item-10** (Performance-deferred) → `_references/ROADMAP-ANALYSIS-NOTES.md` (Depeur-Speed-Cluster, Phase 2+); **Item-11** (Asset-Convention) → Architecture Notes. Die Editor-UI-Sub-Entscheidung aus Item-8 ist via **E6** entschieden: ACF bleibt als Editor-UI eigenständig, depeur-food nutzt nur die Datenschicht.)*

### Standards-Patch-Session Backlog — ✓ ABGESCHLOSSEN (2026-06-12)
Alle drei Items in einer Standards-Patch-Session vor Task 5 erledigt (Reihenfolge klein→groß, Code zuletzt):
1. **Item-3** ✓ — § 2.3 + § 1.1 PSR-4-Sync (Commit `7ea65d9`).
2. **Item-5** ✓ — § 12.1 Renumber-Fix (Commit `45aa3d1`).
3. **Item-6** ✓ — `sanitize_field` string-Symmetrie + Smoke (Commit `03b3780`).

## Architecture Notes for Future Sessions
Vorausschauende Architektur-Hinweise (kein Open-Item-Backlog — werden zur richtigen Zeit sichtbar):
- **`SettingsPage::render_field()` → `Field_Renderer`-Extraktion:** Der natürliche Split-Kandidat, sobald Feldtypen jenseits der heutigen vier (checkbox/text/select/password) dazukommen ODER der Core-Tab eigene Custom-Renderer braucht. Heute (Task 4) nicht nötig — eine Switch-Case-Methode reicht. Aber beim **nächsten Modul mit neuem Feldtyp** (z. B. textarea, multiselect, color-picker) zuerst die `Field_Renderer`-Extraktion erwägen, bevor `render_field()` mit weiteren Switch-Cases wächst. (Kontext: SettingsPage ist nach Task 4 bei 673 Z. — kein Bloat, aber render_field ist die Wachstumsfuge.)

### Architektur-Entscheidungen E1–E8 (2026-06-12d, post Recon-Re-Validation)
- **E1: Rank Math = Hard-Dependency** (`schema-engine` setzt voraus, kein Soft-Path; SEO-Provider-Abstraktion = Phase 2).
- **E2: WPRM = Soft-Dependency** (`function_exists`-gated, graceful skip; Favoriten funktionieren auch auf Nicht-Recipe-Posts).
- **E3: Language-Selector = manuell via ACF `link_en`/`link_de`** (Cross-Domain-Realität de/com, kein Polylang/WPML; Felder → `register_post_meta`).
- **E4: Newsletter = Flodesk-only mit dünner Provider-Naht** (`Providers/Flodesk.php`-Klassen-Trennung; **KEINE** Provider-Abstraktion/Interface/Registry/Factory ohne zweiten realen Konsumenten — Disziplin analog cache-bridge).
- **E5: Migrations-Strategie = gemischt** (parallel für daten-tragende Module mit zu ACF identischen Meta-Keys → koexistenzfähig; Big-Bang für reine Logik wie Schema-Filter/Newsletter-Insertion, sonst doppelter Output).
- **E6: ACF bleibt als Editor-UI eigenständig auf jeder Site** (lizenzrechtlich nicht bundle-bar). depeur-food nutzt nur die Datenschicht parallel via `register_post_meta` (selbe `wp_postmeta`-Tabelle, kein Konflikt). Native Editor-UI (MetaBoxen/Gutenberg-Sidebar) = separate spätere Entscheidung.
  - **ACF-Version = free (nicht Pro)** auf beiden Sites (einfachandersessen.de + alkipedia.com) — verifiziert via P0 + ACF-Export (P1, `_references/acf-discovery.md`).
  - **Konsequenz für künftige Module: KEINE Pro-only-Features voraussetzen** — Repeater, Flexible Content, Gallery, Clone, Options Pages, ACF-Blocks. Das gilt sowohl für gelesene Legacy-Felder als auch für neu zu bauende Editor-UI.
  - **Falls ein Modul-BRIEF doch ein Pro-Feature braucht:** Architektur-Diskussion **VOR** Implementierung (Upgrade zu ACF Pro vs. native Custom-UI bauen) — explizit als **OPEN-DECISION** im BRIEF markieren, nicht stillschweigend voraussetzen.
- **E7: CPT-Registrierung wandert ins Plugin** (`post-type-registry`-Modul, P3) — „Plugins so weit möglich reduzieren". CPT UI später deaktivieren. ACF-Field-Groups bleiben separat in ACF-UI (NICHT E7-Scope).
  - **CPT-/Taxonomie-Quelle = ACF (free) (P0-Erkenntnis 2026-06-13):** Die heute live registrierten CPTs (`cocktails`/`blog`/`tests`/`trinkspiel`/`bar-equipment`) + Taxonomien (`anlass`/`herkunft`/`art`/…) werden von ACF free registriert (ACF free kann seit 6.1 CPTs/Taxonomien anlegen).
  - **P3-Code-Verfahren:** bestehende CPT-/Taxonomie-Definitionen aus ACF auslesen → im Plugin via `register_post_type`/`register_taxonomy` replizieren → **dann** die CPT-/Taxonomie-Registrierung in ACF deaktivieren (Deployment-Schritt, nicht im selben Commit wie der Code). **ACF-Field-Groups bleiben in ACF** (E6, NICHT Teil dieser Migration).
- **E8: REST-Legacy-Routen 1:1 erhalten INKL. Bugs** (`rest-legacy`-Modul, P10). Bewusste Tech-Debt, niedriges Risiko (interner App-Userkreis). Bugs (`ParrentID`-Typo, `content="hallo"`, auskommentierte `permission_callback`/offenes DELETE, `max(…,200)`) in BRIEF „Bekannte Tech-Debt" dokumentiert, **nicht gefixt**. Klassifikation „legacy": BRIEF-Pflicht gilt, voller Code-Qualitäts-Standard nicht. Refactor on-the-table für künftiges `rest-modern`-Modul.

### Sicherheits-Funde aus Legacy-Recon (2026-06-12d)
- **BLOCKING vor Live-Deploy (NEUE Module mit Code-Neubau):** Favoriten-AJAX `my_favorite_post` **ohne Nonce** (→ `favorites`/P4, Nonce + `permission_callback` Pflicht); Newsletter-Admin-Save **ohne Nonce** (→ `newsletter`/P5).
- **Akzeptierte Tech-Debt, niedriges Risiko (`rest-legacy`/P10):** REST-Rating-CRUD mit auskommentierten `permission_callback` (offenes DELETE) + `wl/v1/posts` ohne Permission → **NICHT gefixt** (E8), in `rest-legacy`-BRIEF „Bekannte Tech-Debt". Voll-Details: `_references/legacy-inventory.md`.

### Asset-Convention (plugin-weit, ex-Item-11)
- **Admin-Assets:** jQuery erlaubt (Core-Admin lädt es eh, nicht Pflicht; Heuristik: nur bei >30 % Code-Reduktion). **Frontend-Assets: Vanilla strikt** (Kadence ist Frontend-jQuery-frei → ~30 KB Bloat/Page-Load vermeiden), kein Frontend-jQuery-Enqueue, kein Build-Step (direkt enqueuebares Vanilla). Gilt plugin-weit für alle Module.

### Plugin-Splitting-Strategie (langfristig — ab cache-bridge bindend)

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
