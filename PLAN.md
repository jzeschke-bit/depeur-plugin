# PLAN.md — Depeur Food Suite

Discovery-Output und Architektur-Entscheidungen für das Plugin `depeur-food` und das Child-Theme `kadence-child`. Stand: Phase A abgeschlossen. Diese Datei ist Wissens-Datenbank — sie ändert sich nur, wenn Architektur-Entscheidungen revidiert werden oder neue ADRs dazukommen. Sprint-Status und Working-State stehen in `CLAUDE.md`.

---

## 1. Inventar

### 1.1 Projektstruktur (Stand Phase A)

```
depeur-food-suite/
├── plugins/
│   ├── depeur-wp-suite/        ← Architektur-Vorlage (read-only)
│   │   ├── depeur-wp-suite.php
│   │   ├── src/{Core,Helpers,Support}/
│   │   ├── modules/{_ExampleModule,autoload-cleanup,bunny-cdn}/
│   │   ├── assets/admin/
│   │   ├── docs/{QA.md,TODO.md}
│   │   ├── phpcs.xml.dist
│   │   ├── uninstall.php
│   │   └── README.md
│   └── depeur-food/
│       └── depeur-food.php     ← 9-Zeilen-Stub, Placeholder
├── themes/                     ← FEHLT (legt Phase B an)
├── _references/
│   ├── legacy-plugins/{category-schema,my-favorite-posts-plugin,rest-api-wprm,spotlight-subscribe}/
│   ├── legacy-themes/alkipedia/
│   └── plugin-references/{bunnycdn,runcloud-hub,wp-rocket}/  (gitignored)
├── _premium/                   (gitignored, wp-env-Plugins)
├── mu-plugins/                 (leer)
├── .wp-env.json                (PHP 8.2, alle Premium-Plugins gemappt)
├── .gitignore
├── wordpress.md                ← Standards-Bibel
└── initial-prompt.md
```

### 1.2 depeur-wp-suite — Architektur (Vorlage)

- **Singleton via statische Methoden**: `Depeur\WPSuite\Core\Plugin::init()` an `init`-Hook, kein globaler Helper.
- **PSR-4-Autoloader** (eigener, kein Composer): `src/Helpers/Autoloader.php`, registriert für Namespace `Depeur\WPSuite\` und Module unter `Depeur\WPSuite\Modules\`.
- **Module via Filesystem-Discovery** (`src/Core/ModuleManager.php`): `scandir(modules/)` → liest `manifest.php`; aktive Slugs in Master-Option `depeur_wp_suite_modules`; aktivierte Module werden lazy via `require_once modules/{slug}/module.php` geladen.
- **Settings-Pattern (Suite, Stand heute)**: Multi-Option — pro Modul eigene Option `depeur_wp_suite_{slug}` (Array, autoload=no für Secrets). Tabbed Admin-UI unter `admin.php?page=depeur-wp-suite-settings&tab={slug}`. Felder: `checkbox|text|select|password|info`.
- **Constants**: `DEPEUR_WP_SUITE_VERSION|FILE|PATH|URL|BASENAME`.
- **Uninstall**: löscht alle Optionen mit Prefix `depeur_wp_suite_` (LIKE-Query).
- **Logger** (`src/Support/Logger.php`): File-basiert in `uploads/depeur-wp-suite-logs/` mit Rotation; Toggle via `depeur_wp_suite_logging_enabled`.
- **Keine zentrale Cache-Purge-API**. BunnyCDN-Modul hat eigenen `Services\BunnyApi::purge_all()`, aber kein generischer Hook für externe Konsumenten.

Verbesserungspotenzial fürs neue Plugin (im Bau zu berücksichtigen):
- Einheitliche Cache-Purge-Fassade (siehe ADR-3).
- Settings-Activation-Hook, der Module/Defaults initialisiert.
- Sanitize-Callbacks pro Feldtyp (URL, Int, JSON), nicht nur Text/Checkbox.

### 1.3 Legacy-Plugins — Konsolidierungs-Mapping

| Plugin | Was es tut | ACF? | Post-Type-Annahme | Verwertung |
|---|---|---|---|---|
| `category-schema` | Erweitert WPRM-Recipe-Schema um Author-Felder; Rank-Math-`json_ld`-Filter; liest `WPRM`-Field auf Kategorie-Term. | Pflicht (User-Meta + Kat-Meta) | Archive-Hardcode + Fallback `post`. | **Refactoren** in `schema-engine`-Modul; ACF-Calls → `register_post_meta`/`register_user_meta` mit `show_in_rest`. |
| `my-favorite-posts-plugin` | LocalStorage+Cookie-Hybrid Favoriten-System; AJAX `wp_ajax_(_nopriv)_my_favorite_post` (KEIN Nonce!); Shortcodes `[thumbnail_favorite_button|inline_favorite_button|favorite_posts_archive]`, WPRM-`[wprm-favorite-button]`; Filter `wprm_recipe_image_container` und `wprm_recipe_template_html`. | nein | Hardcoded CPT-Liste `array('post','blog','tests','cocktails','trinkspiel','bar-equipment')`. | **Refactoren**: REST-Endpoint mit Nonce + `permission_callback`, CPT-Liste filterbar via `depeur_food/favorites/post_types`, Cookie raus (nur localStorage), Like-Counter via `register_post_meta`. |
| `rest-api-wprm` | REST-Routes `wl/v1/posts` (Recipe-Slug-Lookup) + `wrm/v1/rating/*` (CRUD); `rest_wprm_recipe_query`-Filter mit `max(200)`-Bug; Typo `ParrentID`. | nein | Hardcoded `wprm_recipe`. | **Mehrheitlich verwerfen.** Slug-Lookup ist redundant zur Standard-WP-REST. Rating-CRUD nur falls Live-Konsumenten existieren (siehe Open Question OQ-1). |
| `spotlight-subscribe` | Newsletter-Form (Flodesk) + App-Promo via `the_content`-Filter; Shortcodes `[spotlight_newsletter|app_promo|both]`; eigene Settings-Page; **registriert ACF-Field-Group programmatisch** für Per-Post-Override; **Admin-Save ohne Nonce!** | Pflicht (Override-Logik) | Hardcoded CPT-Switch. | **Refactoren** in `newsletter`-Modul: Nonce nachziehen, ACF-Override → Custom-Meta-Box mit `register_post_meta`, CPT-Liste filterbar, Provider-Abstraktion. |

**Konsolidierungs-Befund:**

- Schema-Markup wird in zwei Quellen (`category-schema` und `alkipedia/rank-math.php`) angefasst → ein Modul (`schema-engine`).
- `the_content`-Manipulation (Spotlight) und WPRM-Hook-Manipulation (Favorites) konkurrieren um ähnliche Insertion-Slots → Coordinator-Logik in `newsletter`-Modul.
- Hardcoded CPT-Listen an drei Stellen → zentrale Plugin-Setting "Supported Post Types" via `PostTypeRegistry` (siehe ADR-4).

**Lücken (Kandidaten für neue Module / Phase 2):**

- Pinterest-spezifisches OG/Schema (Aspect Ratio, Pin-Optimierungen).
- Print-Recipe-Stylesheet/Shortcode.
- FAQ-/HowTo-Schema (per Block oder Recipe-Step-Mapping).
- Yoast-Filter-Pendant zu Rank-Math (Multi-SEO-Plugin-Support).
- WPRM-Asset-Loading-Conditionals (nur auf Recipe-Pages enqueuen).
- Cache-Purge-Bridge zu CDN/Origin bei Recipe-/Rating-Änderungen (siehe ADR-3).

### 1.4 Legacy-Theme `alkipedia` — Inventar & Migrations-Empfehlung

Dateien: `style.css` (437 Z, Custom-CSS für Star-Rating-Farben, abgerundete Bilder/Inputs, Tag-Badges, Author-Box-Grid, responsive Hero), `functions.php` (928 Z), `rank-math.php` (94 Z, Author-Schema-Erweiterungen), `was-koche-ich-heute.php` (400 Z, AJAX-Rezeptfilter), zwei Page-Templates, eine Test-Datei (`test-multi-taxonomy.php`, löschbar), `template-parts/content/` (single-entry, archive, entry_author, entry_footer, entry_tags, entry_loop_thumbnail).

Wichtige Hooks/Patches im Theme:

- `wp_enqueue_scripts`: `style.css` mit `filemtime()`-Cache-Buster.
- `pre_get_posts`: CPTs in Author- und Tag-Archives einbeziehen.
- `wp_head` + `kadence_footer_navigation`: hreflang via ACF `link_en`/`link_de`.
- `kadence_related_posts_carousel_args`: Related Posts auf alle CPTs erweitern, Tag-Fallback.
- `the_content`: `wpautop` deregistriert (für WPRM-Shortcodes).
- AJAX `filter_recipes` + `nopriv` für "Was koche ich heute"-Template.
- Local-ACF-Field-Group `tag_group` (Select für Tag-Klassifizierung).
- Rank-Math-`json_ld`-Erweiterung um `author.jobTitle/alumniOf/knowsAbout` + `publisher.publishingPrinciples`; Canonical-Fix für paginierte Rezeptkategorien.
- Custom WPRM-Shortcodes: `WPRM_SC_Alkipedia_CTA`, `WPRM_SC_Favorite_Button` (Cookie-basiert).

Migrations-Empfehlung:

- CSS und Cache-Buster: 1:1 ins neue Theme.
- Hook-Logik nach Themengebiet in `inc/`-Files (`schema.php` entfällt — Schema geht ins Plugin; stattdessen `multilingual.php`, `recipe.php`, `taxonomies.php`, `performance.php`, `related-posts.php`, `compat.php`).
- ACF-Calls: defensiv kapseln mit `function_exists('get_field')` und Fallback auf `get_post_meta`/`get_user_meta`. Theme darf ACF lesen, aber nicht hart fordern.
- Schema-Erweiterungen aus `rank-math.php`: nach `wordpress.md` § 1.3 datengetrieben — ins **Plugin** verschieben, Modul `schema-engine`.
- Author-Box, Recipe-CTA, Favorite-Button-Rendering: bleiben Theme-Sache (Darstellung). Favorite-Button-State liest Plugin-API, nicht Cookie.
- `test-multi-taxonomy.php`: löschen.
- `tag_group`-Local-Field-Group: nach `acf-json/` exportieren oder zu `register_term_meta` migrieren.

### 1.5 Drittanbieter-Pattern (Skim, nur Architektur)

- **WP Rocket**: Requirement-Check vor Init übernehmen. League\Container und 60+ ServiceProvider sind für unsere Größenordnung Overkill.
- **BunnyCDN**: Closure-/Lazy-Initialization für externe Clients. Vendor-Scoping ist nicht nötig, solange wir keine Composer-Dependencies haben.
- **RunCloud Hub**: Klare Path-Konstanten und Versionsmanagement. Static-Final-Singleton ist untestbar — nicht übernehmen.

Bewusst **NICHT** übernommen: Composer-Container-Frameworks; vendor/-Auslieferung; Static-Final-Singletons; übergranulare ServiceProvider.

---

## 2. Plugin-Struktur (`plugins/depeur-food/`)

```
plugins/depeur-food/
├── depeur-food.php                    ← Bootstrap + Konstanten + Helper
├── uninstall.php                      ← löscht alle Optionen + Custom Tabellen mit depeur_food_-Prefix
├── readme.txt                         ← WordPress-Plugin-Header
├── README.md                          ← Entwickler-Doku
├── CHANGELOG.md                       ← Keep-a-Changelog
├── HOOKS.md                           ← Liste aller Custom Actions/Filters
├── phpcs.xml.dist                     ← WPCS + PHPCompatibilityWP 8.2-
├── languages/
│   └── depeur-food.pot
├── assets/
│   ├── admin/{admin.css,admin.js}
│   └── public/{favorites.js,subscribe.js}
├── src/
│   ├── Core/
│   │   ├── Plugin.php                 ← Singleton + init
│   │   ├── ModuleManager.php          ← Filesystem-Discovery + Lazy-Load
│   │   ├── PostTypeRegistry.php       ← zentrale Liste supported post types
│   │   ├── Activation.php             ← Activation/Deactivation
│   │   ├── AdminMenu.php              ← Top-Level-Menü mit Tabs
│   │   └── Settings/
│   │       ├── SettingsPage.php       ← Tab-Renderer (Settings API)
│   │       └── SettingsRegistry.php   ← Modul-Registrierung
│   ├── Helpers/
│   │   └── Autoloader.php             ← PSR-4 für Depeur\Food\
│   ├── Support/
│   │   ├── Logger.php                 ← optional, file-based
│   │   └── SuiteCompat.php            ← graceful Fallback wenn Suite nicht aktiv
│   ├── Cache/
│   │   └── Purge_Context.php          ← readonly Value-Object (siehe ADR-3)
│   └── Api/
│       └── PostMeta.php               ← register_post_meta-Helfer mit Schema
└── modules/
    ├── _ExampleModule/                ← Template
    │   ├── manifest.php
    │   ├── module.php
    │   └── Admin/Settings.php
    ├── schema-engine/                 ← ersetzt category-schema + theme/rank-math.php
    │   ├── manifest.php
    │   ├── module.php
    │   ├── Engine.php                 ← Generiert Recipe/Article/Author/Publisher
    │   ├── Filters/RankMath.php
    │   ├── Filters/Yoast.php          ← Stub, opt-in
    │   └── Admin/Settings.php
    ├── favorites/                     ← ersetzt my-favorite-posts-plugin
    │   ├── manifest.php
    │   ├── module.php
    │   ├── Service.php                ← localStorage-only, register_post_meta für Likes
    │   ├── Rest.php                   ← REST-Endpoint mit Nonce + Cap
    │   ├── Shortcodes.php             ← [df_favorite_button], [df_favorite_archive]
    │   ├── WprmIntegration.php        ← Filter wprm_recipe_template_html
    │   └── Admin/Settings.php
    ├── newsletter/                    ← ersetzt spotlight-subscribe
    │   ├── manifest.php
    │   ├── module.php
    │   ├── Inserter.php               ← the_content-Filter mit Position
    │   ├── Provider/Flodesk.php
    │   ├── Provider/Mailchimp.php     ← Stub (siehe Open Question OQ-2)
    │   ├── PerPost.php                ← Custom Meta Box statt ACF
    │   ├── Shortcodes.php
    │   └── Admin/Settings.php
    ├── recipe-extras/                 ← neu, Quality-of-Life für WPRM
    │   ├── manifest.php
    │   ├── module.php
    │   ├── ConditionalAssets.php      ← WPRM nur auf Recipe-Seiten enqueuen
    │   ├── PinterestSchema.php        ← optional
    │   └── Admin/Settings.php
    ├── rest-extensions/               ← optional, ersetzt rest-api-wprm wenn benötigt
    │   ├── manifest.php
    │   ├── module.php
    │   └── Endpoints/Rating.php
    └── cache-bridge/                  ← Hook-First Purge-Layer (siehe ADR-3) — Cluster: Depeur Speed
        ├── manifest.php
        ├── module.php
        ├── Hooks/
        │   └── Listener.php           ← save_post + WPRM-Hooks → feuert Purge-Action  (TENTATIV: zentraler vs. pro-Hook-Listener + Lifecycle final im BRIEF)
        ├── Providers/                 ← PascalCase/PSR-4, kein *.php am Modul-Root (FS-Safety § 2.7)
        │   ├── ProviderInterface.php
        │   ├── LogOnly.php            ← Always-on Default, schreibt error_log
        │   ├── BunnyCDN.php           ← eigenständig, eigene Credentials (kein Suite-Bridge)
        │   ├── Cloudflare.php         ← eigene Credentials in Modul-Settings
        │   └── RunCloud.php           ← analog
        └── Admin/Settings.php
        (Pause-Mechanismus Variante C — Queue/Admin-Bar/Resume — Files im cache-bridge BRIEF)
```

Konventionen:

- Namespace `Depeur\Food\` (Klassen). Hook/Option/Meta-Prefix: `depeur_food_` (snake_case) bzw. `depeur_food/` (Action/Filter-Pfade). Kurzform `df_` für CSS-Klassen, Body-Classes, Shortcode-Tags, JS-Globals.
- Konstanten: `DEPEUR_FOOD_VERSION|FILE|PATH|URL|BASENAME`.
- Plugin-Header: `Requires PHP: 8.2`, `Requires at least: 6.5`.
- `register_post_meta`/`register_user_meta`/`register_term_meta` mit `show_in_rest`, `single`, `type`, `sanitize_callback`, `auth_callback`. Kein ACF im Plugin zur Laufzeit.
- Sicherheit: jede REST-Route mit `permission_callback` (nie `__return_true` ohne Begründung); jede AJAX-Action mit `check_ajax_referer`; jeder Settings-Save mit `check_admin_referer`. Reihenfolge: Capability → Nonce → Sanitize → Process → Escape Output.

---

## 3. Theme-Struktur (`themes/kadence-child/`)

```
themes/kadence-child/
├── style.css                          ← Theme-Header + Custom CSS migriert aus alkipedia
├── functions.php                      ← Bootstrap, lädt inc/-Files
├── screenshot.png
├── README.md
├── inc/
│   ├── enqueue.php                    ← parent + child + filemtime + defer-Strategy
│   ├── performance.php                ← dequeue Liste, preload, kritische Schnitte
│   ├── recipe.php                     ← WPRM-CTA-Shortcode, CSS-Tweaks via PHP
│   ├── multilingual.php               ← hreflang-Logik aus Theme migriert (ACF-optional)
│   ├── related-posts.php              ← Kadence-Filter für CPT-Inclusion
│   ├── taxonomies.php                 ← Tag-Group-Admin-Spalte; ACF-JSON statt PHP
│   └── compat.php                     ← wpautop-Patch + Kadence-Compat
├── template-parts/
│   └── content/
│       ├── single-entry.php
│       ├── archive.php
│       ├── entry_author.php
│       ├── entry_footer.php
│       ├── entry_tags.php
│       └── entry_loop_thumbnail.php
└── acf-json/                          ← exportiertes tag_group-Feld
```

Schema-Markup aus `alkipedia/rank-math.php` wandert ins Plugin (Modul `schema-engine`), weil datengetrieben. Im Theme bleiben nur Darstellungs-Tweaks (CSS für Star-Rating-Farben, Author-Box-Layout, abgerundete Bilder, Tag-Badges).

---

## 4. Architecture Decisions (ADRs)

### ADR-1: Settings-Pattern = Multi-Option (Suite-Stil)

**Status:** Accepted. Phase A.

**Kontext:** `wordpress.md` § 1.1 fordert Single-Array-Option `depeur_food_settings`. Suite nutzt dagegen pro Modul eine eigene Option (`depeur_wp_suite_{slug}`) plus eine Master-Liste der aktiven Module.

**Entscheidung:** Multi-Option-Pattern. Pro Modul eigene Option `depeur_food_{slug}` (Array, autoload=no für Module mit sensiblen Daten wie API-Keys). Master-Liste der aktiven Module: `depeur_food_modules`. Tabbed Admin-UI unter `admin.php?page=depeur-food-settings&tab={slug}`.

**Begründung:** Module-Sandboxing (Modul-Reset löscht nur seine eigene Option), `autoload=no` pro Modul für Secrets ohne globalen Performance-Hit, identischer Code-Reuse aus Suite (`SettingsRegistry`/`SettingsPage`-Logik fast 1:1 portierbar), bewährt im Produktionseinsatz. Single-Array hätte zwar einen einzigen Schreibpfad, koppelt aber alle Module unnötig und macht autoload-Differenzierung umständlich.

**Konsequenz:** `wordpress.md` § 1.1 muss vom User auf Multi-Option-Pattern angepasst werden (siehe § 6 "wordpress.md Updates Required"). `SettingsRegistry::OPTION_PREFIX = 'depeur_food_'`.

### ADR-2: PHP-Minimum 8.2

**Status:** Accepted. Phase A.

**Kontext:** Suite verlangt PHP 8.0. `wordpress.md` sagt 8.2 (RunCloud-Default). Live-Test-Server zeigt PHP 8.4.20.

**Entscheidung:** PHP-Minimum 8.2. Plugin-Header `Requires PHP: 8.2`. PHPCompatibilityWP-Target `8.2-`.

**Begründung:** Konsequent zur Standards-Bibel. RunCloud-Default + Live-Server liefern 8.2+, lokales wp-env auch. Erlaubt readonly properties (verwendet in `Purge_Context`, ADR-3), enums, never-Return-Types, first-class callable syntax. Suite-Compat wird via `class_exists` geprüft, kein PHP-Versions-Konflikt zwischen Plugins.

**Konsequenz:** Kein Code für PHP 8.0/8.1-Fallbacks. CI/Plugin-Check-Profile auf 8.2 fixiert.

### ADR-3: Cache-Purge = Hook-First mit Provider-Pattern in depeur-food selbst

> **⚠️ Update 2026-06-12 — supersedes Provider #2 + Suite-Klauseln (Splitting-Strategie):**
> - **`Provider_Suite_Bunny` → `Provider_BunnyCDN`** (eigenständig, eigene Credentials, **kein** `class_exists`-Bridge auf die Suite-`BunnyApi`). Grund: `cache-bridge` gehört zum künftigen Plugin **Depeur Speed**; eine Suite-Abhängigkeit — auch `class_exists`-gegated — sabotiert den Plugin-Split.
> - Die Klauseln „Suite wird in dieser Phase nicht angefasst" / „Suite kann unverändert bleiben" sind gegenstandslos: Wir bridgen nicht mehr zur Suite — Unabhängigkeit ist jetzt Architektur-Ziel, nicht Nebeneffekt. → CLAUDE.md › Architecture Notes › Plugin-Splitting-Strategie.
> - **Physisches Layout:** Provider-Klassen im `Providers/`-Subordner (PascalCase/PSR-4), **nicht** flach am Modul-Root (FS-Safety, Modul-Architektur-Kanon § 2.7). Exakte Klassennamen + Listener-Platzierung + Pause-Files = im `cache-bridge`-BRIEF.
> - **Neu (Variante C):** Purge-Pause-Mechanismus (Queue + Admin-Bar + Resume-Modal) — Design im BRIEF.
>
> Der Original-Text unten bleibt als Entscheidungs-Historie stehen.

**Status:** Accepted. Phase A.

**Kontext:** Suite hat keine zentrale Cache-Fassade, nur das BunnyCDN-Modul mit eigener Klasse. depeur-food braucht aber bei Recipe-/Rating-Updates Cache-Purges (Origin + CDN). Drei Optionen waren auf dem Tisch: lose Hook-Kopplung (Suite empfängt später), `class_exists`-Direktaufruf der Suite-Klassen, oder Suite zuerst um eine Cache-Fassade erweitern.

**Entscheidung:** Hook-First mit Provider-Pattern in depeur-food selbst.

Public Contract:

```php
namespace Depeur\Food\Cache;

final readonly class Purge_Context {
    public function __construct(
        public ?int    $post_id,
        public ?string $post_type,
        public array   $urls,    // explizite URLs zum Purgen
        public array   $tags,    // Cache-Tags (Cloudflare/Bunny)
        public string  $reason,  // 'post_save' | 'wprm_recipe_save' | 'rating_change' | …
    ) {}
}

// Beliebiges Modul:
do_action( 'depeur_food/cache/purge', new \Depeur\Food\Cache\Purge_Context( … ) );
```

Modul `cache-bridge` (toggleable, Default ON):

- `Listener.php` registriert auf `save_post`, `transition_post_status`, `wprm_save_recipe` (existiert nur wenn WPRM aktiv) und feuert die Action mit befülltem `Purge_Context`.
- `Provider_Interface` mit `is_available(): bool` + `purge( Purge_Context $ctx ): void`.
- Vier Provider, alle in den Modul-Settings einzeln aktivierbar, mit eigener Reihenfolge:
  1. `Provider_Log_Only` — Always-on Fallback, `error_log` mit Context-Dump. Garantiert keinen leisen Datenverlust.
  2. `Provider_Suite_Bunny` — `class_exists( 'Depeur\WPSuite\Modules\BunnyCDN\Services\BunnyApi' )` als Gate, ruft Suite-Klasse **nur lesend** an (kein Schreibzugriff auf Suite-State, keine Suite-Änderung erforderlich).
  3. `Provider_Cloudflare` — direkter API-Call, Credentials in `depeur_food_cache_bridge`-Option (autoload=no, Passwort-Feld).
  4. `Provider_RunCloud_Hub` — analog, falls auf RunCloud-Stack.
- Provider werden in registrierter Reihenfolge ausgeführt; Fehler eines Providers loggt der Logger, blockt aber die nächsten nicht.

**Begründung:** Lose Kopplung; Suite wird in dieser Phase nicht angefasst (kein Suite-PR nötig); Provider sind unabhängig testbar; `Provider_Log_Only` als Always-on macht Purges sichtbar, auch wenn kein anderer Provider aktiv ist; readonly Value-Object verhindert versehentliche Mutation; Public-API ist stabil — wenn Suite später eine zentrale `Cache\PurgeManager`-Fassade bekommt, wird ein neuer `Provider_Suite_Cache` eingefügt, die Action und das Value-Object bleiben unverändert.

**Konsequenz:** Suite kann unverändert bleiben. Wer eigene Provider braucht (z. B. ein zukünftiges Fastly), implementiert `Provider_Interface` und registriert sich an `depeur_food/cache/register_providers`.

### ADR-4: Post-Type-Agnostik via zentraler Liste

**Status:** Accepted. Phase A.

**Kontext:** Drei der vier Legacy-Plugins haben Post-Type-Listen hardcoded. Erstes Rollout-Ziel ist `post`, zweites `cocktail`-CPT auf alkipedia.com. Plugin muss beides ohne Code-Änderung bedienen.

**Entscheidung:** Eine einzige Quelle der Wahrheit: `depeur_food()->get_supported_post_types()`. Default `array( 'post' )`, in den Plugin-Core-Settings (Multi-Select aus `get_post_types( ['public'=>true] )`) erweiterbar. Filter `depeur_food/post_types` für programmatische Erweiterung.

**Begründung:** Ein einziger Konfigurationspunkt verhindert das Hardcoded-Drift-Problem der Legacy-Plugins. Multi-Select in der Admin-UI macht es User-konfigurierbar, ohne Code zu schreiben. Der Filter erlaubt Einsatz auf neuen Sites, die einen weiteren CPT vor Plugin-Aktivierung registrieren.

**Konsequenz:** Kein Modul darf Post-Types hardcoden. Code-Review-Regel (im Self-Review-Hook): Grep nach `'post_type'\s*=>` und Hardcoded-Strings ist ein Stop-Light.

### ADR-5: Custom Fields via `register_post_meta`, kein ACF zur Laufzeit

**Status:** Accepted. Phase A.

**Kontext:** Drei Legacy-Plugins und das Legacy-Theme nutzen ACF intensiv. `wordpress.md` § 1.1 fordert keine Runtime-Dependency auf ACF.

**Entscheidung:** `register_post_meta` / `register_user_meta` / `register_term_meta` mit `show_in_rest`, `type`, `single`, `sanitize_callback`, `auth_callback`. Im Plugin keinerlei `get_field()`/`have_rows()`-Aufrufe. Im Theme erlaubt, aber defensiv mit `function_exists('get_field')` und Fallback auf `get_post_meta`.

**Begründung:** Plugin überlebt Wechsel/Deaktivierung von ACF. Native WP-Schnittstelle ist REST-fertig (`show_in_rest`), Block-Editor-fertig, ohne Lizenzkosten. Migration der bestehenden Daten ist ein Datenproblem, kein Architekturproblem — gleiche Meta-Keys, ACF füllt sie genauso wie native Code.

**Konsequenz:** Theme-Migration: ACF-Calls kapseln, nicht hart fordern. Plugin: Migrations-Helfer schreibt Default-Werte für leere Posts (Graceful Degradation aus `wordpress.md` § 1.1).

---

## 5. Verification (für Phase B, pro Task)

- `phpcs --standard=WordPress` auf geänderten Dateien → 0 Errors / 0 Warnings.
- `find {plugins,themes}/{depeur-food,kadence-child} -name "*.php" -exec php -l {} \;` → keine Syntax-Fehler.
- `wp-env run cli wp plugin check depeur-food` → no failures.
- Manuelles Smoke-Testing in `localhost:8888`: Plugin aktivieren, Settings öffnen, Toggle an/aus, Frontend laden, `wp-content/debug.log` checken.
- Bei post-type-relevanten Modulen: Test gegen `post` UND einen registrierten CPT (z. B. via `register_post_type` in einem mu-plugin für die Test-Session).
- Bei custom-field-relevanten Modulen: Post mit befüllten Feldern UND Post mit leeren Feldern.
- Self-Review-Hook nach `wordpress.md` § 11 punkt-für-punkt vor jedem Ready-Statement.
- Git-Commit mit Conventional-Commit-Message; kein `git push` ohne explizites Go.

### Remote-Test-Hinweise (Phase B, ab Push-Approval)

Lokales wp-env läuft PHP 8.2, Test-Server läuft PHP 8.4 (`Food-Blog_Template`-WebApp). Forward-kompatibel, aber 8.4 loggt Deprecations, die 8.2 lokal nicht zeigt. Bei Remote-Tests deshalb parallel `tail -f` auf das PHP-Error-Log laufen lassen, um 8.4-spezifische Deprecation-Warnings zu erwischen.

Beispiel-Befehle (Pfad: `/home/runcloud/webapps/Food-Blog_Template/`, Push erst nach explizitem Go pro Feature):

```bash
# Lese-Operationen (Discovery, jederzeit erlaubt)
ssh runcloud-test "wp plugin list --path=/home/runcloud/webapps/Food-Blog_Template"
ssh runcloud-test "wp option get depeur_food_modules --path=/home/runcloud/webapps/Food-Blog_Template"

# Push (nur nach Push-Approval pro Feature)
rsync -avz --delete plugins/depeur-food/ runcloud-test:/home/runcloud/webapps/Food-Blog_Template/wp-content/plugins/depeur-food/
ssh runcloud-test "wp plugin activate depeur-food --path=/home/runcloud/webapps/Food-Blog_Template"

# Smoke + Logs (jederzeit nach Push erlaubt)
curl -sI https://<test-domain>/ | head -1
ssh runcloud-test "tail -f /home/runcloud/logs/<webapp-id>/php-error.log"
```

Der konkrete Logfile-Pfad und die Test-Domain sind in Phase B beim ersten Remote-Probelauf zu verifizieren (`ssh runcloud-test "ls /home/runcloud/logs/"` und `ssh runcloud-test "wp option get siteurl --path=/home/runcloud/webapps/Food-Blog_Template"`).

### End-to-End-Verifikation für das gesamte Plugin (vor Live-Rollout)

- W3C-Validator-Stichprobe auf einem Recipe-Post.
- Lighthouse ≥ 90 in allen vier Kategorien (lokal in wp-env messbar).
- Schema-Validator (Google Rich Results Test) gegen Recipe-Output.
- Toggle aller Module nacheinander aus → Frontend bleibt fehlerfrei (Graceful Degradation).

---

## 6. wordpress.md — Updates Required (vom User zu pflegen)

Folgende Stellen widersprechen den frozen ADRs und müssen vom User vor Beginn der Implementierung in `wordpress.md` angepasst werden:

1. **§ 1.1, Bulletpoint 3** — Aktuell: *"Features werden über zentrales Admin-Panel an/aus geschaltet (Settings API, Optionsname `depeur_food_settings` als single-array option, NICHT viele einzelne Optionen)."* — Zu ersetzen durch eine Formulierung, die Multi-Option-Pattern beschreibt: pro Modul eigene Option `depeur_food_{slug}` (Array, autoload=no für sensible Daten), Master-Liste der aktiven Module in `depeur_food_modules`. Begründung verweist auf ADR-1.
2. **§ 4.5** — Aktuell: *"`depeur_food_settings` darf autoloaden (klein, häufig gebraucht)."* — Zu ersetzen: `depeur_food_modules` (Master-Liste) darf autoloaden, einzelne Modul-Optionen `depeur_food_{slug}` autoladen nur, wenn das Modul keine sensiblen Daten enthält; Module mit Credentials/API-Keys autoloaden nicht.

Optional (kein Blocker):

3. **§ 11 Punkt 4** — *"Falls Code post-type-relevant: gegen `post` UND einen CPT getestet?"* — Klingt Single-CPT, ADR-4 fordert post-type-agnostisch via Liste. Formulierung ggf. erweitern: *"… gegen mindestens zwei in `depeur_food()->get_supported_post_types()` konfigurierte Types getestet."*

Diese Edits werden vom User selbst vorgenommen. Plugin-Code geht unabhängig davon nach den ADRs.

---

## 7. Open Questions (nicht-blockierend für Phase-A-Abschluss)

- **OQ-1:** REST-API-Konsumenten der Legacy-Routes `wl/v1/posts` / `wrm/v1/rating/*`? Klärung vor Beginn von Task 11+ (Modul `rest-extensions`).
- **OQ-2:** Newsletter-Provider-Scope — nur Flodesk vs. Multi-Provider von Tag eins (Mailchimp/Plain HTML)? Klärung vor Beginn von Task 7 (Modul `newsletter`).
- **OQ-3:** Verwendung von `mu-plugins/` (aktuell leer, in wp-env gemappt)?

Open Items (User-Action async, nicht-blockierend):

- **Item-1:** SSH-Alias `runcloud-test` verfügbar (Linux Testserver, PHP 8.4.20, User `runcloud`, verifiziert in dieser Session). Test-WebApp ist `/home/runcloud/webapps/Food-Blog_Template/` — bestehendes Test-WordPress, freigegeben für Phase-B-Remote-Tests. Lese-Operationen sind jederzeit zulässig; Schreibe-/Push-Operationen erst nach explizitem Push-Approval pro Feature.
