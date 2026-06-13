# ACF-Discovery — Bestandsaufnahme aller Field-Definitionen (Sprint P1)

> **Zweck:** Vollständige Bestandsaufnahme aller ACF-Field-Definitionen (UI-registriert
> **+** code-registriert via `acf_add_local_field_group()`) als Fundament-Recon für das
> `meta-registry`-Modul (Sprint P2). meta-registry ruft `register_post_meta` /
> `register_user_meta` / `register_term_meta` mit `show_in_rest => true` für jedes hier
> erfasste Feld auf, unter **exakt demselben Meta-Key** (= ACF Field-**Name**), damit die
> Daten in `wp_postmeta`/`usermeta`/`termmeta` koexistenzfähig bleiben (E6, ADR-5).
>
> **Erstellt:** 2026-06-13 (Sprint P1, Live-First-Phase).
> **Quellen:** `_references/acf-export-2026-06-12.json` (UI-Export, 6 Field-Groups),
> `_references/legacy-plugins/spotlight-subscribe/spotlight-subscribe.php`,
> `_references/legacy-themes/alkipedia/functions.php` (Code-Registrierungen).
> **Querverweis:** `_references/legacy-inventory.md` (Konsumenten je Funktion).
> **ACF-Version:** **free** (nicht Pro) auf beiden Sites — verifiziert, keine Pro-Feldtypen
> im Bestand (CLAUDE.md › E6).

## Field-Key vs. Field-Name (kritisch für P2)

- **Field-Key** (z. B. `field_64a68771f9008`) = ACF-interne ID, lebt in `_<name>`-Tracking-Meta.
  **Nicht** der Meta-Key. Wird nach ACF-Deaktivierung obsolet.
- **Field-Name** (z. B. `author_jobtitle`) = der tatsächliche **Meta-Key** in
  `wp_postmeta`/`usermeta`/`termmeta`. **Das** braucht meta-registry (P2).
- Beide unten dokumentiert für vollständige Nachvollziehbarkeit.

## Übersicht / Zählung

| Quelle | Field-Groups | Felder |
|---|---|---|
| UI-Export (`acf-export-2026-06-12.json`) | 6 | 30 |
| Code (`acf_add_local_field_group()`) | 3 Registrierungen / 2 Feld-Sets | 4 |
| **Summe (eindeutige Felder)** | | **34** |

**`show_in_rest`:** ALLE Field-Groups (UI **und** Code) haben `show_in_rest` = `0`/`false`.
→ ACF exponiert heute **kein** Feld in der REST-API. meta-registry (P2) setzt `show_in_rest`
selbst — das ist genau einer der Hauptmehrwerte der Migration.

---

# 1 · UI-registrierte Field-Groups (aus `acf-export-2026-06-12.json`)

## 1.1 Author fields — `group_64a6871dc3795` → **User-Meta**
- **Location:** `user_role == all` · **Ziel:** `register_user_meta` · **show_in_rest:** 0

| Field-Name (Meta-Key) | Label | Type | Default | Req | Field-Key | Konsument (Legacy) |
|---|---|---|---|---|---|---|
| `same_as` | SameAs | text | "" | 0 | field_652420bdd23a0 | category-schema.php:108 (sameAs-Loop) |
| `same_as_2` | SameAs 2 | text | "" | 0 | field_652420ced23a1 | category-schema.php:108 (sameAs-Loop) |
| `author_knowabout` | Knows about 1 | text | "" | 0 | field_64a6871ef9004 | category-schema.php:94 · rank-math.php:46 |
| `author_knowabout_2` | Knows about 2 | text | "" | 0 | field_64a7bffdf93a8 | category-schema.php:94 · rank-math.php:46 |
| `author_knowabout_3` | Knows about 3 | text | "" | 0 | field_64a7c00cf93a9 | category-schema.php:94 · rank-math.php:46 |
| `author_knowabout_4` | Knows about 4 | text | "" | 0 | field_64a7c012f93aa | category-schema.php:94 · rank-math.php:46 |
| `author_knowabout_5` | Knows about 5 | text | "" | 0 | field_64a7c014f93ab | **⚠️ KEINER** (Loops lesen nur `_1`–`_4`, s. § 4.1) |
| `author_jobtitle` | Job Title | text | "" | 0 | field_64a68771f9008 | category-schema.php:75 · rank-math.php:25 · entry_author.php:29 |
| `author_alumniof` | Alumni of | text | "" | 0 | field_64a6bd8bcfb01 | category-schema.php:78 · rank-math.php:33 |
| `author_alumniof_url` | Alumni of URL | url | "" | 0 | field_64a7c091f93ac | category-schema.php:79 · rank-math.php:34 |
| `author_description` | Author long description | wysiwyg | "" | 0 | field_64a7c0a4f93ad | category-schema.php:77,84 |
| `facebook_profile` | Facebook profile | url | "" | 0 | field_64ae7271f4014 | **⚠️ KEINER** im erfassten Legacy (s. § 4.4) |
| `linkedin_profile` | LinkedIn Profile | url | "" | 0 | field_64ae7284f4015 | **⚠️ KEINER** im erfassten Legacy |
| `instagram_profile` | Instagram profile | url | "" | 0 | field_64ae7293f4016 | **⚠️ KEINER** im erfassten Legacy |
| `twitter_profile` | Twitter Profile | url | "" | 0 | field_64ae72a5f4017 | **⚠️ KEINER** im erfassten Legacy |
| `youtube_profile` | Youtube Profile | url | "" | 0 | field_64ae72b4f4018 | **⚠️ KEINER** im erfassten Legacy |
| `website_profile` | Website Profile | url | "" | 0 | field_64aea8f7e08c9 | **⚠️ KEINER** im erfassten Legacy |
| `email_profile` | Email profile | text | "" | 0 | field_64aea907e08ca | **⚠️ KEINER** im erfassten Legacy |
| `static_page_for_author` | Seite 1 | post_object (→ `page`, status `private`, return **object**) | - | 0 | field_6523ed75c2965 | functions.php:527 · archive.php:16 |

## 1.2 Kategorie-Custom — `group_6516b8d64a7b3` → **Term-Meta**
- **Location:** `taxonomy == category` **ODER** `taxonomy == all` · **Ziel:** `register_term_meta` · **show_in_rest:** 0
- ⚠️ Die `taxonomy == all`-Regel macht die Felder auf **jeder** Taxonomie editierbar, nicht nur `category`.

| Field-Name (Meta-Key) | Label | Type | Default | Req | Field-Key | Konsument (Legacy) |
|---|---|---|---|---|---|---|
| `static_page` | Seite 1 | post_object (→ `page`, return **id**) | - | 0 | field_6516c1ef9cfc3 | functions.php:522 · archive.php:14 |
| `WPRM` | WPRM ID | text | "" | 0 | field_653f9974e2ac1 | category-schema.php:22 (CollectionPage-Schema) |

## 1.3 Like-Anzahl — `group_66c0a73268eb4` → **Post-Meta**
- **Location:** `post_type == post` · **Ziel:** `register_post_meta` · **show_in_rest:** 0

| Field-Name (Meta-Key) | Label | Type | Default | Req | Field-Key | Konsument (Legacy) |
|---|---|---|---|---|---|---|
| `_my_favorite_post_likes` | _my_favorite_post_likes | number | "" | 0 | field_66c0a73211898 | my-favorite-posts class:126,137,148 |

> **⚠️ Führender Unterstrich = protected Meta-Key.** WordPress versteckt `_`-Keys aus der
> Custom-Fields-UI; `register_post_meta` mit `_`-Key braucht einen `auth_callback` für
> REST-/Editor-Exposition. Für P4 (favorites) relevant — der Like-Counter ist genau
> dieses Feld. P4 + P2 müssen sich auf einen Owner einigen (dieses Feld ist Favoriten-
> Logik, nicht nur passive Meta — s. § 5).

## 1.4 Reviewed by — `group_64a3ef3013119` → **Post-Meta**
- **Location:** `post_type == page` **ODER** `post_type == post` · **Ziel:** `register_post_meta` · **show_in_rest:** 0

| Field-Name (Meta-Key) | Label | Type | Default | Req | Field-Key | Konsument (Legacy) |
|---|---|---|---|---|---|---|
| `reviewed_by` | Reviewed by | user (Rollen contributor/author/editor/administrator, return **id**) | - | 0 | field_64a3ef30b7aa7 | **empirisch 67 Posts** (§ 6); Reader im Produktiv-Theme (s. § 4.4) |

## 1.5 Rezeptkategorie Einstellungen — `group_682f1db019e50` → **Post-Meta**
- **Location:** `post_type == page` · **Ziel:** `register_post_meta` · **show_in_rest:** 0
- Kontext: liegt auf den manuell angelegten Kategorie-Seiten (Pages mit `single-rezeptkategorie-template.php`).

| Field-Name (Meta-Key) | Label | Type | Default | Req | Field-Key | Konsument (Legacy) |
|---|---|---|---|---|---|---|
| `rezept_art_tags` | rezept_art_tags | taxonomy (`art`, multi_select, return id, save_terms 0/load_terms 0) | - | 0 | field_682f1db0c62c7 | functions.php:204→212 (Multi-Tax-Query) |
| `rezept_tags` | rezept_tags | taxonomy (`post_tag`, multi_select, return id) | - | 0 | field_68bc303b79948 | **⚠️ KEINER direkt** — Code liest `rezept_post_tags`/`rezept_tag` (Namens-Drift, s. § 4.2) |
| `rezeptkategorie_titel` | rezeptkategorie_titel | text | "" | 0 | field_682f38b49b171 | single-rezeptkategorie-template.php:101,108,161 |
| `rezept_anlass_tags` | rezept_anlass_tags | taxonomy (`anlass`, multi_select, return id, add_term 1) | - | 0 | field_68bc331afd184 | functions.php:205→212 (Multi-Tax-Query) |
| `rezept_herkunft_tags` | rezept_herkunft_tags | taxonomy (`herkunft`, multi_select, return id, add_term 1) | - | 0 | field_68bc350e9ed2a | functions.php:206→212 (Multi-Tax-Query) |

## 1.6 Übersetzungen — `group_5f29db788a4f8` → **Mixed (Post-Meta + Term-Meta)**
- **Location:** `post_type == post` **ODER** `page` **ODER** `taxonomy == category` **ODER** `post_type == tests` **ODER** `post_type == blog`
- **Ziel:** `register_post_meta` (post/page/tests/blog) **+** `register_term_meta` (category) — selber Key, zwei Registrierungen
- **show_in_rest:** false · **position:** acf_after_title

| Field-Name (Meta-Key) | Label | Type | Default | Req | Instructions | Field-Key | Konsument (Legacy) |
|---|---|---|---|---|---|---|---|
| `link_de` | link_de | link (return **url**) | - | 0 | Deutsche URL | field_5f29e0d3a8543 | functions.php:434,445,467,487 (`LanguageLink`/`lang_tag`) |
| `link_en` | link_en | link (return **url**) | - | 0 | Englische URL | field_5f29e36e1144d | functions.php:433,444,466,486 |

> **⚠️ `link`-Feldtyp speichert ein Array** (`{title, url, target}`), nicht nur die URL —
> auch wenn `return_format = url`. In `wp_postmeta` liegt das serialisierte Array. P2 muss
> entscheiden, ob meta-registry das Array 1:1 abbildet (`type => array` + Schema) oder ob
> ein Accessor die URL extrahiert. Relevant für P7 (language-selector).

---

# 2 · Code-registrierte Field-Groups (`acf_add_local_field_group()`)

> Diese Felder stehen **nicht** im JSON-UI-Export, weil sie programmatisch via
> `acf_add_local_field_group()` am `acf/init`-Hook registriert werden. Sie sind genauso
> live wie die UI-Felder und **müssen** in P2 (meta-registry) mit — sonst brechen
> Newsletter (P5) und das „Was koche ich heute"-Template (P8).

## 2.1 Spotlight Promotions (Newsletter-Override) → **Post-Meta**
- **Quelle:** `spotlight-subscribe.php:870–991`, Hook `acf/init`.
- **Zwei Group-Registrierungen, geteiltes 3-Feld-Set** (Defaults variieren je Group):
  - `group_spotlight_options_pages` — Location `post_type == page` **ODER** `page_template == single-rezeptkategorie-template.php`; `show_app_promo` default **1**.
  - `group_spotlight_options_cpt` — Location `post_type == blog` **ODER** `post_type == tests`; `show_app_promo` default **0**.
- **show_in_rest:** nicht gesetzt → default false.

| Field-Name (Meta-Key) | Label | Type | Default | Req | Conditional Logic | Field-Key | Konsument (Legacy) |
|---|---|---|---|---|---|---|---|
| `show_newsletter_form` | Newsletter-Formular anzeigen | true_false (ui 1) | 1 | 0 | - | field_show_newsletter | spotlight:1169,1182 |
| `newsletter_position` | Newsletter Position | number (min 1, max 20, step 1) | 4 | 0 | sichtbar wenn `show_newsletter_form == 1` | field_newsletter_position | spotlight:1103 |
| `show_app_promo` | App-Promotion anzeigen | true_false (ui 1) | 1 (pages) / 0 (cpt) | 0 | - | field_show_app_promo | spotlight:1170,1183 |

> **⚠️ NICHT auf Standard-`post` registriert.** Die Override-Felder existieren nur für
> `page`, `blog`, `tests` + das rezeptkategorie-Template. Auf normalen `post`s gibt es
> **keinen** Per-Post-Override → der Newsletter folgt dort allein den globalen
> `spotlight_settings`. P5-BRIEF muss klären, ob das beabsichtigt ist oder ob `post`
> ergänzt werden soll.

## 2.2 Tag-Einstellungen (`tag_group`) → **Term-Meta**
- **Quelle:** `alkipedia/functions.php:597–641`, Hook `acf/init`.
- **Location:** `taxonomy == post_tag`.
- **Hinweis:** `register_tag_group_field` ist via `add_action('acf/init', …)` **doppelt
  registriert** (Z. 584 + Z. 641) — harmlos (WP dedupliziert identische Callbacks), aber
  ein Legacy-Schludrigkeits-Marker.

| Field-Name (Meta-Key) | Label | Type | Default | Req | Choices | Field-Key | Konsument (Legacy) |
|---|---|---|---|---|---|---|---|
| `tag_group` | Tag-Gruppe | select (return **value**) | zutaten | **1** | anlass, zubereitung, zutaten, saisonales, ernaehrung_ziel, herkunft | field_tag_group | functions.php:676 (Admin-Spalte) · was-koche-ich-heute.php:159 |

---

# 3 · Cross-Reference-Master: jedes Feld → Ziel-Registrierung + Konsument

| Field-Name | Scope | P2-Registrierung | Genutzt? | Migriert in Modul |
|---|---|---|---|---|
| `same_as`, `same_as_2` | User | register_user_meta | ✓ | P6 schema-engine |
| `author_knowabout` (1–4) | User | register_user_meta | ✓ | P6 schema-engine |
| `author_knowabout_5` | User | register_user_meta | **✗ ungenutzt** | P6 (optional, s. § 4.1) |
| `author_jobtitle` | User | register_user_meta | ✓ | P6 schema-engine |
| `author_alumniof`, `author_alumniof_url` | User | register_user_meta | ✓ | P6 schema-engine |
| `author_description` | User | register_user_meta | ✓ | P6 schema-engine |
| `facebook_profile` … `email_profile` (7×) | User | register_user_meta | **✗ ungenutzt (erfasst)** | P6 (verifizieren, § 4.3) |
| `static_page_for_author` | User | register_user_meta | ✓ | P8/P9 (Kategorie-Seiten) |
| `static_page` | Term | register_term_meta | ✓ | P8/P9 |
| `WPRM` | Term | register_term_meta | ✓ | P6 schema-engine |
| `_my_favorite_post_likes` | Post | register_post_meta (auth_callback!) | ✓ | **P4 favorites** (nicht P2 — s. § 5) |
| `reviewed_by` | Post | register_post_meta | **✓ (67 Werte, § 6)** | P6 schema-engine |
| `rezept_art_tags`, `rezept_anlass_tags`, `rezept_herkunft_tags` | Post | register_post_meta | ✓ (15/13/12) | P8/P9 |
| `rezept_tags` | Post | register_post_meta | ✓ (15, § 6) | P8/P9 |
| `rezept_tag` (Orphan, nur DB) | Post | register_post_meta | ✓ (9, § 6) | P8/P9 (s. § 4.3) |
| `rezeptkategorie_titel` | Post | register_post_meta | ✓ | P8/P9 |
| `link_de`, `link_en` | Post + Term | register_post_meta + register_term_meta | ✓ | **P7 language-selector** |
| `show_newsletter_form`, `newsletter_position`, `show_app_promo` | Post | register_post_meta | ✓ | **P5 newsletter** |
| `tag_group` | Term (post_tag) | register_term_meta | ✓ | P8 (was-koche-ich-heute) |

---

# 4 · Befunde / Diskrepanzen

## 4.1 `author_knowabout_5` registriert, aber nie gelesen
Beide Schema-Konsumenten (`category-schema.php:94`, `rank-math.php:46`) iterieren über ein
hartkodiertes Array `['author_knowabout', '_2', '_3', '_4']` — **`_5` fehlt im Loop.** Das
Feld existiert in der ACF-UI, trägt evtl. Daten, wird aber nie ins Schema gezogen.
→ **P6-Entscheidung:** entweder in der schema-engine den Loop auf `_5` erweitern (falls
Daten vorhanden) oder das Feld bewusst weglassen. P2 kann es vorsorglich mit-registrieren
(billig, schadet nicht).

## 4.2 `rezept_tags` (Export) vs. `rezept_post_tags`/`rezept_tag` (Code) — Namens-Drift
Die Multi-Taxonomie-Query (`functions.php:189–230`) liest:
- `rezept_tag` (**singular**, Z. 193, „Rückwärtskompatibilität")
- eine Taxonomie→Feldname-Map (Z. 200–209): `rezept_post_tags`, `rezept_categories`,
  `rezept_cocktail_tags`, `rezept_art_tags`, `rezept_anlass_tags`, `rezept_herkunft_tags`,
  `rezept_trinkspiel_tags`, `rezept_equipment_tags`.

Der Export kennt für `post_tag` aber das Feld **`rezept_tags`** (plural) — nicht
`rezept_post_tags` und nicht `rezept_tag`. Drei dicht beieinander liegende Namen für
denselben Zweck. → **P8-BRIEF muss klären**, welcher Name produktiv Daten trägt
(empirisch auf der Live-Site prüfen: `SELECT DISTINCT meta_key FROM wp_postmeta WHERE
meta_key LIKE 'rezept_%'`).

## 4.3 ✅ AUFGELÖST (empirisch, § 6): Code-Referenzen ohne Export-Pendant
Folgende Feldnamen werden im Legacy-Code via `get_field()` gelesen, fehlen aber im
UI-Export **und** in den Code-Registrierungen. **Per wp-cli-Verifikation (§ 6) geklärt** —
Jonas' Klarstellung: der ACF-Export ist global identisch für beide Sites, die Felder waren
auf einfachandersessen.de nur ungenutzt, weil Cocktail/Trinkspiel/Equipment-Content fehlte.
Nach Import von Test-Content (217 cocktails, 23 trinkspiele, 19 bar-equipment) zeigt die
DB den wahren Stand:

| Code-Referenz | Datei:Zeile | post_meta-Count (§ 6) | Verdikt |
|---|---|---|---|
| `rezept_tag` (singular) | functions.php:193 | **9** | **LEBENDER Orphan** — hat Daten, aber nicht im JSON-Export/Code-Registrierung → P2 mit-registrieren (s. § 6) |
| `rezept_post_tags` | functions.php:201 | **0** | **Dead-Code** — nie befüllt, verwerfen |
| `rezept_categories` | functions.php:202 | **0** | **Dead-Code** — verwerfen |
| `rezept_cocktail_tags` | functions.php:203 | **0** | **Dead-Code** — trotz 217 Cocktails 0 Werte → verwerfen |
| `rezept_trinkspiel_tags` | functions.php:207 | **0** | **Dead-Code** — verwerfen |
| `rezept_equipment_tags` | functions.php:208 | **0** | **Dead-Code** — verwerfen |

**Ergebnis: KEIN zweiter Export nötig.** 5 der 6 Referenzen sind toter Code im generischen
Multi-Taxonomie-Query (alle via `if (!empty(...))` geschützt, null = no-op). Die
Cocktail-Taxonomie-Zuordnung läuft live über echte WP-Taxonomie-Terms, **nicht** über
diese Page-ACF-Felder. Einziger Handlungsbedarf: `rezept_tag` (singular, 9 Werte) als
lebenden Orphan in P2 berücksichtigen.

## 4.4 `reviewed_by` IST genutzt (empirisch korrigiert); Social/Author lokal nicht verifizierbar
- **`reviewed_by`:** empirisch **67 Posts** mit Daten (§ 6) → **aktiv gepflegt.** Der
  Reader steht nicht im erfassten Legacy-Code (4 Plugins + `alkipedia`-Theme), sondern im
  **aktuellen Produktiv-Theme** (oder Rank-Math/Kadence-Element). **Klar registrieren in P2.**
- **7× Social-Profile** (`facebook_profile`…`email_profile`) **+ alle Author-Felder:**
  lokal **nicht verifizierbar** — die Test-Env hat nur **1 User** (Admin) ohne ACF-Author-
  Meta und **0 termmeta-Zeilen** (§ 6). Das ist eine **Test-Daten-Lücke, kein Beweis für
  Nichtnutzung.** Die Author-Schema-Felder werden nachweislich vom Schema-Code gelesen
  (`category-schema.php`/`rank-math.php`). P2 registriert alle mit (billig); echte Nutzung
  der reinen Social-Profile-Felder bleibt eine P6-Live-Verifikation.

---

# 5 · Zusammenfassung (Phase-3-Reflektion)

## Cross-Ref-Status (nach empirischer wp-cli-Verifikation, § 6)
- **34 Felder** im Export/Code erfasst (30 UI + 4 Code) + **1 lebender Orphan** (`rezept_tag`,
  nur in DB) = **35 P2-Kandidaten**. Alle Konsumenten je Feld dokumentiert (§ 3).
- **Empirisch bestätigt genutzt (Post-Meta):** `link_de`/`link_en` (368), `reviewed_by`
  (67), `rezeptkategorie_titel` (21), Newsletter-Overrides (17), `rezept_art/anlass/herkunft_tags`
  (15/13/12), `rezept_tags` (15), `rezept_tag` (9), `_my_favorite_post_likes` (19).
- **Dead-Code aufgelöst (§ 4.3):** 5 `rezept_*`-Referenzen mit **0 Werten** → verworfen,
  kein Migrations-Scope. **Kein zweiter Export nötig.**
- **Lokal NICHT verifizierbar (Test-Daten-Lücke, § 4.4 / § 6):** alle Term-Meta-Felder
  (`WPRM`, `static_page`, `tag_group`, term-`link_*`) — **0 termmeta-Zeilen** —, und alle
  User-Meta-Felder (Author-Schema + Social-Profile) — **1 User ohne ACF-Author-Meta.** Aus
  Export/Code/Legacy-Konsumenten dokumentiert, aber empirisch unbestätigt → P6-Live-Check.
- **Registriert, aber ungenutzt (§ 4.1):** `author_knowabout_5` (Loops lesen nur `_1`–`_4`).

## Pro-Features-Status ✓ SAUBER
Kein einziges Pro-only-Feature im gesamten Bestand. Verwendete Feldtypen: `text`, `url`,
`wysiwyg`, `number`, `post_object`, `user`, `taxonomy`, `link`, `true_false`, `select` —
**alle ACF free**. Keine Repeater / Flexible Content / Gallery / Clone / Group / Options
Pages. Bestätigt E6 (ACF-free-Constraint hält).

## Meta-Box-Komplexitäts-Schätzung (für Editor-UI-Entscheidung, ex-Item-8)
Native Replikation der Editier-UI (falls ACF irgendwann abgelöst wird):

| Komplexität | Field-Groups | Begründung |
|---|---|---|
| **Einfach** (Konstant-Werte) | Author fields (Text/URL/wysiwyg), Like-Anzahl, Übersetzungen, rezeptkategorie_titel | Standard-Inputs, 1:1 Meta-Box |
| **Mittel** (Relation/Auswahl) | Kategorie-Custom (post_object), Reviewed by (user-picker), tag_group (select+required), Rezeptkategorie-Taxonomie-Felder (multi-select term-picker) | brauchen Dropdown/Picker-Logik + Term-/Post-Lookups |
| **Mittel-Hoch** (Conditional + Link-Array) | Spotlight (conditional logic auf `newsletter_position`), `link_de`/`link_en` (Array-Serialisierung) | Conditional-Show + Array-Handling |

**Gesamteinschätzung:** keine hohe Komplexität — eine native Editor-UI wäre machbar, aber
nicht trivial wegen der Term-/Post-Picker und der Conditional Logic. Entscheidung bleibt
für die spätere Editor-UI-Architektur-Session (Option 1/2/3, CLAUDE.md › ACF-Migration).

## Wichtigste Beobachtungen für den P2-BRIEF (meta-registry)
1. **Scope geklärt** (§ 4.3, empirisch): Export ist vollständig (global, beide Sites). Die
   5 `rezept_{post_tags,categories,cocktail_tags,trinkspiel_tags,equipment_tags}`-Referenzen
   sind Dead-Code (0 Werte) → **nicht** registrieren. `rezept_tag` (singular, 9 Werte) ist
   ein lebender Orphan → **mit-registrieren** trotz Fehlens im JSON-Export.
2. **`_my_favorite_post_likes` gehört zu P4, nicht P2** — protected Key (`auth_callback`),
   und es ist Favoriten-Geschäftslogik. P2/P4-Owner-Abgrenzung im P2-BRIEF festlegen.
3. **Drei Registrierungs-Ziele** nötig: `register_post_meta` **+** `register_user_meta`
   **+** `register_term_meta` (Übersetzungen brauchen post **und** term).
4. **`link`-Felder = Array-Serialisierung** (§ 1.6) — Schema-Typ in register_*_meta beachten.
5. **`show_in_rest => true`** für alle (ACF setzt es heute nirgends) — Kern-Mehrwert der Migration.
6. **Namens-Identität strikt** (ADR-5/E5): exakt der ACF-Field-**Name** als Meta-Key, sonst
   bricht die Koexistenz mit aktivem ACF.

---

# 6 · Empirisch verifizierte Meta-Keys (wp-cli, localhost:8889)

> **Methodik:** `wp-env run tests-cli wp eval` gegen die tests-DB (localhost:8889) am
> 2026-06-13, nachdem Jonas Test-Content (217 cocktails, 23 trinkspiele, 19 bar-equipment)
> aus alkipedia.com importiert hatte. Zweck: die JSON-/Code-Discovery gegen die **real
> existierenden** Meta-Keys validieren. **Klarstellung Jonas:** der ACF-Export ist global
> identisch für beide Sites; die zuvor „fehlenden" Felder waren nur mangels Content ungenutzt.

## 6.1 Content-Stand (tests-DB)
`cocktails` 217 · `bar-equipment` 19 · `trinkspiele` **23** (CPT heißt `trinkspiele` plural,
nicht `trinkspiel` — Hinweis für P3) · `post` 61 · `page` 67.
ACF aktiv (`acf_user_settings`-Usermeta); CPTs ACF-registriert (`acf-post-type`/`acf-taxonomy`
Post-Types + `manageedit-{cocktails,bar-equipment,trinkspiele}columnshidden`-Usermeta) →
bestätigt P0 (CPT-Quelle = ACF free).

## 6.2 Post-Meta — ACF-relevante Keys mit Count (global, alle Post-Types)
Aus `wp_postmeta` (nicht-protected + `_my_favorite_post_likes`), nur die Discovery-relevanten
herausgefiltert (Rank-Math/WPRM/Smush/Pinterest/WP-Review-Keys gehören anderen Plugins):

| Meta-Key | Count | Discovery-Quelle |
|---|---|---|
| `link_de` | 368 | UI § 1.6 |
| `link_en` | 368 | UI § 1.6 |
| `reviewed_by` | 67 | UI § 1.4 — **korrigiert genutzt** (§ 4.4) |
| `rezeptkategorie_titel` | 21 | UI § 1.5 |
| `_my_favorite_post_likes` | 19 | UI § 1.3 |
| `newsletter_position` | 17 | Code § 2.1 |
| `show_app_promo` | 17 | Code § 2.1 |
| `show_newsletter_form` | 17 | Code § 2.1 |
| `rezept_art_tags` | 15 | UI § 1.5 |
| `rezept_tags` | 15 | UI § 1.5 |
| `rezept_anlass_tags` | 13 | UI § 1.5 |
| `rezept_herkunft_tags` | 12 | UI § 1.5 |
| `rezept_tag` | **9** | **Orphan** — nicht im JSON-Export/Code (§ 4.3) |

**0-Werte (Dead-Code, § 4.3):** `rezept_post_tags`, `rezept_categories`, `rezept_cocktail_tags`,
`rezept_trinkspiel_tags`, `rezept_equipment_tags` — kommen in `wp_postmeta` **nicht vor**.

## 6.3 Term-Meta — leer
`wp_termmeta`: **0 Zeilen** gesamt. Die Term-Meta-Felder aus Export/Code (`WPRM`,
`static_page`, `tag_group`, Term-`link_de`/`link_en`) sind in der tests-DB **nicht
befüllt** → nicht empirisch verifizierbar. **Test-Daten-Lücke, kein Beweis für
Nichtnutzung** (Legacy-Konsumenten lesen sie nachweislich). P2 registriert sie aus
Export/Code; P6/P8 verifiziert auf Live.

## 6.4 User-Meta — keine ACF-Author-Daten
`wp_usermeta`: nur **1 User** (Admin), ausschließlich WP-Standard-Keys + `acf_user_settings`.
**Kein** `author_jobtitle`/`same_as`/`*_profile`/etc. → Author-Felder lokal nicht
verifizierbar (Test-Env hat nicht die Produktiv-Autoren). Dokumentiert aus Export + Schema-
Konsumenten (`category-schema.php`/`rank-math.php`); P6-Live-Verifikation für die reinen
Social-Profile-Felder.

## 6.5 Weitere Recon-Lücken aus der empirischen Prüfung
- **CPT-Name `trinkspiele` (plural)** ≠ `trinkspiel` (legacy-inventory.md / Code-Feldname
  `rezept_trinkspiel_tags`). Für P3 (post-type-registry) relevant.
- **Term-/User-Meta lokal unbefüllt** — wer eine vollständige empirische Term-/User-Meta-
  Verifikation braucht (P6/P7), muss Produktiv-nahe Daten ziehen oder auf `runcloud-test`
  prüfen. Für P1/P2 nicht blockierend (Export + Code sind die kanonische Definition).
