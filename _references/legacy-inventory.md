# Legacy-Inventory — Bestandsaufnahme der 7 Kern-Funktionen

> **Zweck:** Recon-Snapshot der Legacy-Funktionalität (4 Legacy-Plugins + Theme
> `alkipedia`), erstellt 2026-06-12 (Session d) bei der Sprint-Plan-Re-Validation.
> **Referenz für jedes künftige Migrations-BRIEF.** Quellen: `_references/legacy-plugins/`,
> `_references/legacy-themes/alkipedia/`, PLAN.md §1.3–1.5.

## 1 · SEO/Schema (Rank Math + custom Schema pro CPT)
- **Wo:** `legacy-plugins/category-schema/category-schema.php` (127 Z.) **+** `legacy-themes/alkipedia/rank-math.php` (93 Z.) — zwei Quellen.
- **Was:** category-schema: `rank_math/json_ld`-Filter (prio 99) — Archive/Kategorie/Tag liest ACF-Term-Feld `WPRM` (`category_{id}`), zieht WPRM-Recipe-Metadata, hängt sie als CollectionPage-`isPartOf` ein; sonst Delegation an `WPRM_Metadata_Rank_Math`. Plus `wprm_recipe_metadata`-Filter (Author-Anreicherung). rank-math.php: Author-Schema (jobTitle/alumniOf/knowsAbout aus ACF-User-Feldern), `publisher.publishingPrinciples` (hardcodierte fittastetic.com-URL), Canonical-Fix paginierte Rezeptkategorie.
- **WP-Mechaniken:** Rank-Math-Filter (`rank_math/json_ld`, `…/snippet/rich_snippet_article_entity`, `…/frontend/canonical`), WPRM-Klassen, ACF `get_field`.
- **Daten:** ACF-Term-Feld `WPRM`; ACF-User-Meta `author_jobtitle`/`author_alumniof(_url)`/`author_knowabout[_2-4]`/`same_as[_2]`; hardcodierte Publisher-URL.
- **Externe Deps:** Rank Math (hart/E1), WPRM (soft/E2), ACF (hart, User-/Term-Felder).
- **Geplant:** Modul `schema-engine` (Sprint P6). ACF→`register_post_meta`/`register_user_meta`+`show_in_rest`.

## 2 · Like/Favoriten
- **Wo:** `legacy-plugins/my-favorite-posts-plugin/` (main+class+shortcode+js+css) **+ Duplikat** `WPRM_SC_Favorite_Button` in `alkipedia/functions.php` (Z. 853) — **Kollision**.
- **Was:** Cookie+localStorage-Hybrid. AJAX `my_favorite_post` (**KEIN Nonce**) toggelt Cookie `my_favorite_posts` + Post-Meta-Like-Zähler `_my_favorite_post_likes`. Shortcodes thumbnail/inline/archive. WPRM-Integration via String-Replace in `wprm_recipe_image_container` + `wprm_recipe_template_html`. Archiv = `WP_Query post__in` Cookie-IDs.
- **WP-Mechaniken:** `wp_ajax(_nopriv)`, Shortcodes, Post-Meta, `$_COOKIE`/`setcookie`, WPRM-Filter, WP_Query.
- **Daten:** Cookie `my_favorite_posts` (CSV, 30 d) · localStorage `my_favorite_posts` · Post-Meta `_my_favorite_post_likes` · hardcodierte CPT-Liste im Archiv-Query.
- **Externe Deps:** jQuery, WPRM (soft/E2) — kein ACF.
- **Geplant:** Modul `favorites` (Sprint P4). REST+Nonce+`permission_callback`, CPT filterbar `depeur_food/favorites/post_types`, Cookie→localStorage-Migration, Like-Counter `register_post_meta`. Parallel-Migration (E5).

## 3 · Language Selector
- **Wo:** `alkipedia/functions.php` — `LanguageLink()` (Z. 429) + `lang_tag()` (Z. 462).
- **Was:** KEIN Übersetzungssystem. Manuelles **Cross-Domain-hreflang**: jeder Post/Term trägt ACF `link_en`/`link_de` = URLs zur jeweils anderen Site. `LanguageLink` → `wp_head` hreflang; `lang_tag` → Footer-Sprachumschalter.
- **WP-Mechaniken:** `wp_head`, `kadence_footer_navigation`-Hook, ACF (Post + Term).
- **Daten:** ACF `link_en`, `link_de` (pro Post + pro Term).
- **Externe Deps:** ACF (hart), Kadence — **NICHT Polylang/WPML** (E3).
- **Geplant:** Modul `language-selector` (Sprint P7), `link_en/de` via register_post_meta.

## 4 · Newsletter
- **Wo:** `legacy-plugins/spotlight-subscribe/spotlight-subscribe.php` (1272 Z.).
- **Was:** Flodesk-Form + App-Promo via `the_content`-Filter (prio 99): splittet nach `</p>`, fügt an konfigurierbarer Absatz-Position ein. Eigene Settings-Page (Option `spotlight_settings`). Per-Post-ACF-Override (`show_newsletter_form`/`newsletter_position`/`show_app_promo`). Hardcodierter CPT-Switch. Shortcodes `[spotlight_newsletter|app_promo|both]`. Gutenberg-Marker-Block. **Admin-Save ohne Nonce.**
- **WP-Mechaniken:** `the_content`-Filter, Options-API, `acf_add_local_field_group`, `add_options_page`, Shortcodes, `register_block_type`, `wp_is_mobile`, `kadence_single_content`-Hook.
- **Daten:** Option `spotlight_settings` (großes Config-Array inkl. Flodesk-`form_id`, URLs, Per-CPT-Toggles) · ACF `show_newsletter_form`/`newsletter_position`/`show_app_promo` · hardcodierte Flodesk-ID + alkipedia.com/fittastetic-URLs.
- **Externe Deps:** Flodesk (Backend — nicht Mailchimp/MailPoet), ACF (hart, Override), jQuery (Admin), Kadence.
- **Geplant:** Modul `newsletter` (Sprint P5). Nonce, ACF-Override→Meta-Box+`register_post_meta`, CPT filterbar, **Flodesk-only mit dünner Provider-Naht** `Providers/Flodesk.php` (E4). Big-Bang-Migration (E5). Coordinator mit Favoriten um `the_content`-Slots.

## 5 · Kadence-Templates (Favoriten-Seite, Kategorie-Seiten)
- **Wo:** `alkipedia/template-favorite-archive.php` (73) · `single-rezeptkategorie-template.php` (234) · `was-koche-ich-heute.php` (399).
- **Was:** favorite-archive: Page-Template, liest Cookie, `WP_Query post__in`. rezeptkategorie: Page-als-Pseudo-Archiv (Seite 1 = 4 Posts single-style, Seite 2+ = 21/Seite archiv-style), Multi-Taxonomie-Query aus ACF, **faked `is_archive`/`is_single` via `$wp_query`-Manipulation**, eigene Pagination, Fallback-Tag `low-carb`. was-koche-ich-heute: Tag-Filter-UI gruppiert nach ACF `tag_group`, AJAX `filter_recipes`, Load-More.
- **WP-Mechaniken:** Page-Templates, WP_Query, `$wp_query`-Manipulation, `body_class`/`kadence_is_loop_archive`-Filter, ACF, AJAX, Kadence-Loop-Hooks.
- **Daten:** ACF `rezept_tag`+Multi-Taxonomie-Felder, `rezeptkategorie_titel`, Cookie-Favoriten, ACF `tag_group` auf `post_tag`-Terms.
- **Externe Deps:** Kadence (hart), ACF (hart), WPRM (indirekt).
- **Geplant:** Theme-Bootstrap + Migration (Sprint P8, **realistisch 5–8 Sessions**). Templates bleiben Theme (Darstellung), Daten via Plugin-API. Favoriten-Seite liest Plugin-API statt Cookie.

## 6 · Automatisierung Kategorie-Seiten-Anlage
- **Wo:** ⚠️ **RECON-LÜCKE / NICHT VORHANDEN** — kein Auto-Creation-Code im Legacy (`wp_insert_post`/`wp_insert_term` = 0 Treffer). Kategorie-Seiten sind manuell angelegte Pages mit dem rezeptkategorie-Template + ACF.
- **Was (gewünscht):** Automatische Anlage von Kategorie-Seiten — **echtes Neu-Feature, keine Migration.**
- **Geplant:** Sprint P9 (Neu-Feature, kein Legacy-Pendant; Voraussetzung P3 + P8).

## 7 · REST-API (externe App)
- **Wo:** `legacy-plugins/rest-api-wprm/wl-api.php` (258 Z.).
- **Was:** `wl/v1/posts` (Recipe-Slug-Lookup — buggy: Typo `ParrentID`, hardcodierter `content="hallo"`, kein `permission_callback`). `wrm/v1/rating/*` CRUD (GET/POST/DELETE, **alle `permission_callback` auskommentiert = offen**, inkl. DELETE). `rest_wprm_recipe_query`-Filter mit `max(…,200)`-Bug.
- **WP-Mechaniken:** `register_rest_route`, `WPRM_Rating_Database`.
- **Daten:** WPRM-Recipe-Posts, WPRM-Rating-DB-Tabelle.
- **Externe Deps:** WPRM (hart), externe App (OQ-1).
- **Geplant:** Modul `rest-legacy` (Sprint P10) — **Routen 1:1 inkl. Bugs erhalten** (E8), Klassifikation „legacy", Bugs in BRIEF „Bekannte Tech-Debt", nicht gefixt. Refactor on-the-table für künftiges `rest-modern`-Modul.
- **OQ-1-Status (2026-06-13):** **obsolet durch E8-Entscheidung.** Kein externer App-Audit (Endpoints/Plattform/Auth) mehr nötig — alle Legacy-Routen werden im `rest-legacy`-Modul (P10) 1:1 inkl. Bugs übernommen, unabhängig davon, welche die externe App konkret nutzt.

## 8 · Post-Type-Generizität
- **Wo:** `alkipedia/functions.php`: `alkipedia_get_supported_post_types()` (hardcodierte 6 Typen), `alkipedia_get_taxonomy_mapping()` (hardcodiert), `alkipedia_add_custom_post_type()` = **STUB mit `TODO: dynamische Hinzufügung`** (Z. 77–82).
- **Wichtig:** CPT-/Taxonomie-**Registrierung** selbst ist NICHT im Legacy-Code — nur Listen + Query-Erweiterung.
- **Geplant:** ADR-4 `PostTypeRegistry` steuert nur, *welche* Typen Features bekommen. **CPT-Registrierung** wandert ins Plugin als `post-type-registry`-Modul (Sprint P3, E7) mit generischer CPT-Config-Settings-UI. CPT UI später deaktivieren.

---

## RECON-LÜCKEN (Stand 2026-06-12d — Status-Update 2026-06-13: P0 + P1 geschlossen)
1. **CPT-Registrierungs-Quelle** (`cocktails`/`bar-equipment`/`trinkspiele`) — ✅ **GEKLÄRT (P0):** = **ACF free** (`acf-post-type`/`acf-taxonomy`-Post-Types empirisch bestätigt, P1 § 6.1). Übernahme ins `post-type-registry`-Modul (P3, E7). *(Hinweis P3: lokal registriert sind `cocktails`/`bar-equipment`/`trinkspiele` (plural!); die ACF-Location-Werte `blog`/`tests` aus dem Field-Export sind in der tests-Env NICHT als CPT registriert — P3 enumeriert die wahre CPT-Liste aus ACF.)*
2. **Taxonomie-Registrierungs-Quelle** (`anlass`/`herkunft`/`art`/`cocktail_tags`/`trinkspiel_tags`/`equipment_tags`) — ✅ **GEKLÄRT (P0):** = **ACF free.** Übernahme P3.
3. **Kategorie-Seiten-Auto-Anlage** — existiert nicht (Neu-Feature, Sprint P9). *(unverändert offen — kein Recon-Gap, sondern Neubau)*
4. **ACF-Field-Definitionen** — ✅ **GESCHLOSSEN (P1):** vollständig erfasst in `_references/acf-discovery.md` (35 Kandidaten-Felder: 30 UI + 4 Code + 1 Orphan; empirisch via wp-cli validiert).
5. **OQ-1 Live-REST-Konsumenten** — ✅ **OBSOLET (E8):** Legacy-Routen werden 1:1 inkl. Bugs ins `rest-legacy`-Modul (P10) übernommen, kein externer App-Audit nötig (s. § 7 OQ-1-Status).

---

## SICHERHEITS-FUNDE (aus Legacy-Recon)

**BLOCKING vor Live-Deploy — für NEUE Module (Migration mit Code-Neubau):**
- Favoriten-AJAX **ohne Nonce** (`my_favorite_post`) → neuer Code-Pfad, Nonce + `permission_callback` Pflicht (Modul `favorites`).
- Newsletter-Admin-Save **ohne Nonce** → neuer Code-Pfad, Nonce Pflicht (Modul `newsletter`).

**Akzeptierte Tech-Debt mit niedrigem Risiko — für `rest-legacy`-Modul (E8):**
- REST-Rating-CRUD mit auskommentierten `permission_callback` (offenes DELETE), `wl/v1/posts` ohne Permission, `ParrentID`-Typo, `content="hallo"`, `max(…,200)`-Bug.
- **NICHT gefixt** im `rest-legacy`-Modul → dokumentiert in BRIEF „Bekannte Tech-Debt". Refactor später in `rest-modern`-Modul.
