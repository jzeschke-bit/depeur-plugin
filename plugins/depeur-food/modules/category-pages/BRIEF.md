# BRIEF — Theme→Plugin Ein-Durchlauf-Umbau: `kadence-child` (sauber) + Modul `category-pages`

**Status:** v0.1 ENTWURF (2026-07-08) · Schema 1.1 · Klassifikation: geschäftslogik-tragend + Frontend-Rewrite + neuer Security-Pfad (AJAX/REST) → § 12-pflichtig. **Code erst nach Freigabe.**

Quellen: `_references/legacy-themes/alkipedia/` (14 Dateien) + zwei Deep-Read-Specs (`scratchpad/spec-category-engine.md`, `spec-presentation.md`) + `_references/acf-discovery.md`.

## 1. Ziel (ein Satz)
Das Alt-Theme `alkipedia 3.0` durch ein **frisches, dünnes `kadence-child`** ersetzen, das **nur Präsentation** enthält, während die gesamte Logik (Kategorie-Seiten, „Was koche ich heute", Favoriten-Archiv, Static-Page-Intro, Tag-Gruppen, Schema, Sprache) **aus dem Plugin** kommt — so, dass man das neue Child installiert und **alles weiterläuft**, nur eben plugin-getragen und konfigurierbar statt hartkodiert.

## 2. Leitprinzip
**Logik → Plugin** (post-type-agnostisch via `depeur_food()->get_supported_post_types()`, ADR-4). **Reines Markup/CSS → Child.** **Alles, was ohnehin per Dedup wegfällt, wird gar nicht erst übernommen.**

## 3. Master-Zuordnung (übernehmen / aus Plugin / weg)

| Legacy-Bestandteil | Ziel |
|---|---|
| `single-rezeptkategorie-template.php` (Query-Logik) | **Plugin** `category-pages` (Engine) + **dünnes** Child-Template (Markup ruft `[df_category_page]`) |
| `was-koche-ich-heute.php` (Filter + AJAX) | **Plugin** `category-pages` (Filter-Engine + REST-Endpoint m. Nonce) + dünnes Child-Template (`[df_recipe_filter]`) |
| `template-favorite-archive.php` (Cookie-Query) | **WEG** (Cookie/WP_Query raus) → `[df_favorites_archive]` (favorites-Modul, existiert). Child-Template optional. |
| `template-parts/content/archive.php` (static_page-Intro) | **BLEIBT** als Child-Markup; Intro-ID-Auflösung → **Plugin**-Helper (post-type-agnostisch) |
| `template-parts/content/*` (entry_author/footer/loop_thumbnail/tags/single-entry) | **BLEIBEN** als Child-Präsentation; nur `alkipedia_get_supported_post_types()` → `depeur_food()->get_supported_post_types()` |
| Multi-Taxonomie-Engine (`…terms_from_acf` / `build_multi_taxonomy_query` / `validate_and_group_tag_ids`) | **Plugin** `category-pages` (neu, mit Bugfix + AND/OR-Konfig) |
| `filter_recipes_handler` (AJAX ohne Nonce) | **Plugin** `category-pages` → **REST mit Nonce** (favorites-Muster) |
| Tag-Gruppen (`tag_group`-ACF + Admin-Spalte/Sortierung) | **Plugin** `category-pages` (Term-Meta via `Field_Provisioner` + Admin-Spalte) |
| `wpse107459_add_cpt_author` / `alkipedia_add_cpt_to_tag_archives` (`pre_get_posts`) | **Plugin** `category-pages` (post-type-agnostisch, je Toggle) |
| `alkipedia_page_pagination_rewrite` | **Plugin** `category-pages` — **eng gefasst** auf Kategorie-Seiten (Legacy-Regel ist greedy) |
| `alkipedia_extend_related_posts_for_cpt` (kadence_related_posts) | **Child** (Kadence-spezifischer Filter; Logik post-type-agnostisch belassen) |
| `custom_css_for_static_page` (inline `<style>`) | **Child** `style.css` (statisch, PHP-Bedingung entfällt) |
| `rank-math.php` (Autor-Schema) | **WEG** → schema-engine (existiert). **publishingPrinciples** → schema-engine ergänzen; **Canonical-Fix paginierte Kat.-Seiten** → `category-pages` |
| `LanguageLink` / `lang_tag` (hreflang + Footer-Switcher) | **WEG (Dedup)** → language-selector (existiert). Footer-Switcher via **Child-Einzeiler** auf `kadence_footer_navigation` |
| `alkipedia_get_supported_post_types()` / `…taxonomy_mapping()` | **WEG** → Plugin-Core-Resolver + `get_object_taxonomies()` |
| `custom_page_taxonomy` (`page_category`) | **WEG** falls ungenutzt (live prüfen); sonst content-types |
| `alkipedia_add_custom_post_type` / `…add_taxonomy_mapping` (No-Op-Stubs) | **WEG** |
| `test-multi-taxonomy.php`, auskommentierte Doppel-Funktionen, `screenshot.` (0-Byte) | **WEG** (Müll) |

## 4. Modul `category-pages` (Namespace `Depeur\Food\Modules\CategoryPages`)

### 4.1 Konfigurations-Ansatz — **A: Meta-Box je Seite** (Empfehlung)
Eine normale `page` bekommt ein „Kategorie-Seite"-Panel (Felder via `Field_Provisioner`, wie die anderen Module). Migrationsnah (heutige Seiten sind Pages), kein CPT nötig. Global-Defaults im Settings-Tab. *(Alternative B — eigener CPT `df_category_page` — nur, wenn eine zentrale Verwaltungsliste gewünscht ist; mehr Migrationsaufwand. → offene Entscheidung § 10.)*

### 4.2 Rendering-Naht (statt `$wp_query`-Global-Fake)
Der fragilste Legacy-Teil (is_single/is_archive manuell umschalten, `$wp_query` für Pagination ersetzen) wird **nicht** portiert. Stattdessen: das Modul liefert **Shortcodes/Template-Tags**, die die Query selbst fahren und Grid + Pagination als HTML zurückgeben:
- `[df_category_page]` — kuratierte Kategorie-Seite (Multi-Taxonomie-AND/OR, Seite-1-Vorschau + Folgeseiten-Grid, konfigurierbar).
- `[df_recipe_filter]` — „Was koche ich heute" (Filter-Bubbles + Grid + „Mehr laden" via REST).
- Pagination über `paged`-Query-Var + eng gefasste Rewrite; kein Objekt-Fake.

### 4.3 Multi-Taxonomie-Engine (Neubau, korrigiert)
- Term-Auflösung aus Konfig (Meta) statt 8 gestreuter `rezept_*`-ACF-Felder → **ein** Term-Selektor „Terms je Taxonomie".
- `build_query()`: `post_tag` → `tag__and`/`tag__in` (je Modus), andere Taxonomien → `tax_query`-Einträge; **AND/OR pro Gruppe konfigurierbar** + Relation zwischen Gruppen (heute stur AND).
- **Bugfix:** `validate_and_group_tag_ids` referenzierte undefiniertes `$tag_id` → korrekt implementieren.
- post-type-agnostisch: Ziel-Typen aus `get_supported_post_types()`, Taxonomien aus `get_object_taxonomies()`.

### 4.4 „Was koche ich heute" — Filter + REST
- Tag-Gruppierung: Terms nach `tag_group` (Term-Meta) in konfigurierbare Filter-Gruppen (heute 6 hartkodierte DE-Labels → Settings).
- **REST-Endpoint** `depeur_food/v1/recipe-filter` (GET, lesend, **Nonce** `X-WP-Nonce` wie favorites) → `{ content, title, hasMore }`. Ersetzt den nonce-losen `admin-ajax`-Handler. Result-Cap gegen anonymen Query-Spam.
- Frontend-JS als enqueuetes **Vanilla**-Asset (`df_`-Prefix), kein Inline-`<script>` (Asset-Convention).

### 4.5 Static-Page-Intro (post-type-agnostisch)
Helper `intro_page_id( $term_or_user )` liest die Intro-Seiten-ID (Meta) für **jedes** Term-/Author-Archiv (nicht nur `category`). Feld via `Field_Provisioner` (term-/user-meta), Legacy-Keys `static_page`/`static_page_for_author` als Startwert.

### 4.6 Tag-Gruppen-Admin
`tag_group` als **term-meta** (`register_term_meta` via `Field_Provisioner`, exakt der Legacy-Key → Daten bleiben) + Admin-Spalte/Sortierung auf `edit-post_tag`.

### 4.7 Glue
`pre_get_posts`-Erweiterung „CPTs in Tag-/Author-Archiven" (je Toggle); eng gefasste Pagination-Rewrite nur für konfigurierte Kategorie-Seiten; Canonical-Fix paginierter Kategorie-Seiten (aus rank-math.php).

### 4.8 Dateien (Entwurf)
`manifest.php`, `module.php`, `config/fields.php` (+`groups.php`), `Query/Term_Resolver.php`, `Query/Query_Builder.php`, `Frontend/Category_Page.php` (`[df_category_page]`), `Frontend/Recipe_Filter.php` (`[df_recipe_filter]`), `Rest/Filter_Controller.php`, `Frontend/Assets.php` (+`assets/df-recipe-filter.js/.css`), `Support/Intro_Page.php`, `Support/Tag_Groups.php` (Term-Meta + Admin-Spalte), `Hooks/Archive_Query.php` (pre_get_posts), `Hooks/Rewrite.php`, `Admin/Settings.php` (Defaults-Tab).

## 5. Child-Theme `kadence-child` (im Repo `themes/kadence-child/`)

### 5.1 Struktur (dünn)
- `style.css` — Theme-Header (`Template: kadence`) + **nur essenzielles CSS**: Author-Box (`.entry-author-*`), Tags (`.taxonomy-post_tag`), Sidebar/Kategorie (`.primary-sidebar`, `.category-related`), Static-Page-Margin, bedingt Hero-Responsive. **KEIN** Favoriten-/Switcher-/App-Promo-CSS (Module bringen es mit).
- `functions.php` — **minimal**: Child-CSS enqueue; Footer-Switcher-Einzeiler `add_action('kadence_footer_navigation', fn() => print do_shortcode('[df_language_switcher]'), 100)`; Kadence-Related-CPT-Filter (Präsentation). **KEINE** Schema-/hreflang-/Query-Logik.
- `templates/` bzw. Page-Template-Header: `single-rezeptkategorie-template.php` → `[df_category_page]`; `was-koche-ich-heute.php` → `[df_recipe_filter]`; `template-favorite-archive.php` → `[df_favorites_archive]` (oder ganz ohne Sondertemplate via Shortcode-Block).
- `template-parts/content/*` — aus Legacy übernommen, **eine** Ersetzung: `alkipedia_get_supported_post_types()` → `depeur_food()->get_supported_post_types()`. `[thumbnail_favorite_button]` bleibt wörtlich (Alias zeigt aufs Plugin).

### 5.2 Integrations-Naht (Child ↔ Plugin)
Child ruft **nur** Shortcodes/Template-Tags des Plugins; keine direkten Klassen-Imports. Fällt ein Modul aus, degradiert der Shortcode leer (safe). Legacy-Aliase (`thumbnail_favorite_button` etc.) bleiben plugin-seitig gültig.

## 6. Ergänzungen an bestehenden Modulen
- **schema-engine:** `publishingPrinciples` am `publisher` ergänzen (aus rank-math.php).
- **language-selector:** nichts Neues (deckt hreflang + Switcher bereits ab); nur die Theme-Registrierungen entfallen.
- **favorites:** Archiv bereits abgedeckt (`[df_favorites_archive]`).

## 7. Sicherheit (nicht verhandelbar beim Neubau)
- „Was koche ich heute"-Endpoint: **REST + Nonce** (schließt die nonce-lose Legacy-Lücke) + Result-Cap.
- Alle Meta-Felder mit Sanitize (Field_Provisioner), Admin-Save mit Cap+Nonce.
- Rewrite eng fassen (keine greedy `^(.+?)/page/…`-Regel plugin-weit).

## 8. Build-Order (mehrere Sessions — ehrlich)
1. Modul-Skelett + Autoloader-Smoke (`CategoryPages`→`category-pages`) + config/fields.
2. Query-Engine (`Term_Resolver`, `Query_Builder`) + Bugfix + Unit-nahe Smokes.
3. `[df_category_page]` + Rewrite + Canonical + dünnes Rezeptkategorie-Template.
4. `[df_recipe_filter]` + REST-Endpoint (Nonce) + Vanilla-JS + Was-koche-Template.
5. Tag-Gruppen (Term-Meta + Admin-Spalte) + Static-Page-Intro-Helper + archive.php-Naht.
6. `pre_get_posts`-Toggles + Settings-Tab (Defaults) + schema-engine-`publishingPrinciples`.
7. Child-Theme-Gerüst (style.css, functions.php, template-parts, Templates) + Footer-Switcher.
8. Voll-Smoke auf Staging: neues Child aktivieren → alle drei Seiten + Archive + Favoriten + Schema + hreflang **einmalig** und korrekt; Alt-Theme-Dedup verifizieren (keine doppelten Tags).

## 9. Bekannte Legacy-Bugs — NICHT mitschleppen
- `validate_and_group_tag_ids`: `$tag_id`-statt-`$tag_ids`-Referenz → fixen.
- Pagination-Rewrite greedy/kollisionsanfällig → eng fassen.
- Pagination-Schwelle 10 ≠ Seite-1-Menge 4 → konsistent konfigurieren.
- Multi-Tax stur AND → AND/OR konfigurierbar.
- `rezept_{post_tags,categories,cocktail_tags,trinkspiel_tags,equipment_tags}` = Dead-Code (0 Werte) → nicht nachbauen; real steuert `rezept_tag`.

## 10. Offene Entscheidungen (vor/während Code klären)
- **OE-1:** Konfig-Ansatz **A (Meta-Box je Page)** vs. B (CPT `df_category_page`). Empfehlung A.
- **OE-2:** Seite-1-Verhalten der Rezeptkategorie beibehalten (Page-Content + 4-Posts-Vorschau, ab Seite 2 reines Grid) — ja/vereinfachen?
- **OE-3:** Favoriten-Archiv — reicht der Shortcode auf einer normalen Kadence-Seite (kein Sondertemplate)? Und: Plugin-JS-Karten vs. exakte Kadence-Loop-Optik (Design-Frage ans favorites-JS).
- **OE-4:** WPRM-/Rank-Math-/Social-Warfare-Kosmetik (style.css) — vorerst im Child lassen oder aufräumen?
- **OE-5:** Child-Theme im selben Repo (`themes/kadence-child/`) vs. eigenes Repo.

## 11. Smoke/Verifikation
`php -l`/phpcs/Autoloader-Smoke je Datei; Engine-Query gegen bekannte Terms; REST-Nonce (403 ohne Nonce); neues Child auf Staging → drei Seiten + Archive rendern, Favoriten-Herzen da, Schema einmalig (kein Doppel nach rank-math.php-Wegfall), hreflang einmalig (kein Doppel nach LanguageLink-Wegfall), „Cocktails kein 404".
