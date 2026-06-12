# WordPress Development Standards – Depeur Food Suite

Diese Datei ist die **verbindliche Referenz** für alle Code-Arbeiten an Plugin und Child-Theme. Vor jedem "Task complete" muss der Code gegen jeden Punkt dieser Liste geprüft werden.

---

## 1. Architektur-Prinzipien

### 1.1 Plugin-Struktur (Depeur Food)

Modulares Feature-Toggle-Pattern wie das bestehende Depeur Suite Plugin (Toggle-Mechanik), aber mit eigener PSR-4-Architektur (siehe § 2.3):

- Hauptklasse `Depeur\Food\Core\Plugin` als Singleton, zugänglich über den globalen `depeur_food()`-Helper.
- Plugin-Klassen folgen PSR-4 (PascalCase): Core unter `src/`, Module unter `modules/{slug}/` (Discovery via `manifest.php` + `module.php`). Kein `class-{name}.php`.
- Features werden über zentrales Admin-Panel an/aus geschaltet (Settings API). 
  Settings-Pattern: Multi-Option — pro Modul eine eigene Option 
  `depeur_food_{slug}` (Array, autoload=no für Module mit sensiblen Daten wie 
  API-Keys). Master-Liste der aktiven Module: `depeur_food_modules` 
  (autoload=yes). Tabbed Admin-UI unter 
  `admin.php?page=depeur-food-settings&tab={slug}`. Begründung: siehe 
  PLAN.md § 4 ADR-1.
- Feature-Klassen werden NUR instanziiert wenn aktiv (Lazy-Loading via `if ( $this->is_feature_active( 'feature_x' ) )`).
- Hooks werden im Feature-Konstruktor registriert, nirgendwo sonst.
- Keine Globals außer dem Singleton-Helper.
- **POST-TYPE-AGNOSTISCH.** Das Plugin funktioniert mit `post`, `page` und beliebigen Custom Post Types. Welche CPTs unterstützt werden, wird in den Plugin-Settings konfiguriert (Mehrfachauswahl). Default: `post`. Hooks, Queries, Meta-Felder und Schema-Output dürfen NIRGENDS einen Post-Type hardcoden. Wenn ein Feature post-type-spezifische Logik braucht, holt es die konfigurierten Types über die Plugin-API (z.B. `depeur_food()->get_supported_post_types()`).
- **GRACEFUL DEGRADATION** bei fehlenden Custom-Feldern. Wenn ein Post die erwarteten Werte (Zubereitungszeit, Zutaten, Bewertung, etc.) nicht hat, fällt der Output sauber zurück: kein leeres Schema-Objekt im JSON-LD, kein "undefined" im Frontend, keine PHP-Notices, keine HTML-Strukturen die mit leerem Inhalt rendern. Defensive `empty()`/`isset()`-Checks sind Pflicht.
- **KEINE Runtime-Dependency auf ACF.** Custom Fields werden via `register_post_meta()` mit Schema und `show_in_rest` definiert, Blöcke nativ via `block.json` + Edit/Save-Komponenten. ACF darf im Discovery gelesen werden um Legacy-Code zu verstehen, darf aber nicht ins finale Plugin migriert oder als Dependency deklariert werden.

### 1.2 Child-Theme (Kadence-Child)

- Erbt von Kadence, überschreibt nur was nötig ist.
- Eigene Funktionalität in `inc/`-Unterordnern, nach Funktionsbereich gruppiert.
- Keine Plugin-Funktionalität ins Theme – nur Darstellungs-/Template-Logik.
- Performance-Optimierungen (dequeue, defer, critical CSS) in `inc/performance.php`.
- Schema/SEO-Erweiterungen in `inc/schema.php`.
- Recipe-spezifische Template-Anpassungen in `inc/recipe.php`.

### 1.3 Trennung Plugin vs. Theme

- **Plugin** = Funktionalität, Daten, Admin-Logik, Cache-Management.
- **Theme** = Darstellung, Asset-Loading, Layout-Hooks.
- Wenn unklar wo etwas hingehört: Plugin (überlebt Theme-Wechsel).
- Schema-Markup gehört ins Theme wenn rein darstellungsabhängig, ins Plugin wenn datengetrieben.

---

## 2. Coding Standards

### 2.1 PHPCS

- Standard: `WordPress` ruleset (WPCS).
- Zusätzlich: `PHPCompatibilityWP` mit Target `8.2-`.
- Zero warnings, zero errors für Production-Code.
- Run vor jedem Commit: `phpcs --standard=WordPress plugins/depeur-food/`.

### 2.2 PHP-Anforderungen

- Minimum PHP-Version: **8.2** (matcht den RunCloud-Default).
- PHP 8.0+ Features sind erlaubt und erwünscht (named arguments, match expression, constructor property promotion, readonly properties, enums).
- Strict Types optional, aber wenn dann konsistent pro Datei.

### 2.3 Naming Conventions

- Funktionen, Variablen, Hooks: `snake_case`.
- Klassen: `PascalCase`, via Namespace `Depeur\Food\…` (Core) bzw. `Depeur\Food\Modules\{Slug}\…` (Module) — kein `Depeur_Food_`-Klassen-Prefix nötig, das Namespacing übernimmt die Eindeutigkeit.
- Konstanten: `UPPER_SNAKE_CASE`, immer mit Prefix `DEPEUR_FOOD_`.
- Optionen, Transients, Meta-Keys, Hooks, CSS-Klassen: alle mit Prefix `depeur_food_` oder `df_` (kürzer, aber konsequent).
- Dateinamen: **PSR-4 PascalCase** passend zum Klassennamen (`Plugin.php`, `SettingsPage.php`), geladen über `src/Helpers/Autoloader.php` — **nicht** `class-{name}.php`. Ausnahmen für Lowercase-Filenames: WordPress-Konventions-Files (`depeur-food.php`, `uninstall.php`) am Plugin-Root, Modul-Reserved-Files (`manifest.php`, `module.php`) am Modul-Root. Hintergrund: der WP-Plugin-Header sucht den Plugin-Entry nach Konvention, die Modul-Discovery sucht `manifest`/`module` nach hardcodiertem Namen. Keine `*.php`-Klassen am Modul-Root (FS-Safety gegen `module.php`/`Module.php`-Kollision auf case-insensitive FS, siehe BRIEF § 2.7).

### 2.4 Inline Documentation

- PHPDoc für alle public/protected Funktionen, alle Klassen, alle Hooks.
- `@since` Tag mit Plugin-Version.
- `@return` und `@param` Pflicht.
- Hooks dokumentieren via `do_action()` / `apply_filters()` Block-Comments im WordPress-Style.

### 2.5 Code-Lesbarkeit für Wartung in 6+ Monaten

Ziel: Wer den Code in einem Jahr zum ersten Mal liest, versteht nicht nur **was er tut**, sondern **warum er so existiert** und welche Alternativen verworfen wurden. Diese Anforderung **ergänzt § 2.4 (PHPDoc), ersetzt sie nicht**. Geltungsbereich: alle Files in `plugins/depeur-food/` und `themes/kadence-child/`. Code in `_references/` ist read-only und wird nicht annotiert.

- **Why-Kommentar bei nicht-trivialen Funktionen.** Über dem Doc-Block ein kurzer Block-Kommentar, der den Use-Case und das gelöste Problem nennt — nicht das Was (das steht im Code und PHPDoc):
  ```php
  // WPRM-Recipes haben eigene save-Hooks, die nicht von save_post abgedeckt
  // werden. Ohne diesen Listener bleiben Cache-Purges bei Recipe-Edits aus.
  /**
   * @since 1.0.0
   */
  public function on_recipe_save( int $recipe_id ): void { ... }
  ```
- **Inline-Kommentar bei Hook-/Filter-Aufrufen.** Direkt über `add_action`/`add_filter`: warum hängen wir uns hier ein, was erwarten wir vom Hook, was passiert wenn er nicht feuert.
- **Magic Numbers / Hardcoded Strings.** Jeder lokale Wert kommentiert (`// 86400 = 24h, weil Cloudflare-Edge-TTL für statische Assets`). Konstanten sind dem Magic-Number-Inline vorzuziehen; bleibt der Wert lokal, dann wenigstens kommentieren.
- **`register_post_meta` / `register_user_meta` / `register_term_meta` / `register_setting`.** Block-Kommentar darüber: was speichert das Feld, wer schreibt rein, wer liest raus, was passiert bei leerem Wert (Graceful Degradation aus § 1.1).
- **Komplexe Conditionals (mehr als zwei verschachtelte if).** Davor eine 1–3-Zeilen-Plain-English-Zusammenfassung der Logik.
- **Provider-/Strategy-/Pattern-Klassen.** Am Klassen-Anfang ein Block-Kommentar (vor der `class`-Deklaration), der das Pattern, die Rolle in der Plugin-Architektur und die Stellen referenziert, an denen die Klasse gewählt/instanziiert wird.

---

## 3. Security (nicht verhandelbar)

### 3.1 Input

- **Alle** `$_GET`/`$_POST`/`$_REQUEST`/`$_COOKIE`-Werte über `sanitize_*` Funktionen leiten.
- Richtigen Sanitizer wählen: `sanitize_text_field`, `sanitize_email`, `absint`, `sanitize_key`, `wp_kses_post`, etc.
- Niemals `$_REQUEST` benutzen wenn `$_GET` oder `$_POST` reicht.

### 3.2 Output

- **Alle** Variablen beim Output escapen: `esc_html`, `esc_attr`, `esc_url`, `esc_js`, `wp_kses_post`.
- Kein `echo $variable;` ohne Escape.
- Translations escapen: `esc_html__()`, `esc_attr_e()` etc.

### 3.3 Nonces & Capabilities

- Jedes Form, jeder AJAX-Handler, jeder REST-Endpoint:
  - Nonce-Check: `wp_verify_nonce` oder `check_admin_referer` / `check_ajax_referer`.
  - Capability-Check: `current_user_can( 'manage_options' )` (oder spezifischer).
- Reihenfolge: Capability → Nonce → Sanitize → Process → Escape Output.

### 3.4 Datenbank

- Immer `$wpdb->prepare()` für variable Queries.
- Niemals String-Concatenation in SQL.
- Bei Schema-Änderungen: `dbDelta()` mit korrektem Format, nicht raw `CREATE TABLE`.
- Eigene Tabellen mit `$wpdb->prefix` und Plugin-Suffix: `{$wpdb->prefix}depeur_food_{name}`.

---

## 4. Performance

### 4.1 Hooks

- Conditional Hook-Registration: nur registrieren wenn Hook auf der jeweiligen Seite überhaupt feuern kann.
- Keine `init`-Hooks für Frontend-only Code, stattdessen `wp` oder spezifischer.
- Keine teuren Operations in `init` ohne Caching.

### 4.2 Caching

- Transients für teure Berechnungen (`set_transient` / `get_transient`).
- Object Cache aware: `wp_cache_get` / `wp_cache_set` mit eigener Group (`depeur_food`).
- Bei Cache-Invalidierung: gezielt purgen, nicht `wp_cache_flush()`.
- Edge-/CDN-Purges (Cloudflare, RunCloud, Bunny) werden NICHT direkt aus dem aufrufenden Code ausgelöst, sondern über die plugin-eigene Hook-First-Fassade: `do_action( 'depeur_food/cache/purge', new \Depeur\Food\Cache\Purge_Context( … ) )`. Das `cache-bridge`-Modul übersetzt die Action in Provider-Aufrufe (siehe ADR-3).
- Provider-Pattern statt Hardcode: `Provider_Log_Only` ist Always-on-Fallback (loggt jeden Purge), Cloudflare/RunCloud-Hub nutzen eigene Modul-Credentials, die Suite-`BunnyApi` wird nur optional und rein lesend über einen `class_exists`-gegateten Provider angebunden. depeur-food implementiert seine Cache-Bridge selbst und ist nicht von der Suite abhängig.

### 4.3 Datenbank

- Nie `query_posts()`, immer `WP_Query` oder `get_posts`.
- `posts_per_page => -1` vermeiden, immer Limit setzen.
- `no_found_rows => true` wenn Pagination nicht gebraucht.
- `update_post_meta_cache => false` wenn Meta nicht gebraucht.
- Eigene Queries cachen.

### 4.4 Asset-Loading (Theme)

- Kein globaler `wp_enqueue_*` ohne Conditional.
- Critical CSS inline im `<head>`, Rest deferred.
- JS grundsätzlich `defer` oder `async`.
- `wp_enqueue_script` mit `array( 'in_footer' => true, 'strategy' => 'defer' )` (WP 6.3+).
- Fonts self-hosted, mit `<link rel="preload">` für kritische Schnitte.

### 4.5 Autoload

- Optionen mit Vorsicht autoloaden (`autoload = no` für seltene/große Optionen).
- `depeur_food_modules` (Master-Liste aktiver Module) darf autoloaden 
  (klein, häufig gebraucht). Einzelne Modul-Optionen `depeur_food_{slug}` 
  autoladen nur, wenn das Modul keine sensiblen Daten enthält. Module mit 
  Credentials/API-Keys autoloaden NICHT.
- Logs, Caches, History → autoload nein.

---

## 5. Internationalization (i18n)

- Text Domain: `depeur-food` (Plugin) bzw. `kadence-child` (Theme).
- Alle User-Facing Strings durch `__()`, `_e()`, `_n()`, `_x()` etc. mit Text Domain.
- Translation-Funktionen mit Escape: `esc_html__()` etc.
- `load_plugin_textdomain()` in Hauptklasse, Hook `init`.
- `.pot`-Datei generierbar via `wp i18n make-pot`.
- Keine konkateinierten Strings, immer Platzhalter-Variant: `sprintf( __( 'Hello %s', 'depeur-food' ), $name )`.

---

## 6. Admin-UX

### 6.1 Accessibility

- Form-Labels immer mit `<label for="...">`.
- ARIA-Attribute wo nötig (`aria-describedby` für Hints etc.).
- Tastaturnavigation testbar.
- Kontrast nach WCAG AA (Settings-Seiten erben das von WP-Admin, also meist ok).

### 6.2 Admin-UI Documentation

Ziel: Nach Plugin-Aktivierung kann jeder Admin (auch nicht-technische) die Settings-Page öffnen und ohne externe Doku verstehen, was das Plugin kann und wie es konfiguriert wird. Zielgruppe ist der **Site-Owner**, nicht der Entwickler — technische Details gehören in `HOOKS.md` und `README.md`.

- **Settings-Page-Intro.** Jedes Modul-Tab hat oben einen 2–4-Sätze-Absatz: was macht dieses Modul, welche Sites profitieren, wann sollte man es aktivieren, wann nicht.
- **Field-Description.** Jedes Settings-Feld hat eine Description (mindestens ein Satz): was bewirkt dieser Toggle/Wert, was ist der Default-Effekt, was passiert wenn aktiviert, was passiert wenn deaktiviert.
- **Beispiel-Werte.** Bei nicht-offensichtlichen Zahlen oder Strings: ein Beispiel ("z. B. `86400` für 24 h").
- **Cross-Modul-Abhängigkeiten.** Explizit dokumentiert ("Dieses Modul nutzt die Cache-Bridge — stelle sicher, dass `cache-bridge` aktiv ist").
- **Tutorial-Sektion bei komplexen Modulen.** `schema-engine`, `cache-bridge`, `newsletter` bekommen eine Help-Sektion auf ihrer Settings-Page (collapsible `<details>`-Block oder eigener Tab) mit typischen Use-Cases, FAQs und Verlinkung zu `HOOKS.md` für Devs.
- **i18n.** Alle Texte über `__()` / `esc_html__()` mit Text Domain `depeur-food`, so dass spätere Übersetzung möglich ist. Keine konkatenierten Strings (siehe § 5).
- **Tonality.** Nicht-technisch wo möglich. "Aktiviere dies, wenn du …" statt "Hook X feuert mit Argument Y".

---

## 7. REST API & AJAX

- Eigene Endpoints unter Namespace `depeur-food/v1`.
- `permission_callback` immer setzen (nie `__return_true` ohne Begründung).
- Schema-Definition in `register_rest_route` für Validierung.
- Response über `WP_REST_Response`, nicht direkt `wp_send_json`.
- AJAX nur für Backend, REST API für Frontend-Aufrufe.

---

## 8. Testing

### 8.1 Static Analysis

- `phpcs` muss durchlaufen ohne Errors/Warnings.
- WordPress Plugin Check (`wp plugin check depeur-food`) muss bestanden werden.
- `php -l` (Syntax-Lint) auf alle PHP-Dateien.

### 8.2 Funktional

- wp-env up, Plugin aktivieren, Frontend laden, Console + PHP-Error-Log prüfen.
- Bei jedem Feature: Toggle an → Funktion testen → Toggle aus → Funktion verschwunden.
- Test gegen mindestens zwei Post-Types (Standard `post` und ein registrierter CPT) um die Post-Type-Agnostik zu validieren.
- Test gegen Posts MIT befüllten Custom-Fields und Posts OHNE (Graceful Degradation).
- Multisite-Compatibility nicht erforderlich (markieren wir explizit als nicht supported).

### 8.3 Manuelle Test-Checklist (vor Release)

1. Plugin-Aktivierung ohne PHP-Notice.
2. Plugin-Deaktivierung räumt Hooks auf.
3. Plugin-Deinstallation (uninstall.php) räumt Optionen + Tabellen.
4. Settings speichern → Reload → Werte persistent.
5. Jedes Feature einzeln togglen, Verhalten prüfen.
6. Funktionalität auf `post` UND auf einem CPT testen.
7. Frontend HTML valide (W3C Validator stichprobenartig).
8. Lighthouse Score ≥ 90 (Performance, Best Practices, SEO).

---

## 9. Git-Workflow

- Branch-Strategie: `main` ist deploybar, Feature-Branches `feat/{name}`, Bugfixes `fix/{name}`.
- Conventional Commits: `feat:`, `fix:`, `chore:`, `docs:`, `refactor:`, `perf:`, `test:`.
- Commit-Body wenn nötig erklärt das *Warum*, nicht das *Was*.
- Jeder Commit muss kompilieren (PHP-Lint clean).
- Vor `git push`: PHPCS lokal durchgelaufen.

---

## 10. Dokumentations-Pflicht

Folgende Dateien werden gepflegt:

- **CLAUDE.md** (von Claude Code) – Session-State, aktuelle Aufgabe, offene Fragen, Architektur-Snapshot.
- **README.md** (pro Plugin/Theme) – Installation, Features, Konfiguration.
- **CHANGELOG.md** (pro Plugin/Theme) – Versionsverlauf nach Keep-a-Changelog.
- **HOOKS.md** (pro Plugin) – Liste aller Custom Actions/Filters mit Signatur und Zweck.
- Inline PHPDoc – wie in 2.4 beschrieben.

---

## 11. Self-Review-Hook (vor jedem "Ready"-Statement)

Vor jeder Aussage "Task ist abgeschlossen" oder "Bereit für Review" wird folgende Prüfung explizit durchlaufen:

1. PHPCS auf geänderten Dateien clean? `phpcs --standard=WordPress {paths}`
2. PHP-Syntax-Check clean? `find . -name "*.php" -exec php -l {} \;`
3. Plugin Check ausgeführt? `wp plugin check depeur-food`
4. Falls Code post-type-relevant: gegen `post` UND einen CPT getestet?
5. Falls Code Custom-Field-relevant: mit befüllten UND leeren Feldern getestet?
6. Diese wordpress.md Punkt für Punkt mental durchgegangen, kritische Sektionen nochmal explizit gelesen (Security, Performance, Naming).
7. CLAUDE.md aktualisiert mit aktuellem Stand und offenen Fragen.
8. Git-Status sauber, alle relevanten Änderungen committed mit aussagekräftiger Message.

Wenn auch nur ein Punkt nicht erfüllt ist: kein "Ready"-Statement, sondern Issue dokumentieren und beheben.

---

## 12. Pre-Implementation-Review

Bevor ein geschäftslogik-tragendes Modul implementiert wird, läuft ein **verpflichtender Architektur-Review-Schritt** zwischen User und Claude. Begründung: die Legacy-Plugins, die wir migrieren, sind ohne diesen Schritt entstanden und sind heute schwer nachvollziehbar, weil das Warum nicht festgehalten wurde. Diesen Fehler wiederholen wir nicht.

### 12.1 Geltungsbereich

- **Pflicht** ab dem ersten geschäftslogik-tragenden Modul. In der Sprint-Reihenfolge aus `CLAUDE.md` heißt das aktuell: ab Task 5 (`cache-bridge`). Maßgeblich ist das semantische Kriterium (geschäftslogik-tragend), nicht die Task-Nummer — letztere ist nur ein bei Renumbers nachzuführendes Beispiel.
- **Nicht erforderlich** für strukturelle Tasks ohne Geschäftslogik: Plugin-Bootstrap, Core-Klassen, Beispiel-Modul sowie Tab-System/Core-UI samt zugehöriger UI-Erweiterungen (z. B. Modul-Toggle).

### 12.2 Modul-Brief

Vor jeder Implementierung erstellt Claude ein 1–2-seitiges Brief-Dokument unter `plugins/depeur-food/modules/{slug}/BRIEF.md` mit:

1. **Funktionalitäts-Inventar** — was macht das Modul (Migrations-Quelle aus `_references/legacy-plugins/` plus neue Features).
2. **Public API** — welche Hooks/Filters/REST-Routes/Shortcodes/AJAX-Actions werden registriert; mit Signatur und Zweck.
3. **Datenstrukturen** — Meta-Keys (mit `register_post_meta`-Schema), Optionen, Cache-Keys, Custom Tables.
4. **Settings-UI** — welche Felder, ihre Defaults, ihre Cross-Modul-Abhängigkeiten.
5. **Edge-Cases** — leere Daten (Graceful Degradation aus § 1.1), fehlende Drittplugins (z. B. WPRM nicht aktiv), Multi-CPT, Mehrsprachigkeit.
6. **Bekannte Risiken / Annahmen / Open Questions.**
7. **Konkrete Datei-Liste**, die angelegt/geändert wird.

### 12.3 Workflow

1. Claude liest den entsprechenden Legacy-Plugin-Code in `_references/legacy-plugins/` nochmal vollständig (auch wenn schon einmal in der Discovery erfasst).
2. Claude schreibt `BRIEF.md` im Modul-Verzeichnis.
3. User reviewt: korrigiert Logik-Fehler, ergänzt vergessene Use-Cases, gibt explizit frei. Korrekturen werden im Brief eingepflegt.
4. **Erst dann** beginnt die Implementierung. Sub-Task-Reihenfolge in der TodoWrite-Liste: "Brief schreiben + freigeben lassen" → "Implementieren".

### 12.4 Brief als lebende Dokumentation

`BRIEF.md` ist **nicht** Wegwerf-Plan, sondern bleibt als Architektur-Snapshot des Moduls erhalten und wandert mit. Bei späteren Änderungen am Modul (Phase 2+) wird `BRIEF.md` mit-aktualisiert — er beschreibt immer den aktuellen Zustand des Moduls.
