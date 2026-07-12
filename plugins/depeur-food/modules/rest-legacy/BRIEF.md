# BRIEF — Modul `rest-legacy` (Sprint P10)

**Status:** v1.0 **ENTWURF — zur Freigabe** · Schema 1.1
**Klassifikation:** **legacy** (E8) — BRIEF-pflichtig, aber **eingeschränkter** Code-Qualitäts-Standard. Die Legacy-REST-Routen werden **1:1 inkl. Bugs** erhalten; Bugs sind als „Bekannte Tech-Debt" (§ 5) dokumentiert und werden **nicht** gefixt. Voller Refactor bleibt einem künftigen `rest-modern`-Modul vorbehalten.

> Quelle (empirisch): `_references/legacy-plugins/rest-api-wprm/wl-api.php` (258 Z.). Referenz: `_references/legacy-inventory.md` § 7 + E8 (CLAUDE.md).

---

## 1. Ziel & Kontext
`rest-api-wprm` ist das **einzige noch aktive Legacy-Plugin** (die anderen drei — category-schema, my-favorite-posts-plugin, spotlight-subscribe — sind bereits abgelöst/deaktiviert). Es stellt REST-Routen bereit, die eine **externe App** konsumiert. Solange es aktiv sein muss, ist der Legacy-Stack nicht vollständig abgelöst.

**Ziel:** die Routen **vertragstreu** (gleiche Namespaces, Pfade, Methoden, Response-Shapes — inkl. der bestehenden Bugs) ins Plugin holen, damit `rest-api-wprm` **deaktiviert** werden kann → **alle** Legacy-Plugins entfernt. Die App darf den Wechsel **nicht** merken (kein Vertrags-/Shape-Bruch).

**Nicht-Ziel:** Modernisierung, Auth-Härtung, Bugfixes. Das wäre `rest-modern` (später, eigener BRIEF). Hier gilt bewusst Legacy-Treue vor Sauberkeit.

## 2. Quelle (was `wl-api.php` tut)
Drei Bestandteile:
1. **Filter** `rest_wprm_recipe_query` (`se35728943_change_post_per_page`): setzt `posts_per_page = max( (int) custom_per_page, 200 )`.
2. **Route** `GET wl/v1/posts` (`?slug=`) → Recipe-Slug-Lookup (`get_posts` auf `wprm_recipe`), Response-Array `{ id, id2, title, content, slug, featured_image{ thumbnail, medium, large } }`.
3. **Klasse** `XWPRM_Api_Rating` → Rating-CRUD unter `wrm/v1/…` (8 Routen), Backend `WPRM_Rating_Database`, **alle** `permission_callback` auskommentiert.

## 3. Architektur (Modul)
- Namespace `Depeur\Food\Modules\RestLegacy\`. Ordner `rest-legacy` (kebab), PascalCase ab Subordner (Modul-Kanon).
- Pflicht-Root-Files: `manifest.php` + `module.php` (beide lowercase, keine `*.php`-Klasse am Root — FS-Safety).
- **Default-OFF** (nur aktiv, wenn per `depeur_food_modules` gesetzt).
- Klassen:
  - `Rest/Recipe_Routes.php` — Route `wl/v1/posts` **und** der `rest_wprm_recipe_query`-Filter (beide betreffen `wprm_recipe`).
  - `Rest/Rating_Routes.php` — Port von `XWPRM_Api_Rating` (die 8 `wrm/v1/rating*`-Routen).
- **WPRM = harte Modul-Dependency** (nutzt `WPRM_Rating_Database` + `wprm_recipe`). Guard: ohne die WPRM-Klasse werden die betroffenen Routen **nicht** registriert (graceful, kein Fatal) — analog zum favorites-`wprm_active()`-Muster.
- Hook-Wiring im Konstruktor (`rest_api_init`), Registrierung via `SettingsRegistry` nur falls ein Diagnose-Tab gewünscht ist (optional, § 9).

## 4. Vertrag 1:1 (unverändert zu erhalten)

| # | Methode | Route | Callback (Legacy) | Response / Backend |
|---|---------|-------|-------------------|--------------------|
| 1 | GET | `wl/v1/posts?slug=` | `wl_posts` | `{id, id2, title, content, slug, featured_image{…}}` |
| 2 | — (Filter) | `rest_wprm_recipe_query` | `se35728943_change_post_per_page` | `posts_per_page = max(custom_per_page, 200)` |
| 3 | GET | `wrm/v1/rating` | `aapi_get_ratings` | `WPRM_Rating_Database::get_ratings([])` |
| 4 | POST | `wrm/v1/rating` | `aapi_add_or_update_rating` | `…::add_or_update_rating($params['rating'])` |
| 5 | GET | `wrm/v1/rating/(id)` | `aapi_get_rating` | `…::get_rating(['where'=>'id = '.$id])` |
| 6 | DELETE | `wrm/v1/rating/(id)` | `aapi_delete_rating` | `…::delete_rating($id)` |
| 7 | GET | `wrm/v1/rating/recipe/(id)` | `aapi_get_ratings_for_recipe` | `…::get_ratings(['where'=>'recipe_id = '.$id])` |
| 8 | DELETE | `wrm/v1/rating/recipe/(id)` | `aapi_delete_ratings_for_recipe` | `…::delete_ratings_for($id)` |
| 9 | GET | `wrm/v1/rating/comment/(id)` | `aapi_get_rating_for_comment` | `…::get_rating(['where'=>'comment_id = '.$id])` |
| 10 | DELETE | `wrm/v1/rating/comment/(id)` | `aapi_delete_rating_for_comment` | `…::delete_ratings_for_comment($id)` |

Namespaces/Pfade **exakt** wie oben (auch die uneinheitlichen führenden Slashes normalisiert WP; Verhalten identisch). ID-Parameter via `validate_callback` = `is_numeric` (siehe § 6).

## 5. Bekannte Tech-Debt (bewusst NICHT gefixt — E8, in „legacy"-Klassifikation akzeptiert)
- **`wl/v1/posts`:** kein `permission_callback` (offener GET); `id2` = `$post[0]->ParrentID` (undefinierte Property → `null`); `content` hart `"hallo"`; keine Existenzprüfung `$post[0]` (PHP-Notice bei unbekanntem Slug). → 1:1 erhalten.
- **`rest_wprm_recipe_query`:** `max(custom_per_page, 200)` ignoriert kleinere Custom-Werte (Ergebnis immer ≥ 200). → 1:1 erhalten.
- **Rating-Routen:** **alle** `permission_callback` auskommentiert → **offener Schreib-/Löschzugriff** (POST/DELETE ohne Auth). Bewusst akzeptiert (E8: interner App-Userkreis, niedriges Risiko). → 1:1 erhalten, hier prominent dokumentiert. *(Empfehlung außerhalb dieses Moduls: künftiges `rest-modern` mit Auth-Gate.)*

## 6. Die EINE nötige Abweichung (OPEN-DECISION — Freigabe nötig)
Drei Routen referenzieren im Legacy einen **fehlenden** `validate_callback` `api_validate_numeric` (Tippfehler; korrekt wäre `aapi_validate_numeric`): **Route 7 (recipe GET), 8 (recipe DELETE), 9 (comment GET)**. Eine **exakte** 1:1-Übernahme würde dort einen **garantierten Fatal/Undefined-Callback** erzeugen, sobald die App diese Routen trifft — also kein „harmloser Bug", sondern ein sicherer Absturz.

- **R1 (Empfehlung):** den validate_callback korrekt verdrahten (Intention = `is_numeric`). Einzige Abweichung vom „1:1", weil „1:1 inkl. Bug" hier ein Absturz wäre. **Alle anderen** Bugs (§ 5) bleiben unangetastet.
- **R2:** exakt 1:1 inkl. kaputter Callbacks — nur vertretbar, wenn die App diese drei Routen **nachweislich nie** nutzt.

→ **Bitte R1 oder R2 freigeben.** (Default-Empfehlung: **R1**.)

## 7. Sicherheits-Einordnung
- Klassifikation „legacy": die offenen Permissions (§ 5) sind **bewusste** Tech-Debt (E8) und werden **nicht** als BLOCKING gewertet (Unterschied zu neu gebauten Modulen wie favorites/newsletter, wo Nonce Pflicht war).
- **SQL-Injection:** die `WPRM_Rating_Database`-Aufrufe konkatenieren die ID in `where`-Klauseln, aber die IDs sind per `is_numeric`-`validate_callback` numerisch → über den ID-Parameter keine Injection. (Bei R2 fehlt die Validierung auf 3 Routen — ein weiteres Argument für R1.)
- **POST-Payload** (`rating`) geht 1:1 an `WPRM_Rating_Database::add_or_update_rating` (WPRM-seitige Verarbeitung, wie im Legacy).

## 8. Deployment (nach Push-Approval)
1. Modul aktivieren (`wp option update depeur_food_modules '[…,"rest-legacy"]' --format=json`).
2. App-Endpoints gegen die **neuen** Routen prüfen (Response-Shape-Diff Legacy ↔ neu; identisch?).
3. `rest-api-wprm` **deaktivieren**.
4. Re-Test (Routen weiterhin erreichbar, gleiche Shapes; App funktioniert). Rollback = Legacy reaktivieren.

## 9. Build-Order & Umfang
1. `manifest.php` + `module.php`-Skelett → Autoloader-Smoke (`class_exists('Depeur\Food\Modules\RestLegacy\Rest\Rating_Routes')`).
2. `Rest/Rating_Routes.php` (Port `XWPRM_Api_Rating`, WPRM-Guard, R1/R2 gemäß Freigabe).
3. `Rest/Recipe_Routes.php` (`wl/v1/posts` + `rest_wprm_recipe_query`-Filter).
4. Smoke: `php -l`/phpcs, Routen registriert (`wp rest`), Response-Shapes gegen Legacy.
5. Deployment (§ 8).

**Umfang:** ~2 Dateien Logik + 2 Root-Files, ~250–320 LOC. **KEINE Core-Änderungen.** 1 Session.

## 10. Smoke/Verifikation
- Autoloader löst `RestLegacy\Rest\*` auf; `php -l` clean; phpcs (legacy-Klassifikation: WPRM-Backend-Aufrufe + „where"-Konkatenation erhalten → ggf. begründete `phpcs:ignore` für `WordPress.DB.PreparedSQL`, da die Werte numerisch-validiert sind und die Query-Semantik 1:1 aus dem Legacy stammt).
- `wp rest` / `curl`: alle 10 Einträge aus § 4 erreichbar, Shapes identisch zu `rest-api-wprm`.
- Nach Legacy-Deaktivierung: keine 404, App unverändert.

---

### Freigabe-Bedarf
1. **§ 6 R1 vs. R2** (kaputter validate_callback) — Empfehlung **R1**.
2. Grundsätzliche Freigabe der 1:1-Erhaltung inkl. der offenen Rating-Permissions (E8-Bestätigung).
