# BRIEF.md — `meta-registry` (Custom-Field Schema- & Editor-UI-Registry)

**BRIEF-Schema-Version: 1.1** — geschäftslogik-tragender Volltext-Brief nach § 12.2.
Erbt das geschäftslogik-Schema (1.1) vom `cache-bridge`-Brief; die reine Modul-Mechanik
(Naming, FS-Safety, Discovery, Autoloader, Lifecycle-Grundmuster) steht im
`example-module/BRIEF.md` (v1.0) und wird hier **nicht** wiederholt.

> **§ 12.2-Pflicht-Brief (geschäftslogik-tragend).** Vor jedem Code zu schreiben und
> freizugeben (§ 12.3), lebende Doku (§ 12.4). **Migrations-Quelle = `_references/acf-discovery.md`**
> (Konsolidat aus 6 UI-Field-Groups + 3 Code-Registrierungen + empirischer wp-cli-Validierung) —
> NICHT ein einzelnes Legacy-Plugin (Abweichung von § 12.2 #1, analog cache-bridge).

## 1. Meta / Einordnung

- **Plugin-Cluster:** **Depeur Food** (Content-Datenschicht). **Fundamental** —
  „entsperrt ALLE Feature-Module": P4–P9 lesen ihre Daten aus den hier registrierten
  Meta-Keys. P2 ist damit Voraussetzung für jedes daten-tragende Folge-Modul.
- **Default-Status:** **Default-ON.** Ohne Registry sind die Felder weder in der REST-API
  sichtbar noch (nach ACF-UI-Abbau) im Editor editierbar → Infrastruktur, kein optionales
  Feature. (Aktivierung weiter via `depeur_food_modules`; „Default-ON" = beim Onboarding
  aktiv vorgeschlagen.)
- **Brief-Verwandtschaft:** baut auf `example-module/BRIEF.md` v1.0 (Modul-Kanon) +
  `cache-bridge/BRIEF.md` (Schema-1.1-Struktur) auf.
- **Cross-Module-Disziplin (Splitting-Strategie):** meta-registry hat **keine** direkten
  Cross-Module-Importe. Konsumenten-Module greifen **nie** auf meta-registry-Klassen zu,
  sondern lesen via `get_post_meta()`/`get_field()`/REST. Die einzige Kopplung ist der
  gemeinsame Meta-Key-Namensraum (= ACF-Field-Name) + optionale Filter (§ 5).
- **Status:** Volltext-BRIEF zur Freigabe (Session 2026-06-13). Nach Approval →
  **ACF-Dependency-Core-Mini-Task** (Plugin-Core, vor Modul-Code) → P2-Code-Phase.

## 2. Zweck & Funktionalitäts-Inventar

**Zweck:** Single-Point-of-Truth für **alle** Custom-Field-Definitionen aus der Discovery.
Aus **einer** Definitionsquelle entstehen **zwei** gekoppelte Wirkungen:
1. **Datenschicht** — `register_post_meta` / `register_user_meta` / `register_term_meta`
   mit `show_in_rest`, `sanitize_callback`, ggf. `auth_callback`.
2. **Editor-UI-Definition** — `acf_add_local_field_group`, damit die Felder **ohne**
   manuelle ACF-UI-Anlage pro Site im Post-/Term-/User-Editor erscheinen (ACF rendert).

**Doppel-Owner-Pattern (bindend):**
- **Plugin (meta-registry)** = Schema-Definition (`register_*_meta`) **+** Field-Group-
  Definition (`acf_add_local_field_group`).
- **ACF free** = Editor-UI-**Renderer** (liest die Field-Group, zeigt die Eingabefelder).
- **Konsumenten-Module** = Logik-Schreiber/-Leser (z. B. P6 schema-engine liest `author_*`,
  P5 newsletter liest `show_newsletter_form`).

**Funktionalitäts-Inventar:**
- **Field-Registry** — zentrale Definitionsliste, ein Eintrag pro Feld (§ 4).
- **Meta-Registrierung** (`init`): `register_*_meta` je Eintrag, korrektem Object-Type
  (post/user/term) + Object-Subtypes.
- **Field-Group-Registrierung** (`acf/init`): `acf_add_local_field_group` je Gruppe.
- **Sanitize-Callbacks** pro Feldtyp (text/url/wysiwyg/number/true_false/select/link/
  post_object/user/taxonomy).
- **`show_in_rest => true`** (+ REST-Schema, wo nicht-skalar) — Kern-Mehrwert der Migration.
- **`auth_callback`** für protected Keys (führender `_`).
- **Settings:** Modul-Toggle + **Diagnose-Tab** (welche Felder registriert + REST-sichtbar).
- **Public API:** Filter zur Registry-Erweiterung + Wizard-Vorbereitungs-Hooks (§ 5).

**Empirische Korrekturen aus P1** (acf-discovery.md § 4/§ 6) sind bindend eingearbeitet:
- **Dead-Code weglassen:** `rezept_post_tags` / `rezept_categories` / `rezept_cocktail_tags` /
  `rezept_trinkspiel_tags` / `rezept_equipment_tags` (0 DB-Werte) → **nicht** registrieren.
- **`rezept_tag`** (singular, lebender Orphan, 9 Werte, nicht im JSON-Export) → **aufnehmen.**
- **`reviewed_by`** (67 Werte, aktiv) → als reguläres Feld registrieren.

## 3. Eingefrorene Architektur-Entscheidungen

- **3.1 ACF free = Hard-Dependency (Plugin-Core, NICHT Modul).** Aktivierungs-Block +
  Runtime-Dormancy + Load-Order `plugins_loaded` prio 20. Implementierung in der Core-
  Activation/Bootstrap, als eigener Mini-Task **vor** der Modul-Code-Phase. Detail § 11.
- **3.2 Doppel-Owner-Pattern** (§ 2): Plugin definiert (Schema + Field-Group), ACF rendert,
  Konsumenten schreiben/lesen. Begründung: löst ADR-5 (Datenschicht ohne ACF-Runtime-Dep
  für Konsumenten) **und** E6 (ACF bleibt Editor-UI) gleichzeitig — die Konsumenten hängen
  nur am Meta-Key, nicht an ACF.
- **3.3 Field-Registry = eine Definitionsquelle, zwei Registrierungen.** Je Feld ein
  Config-Eintrag (PHP-Array); ein Registrar erzeugt daraus **beide** Seiten —
  `register_*_meta` **und** die ACF-Field-Group-Zugehörigkeit. **Warum DRY:** verhindert
  Drift zwischen REST-Schema und Editor-UI (der häufigste Legacy-Fehler). Struktur § 4.
- **3.4 KEINE CPT-/Taxonomie-Registrierung** (out of scope — ACF free auf der Site, P3
  später). **Klarstellung zu „keine hardcoded Post-Type-Listen":** die Object-Subtypes /
  ACF-Location-Werte je Feld (z. B. `reviewed_by` → `post`+`page`) sind **Field-Definitions-
  Daten aus der Discovery** (1:1-Migration der Live-Realität), **keine** post-type-
  verzweigende Feature-Logik. ADR-4-Post-Type-Agnostik gilt für **Feature**-Module (die
  `depeur_food()->get_supported_post_types()` nutzen) — meta-registry ist die Datenschicht
  und repliziert feste ACF-Locations. Die Locations sind **filterbar** (§ 5), damit
  Site/Wizard sie anpassen können, ohne den Code zu ändern.
- **3.5 Hook-Timing:** `register_*_meta` auf `init`; `acf_add_local_field_group` auf
  `acf/init` (feuert nach ACF-Load). Beide idempotent.
- **3.6 E5 Parallel-Migration.** Die Code-Field-Groups **koexistieren** zunächst mit den in
  der ACF-UI angelegten Groups der Site — `acf_add_local_field_group` überschreibt UI-Groups
  **nicht** (eigene Group-Keys). Daten in `wp_postmeta`/usermeta/termmeta bleiben unverändert
  (gleiche Meta-Keys). **Abbau der UI-Groups = manueller Deployment-Schritt durch Jonas pro
  Site, NACH Render-Verifikation** — kein Code, kein Sprint-Task.
- **3.7 Setup-Wizard-Future (deferred — NICHT bauen).** Die meta-registry-API muss
  wizard-tauglich sein: Settings über Options-API erreichbar, Registrierungs-Status
  abfragbar, Filter-Hooks zur Befüllung. Nur Architektur-Note (§ 5). TGM-Library später.
- **3.8 Owner von `_my_favorite_post_likes` (entschieden 2026-06-13, full gate).**
  **P4 `favorites` ownt dieses Feld vollständig** (Registrierung + ggf. ACF-Group
  „Like-Anzahl" + Schreib-Logik), **meta-registry registriert es NICHT.** Begründung:
  (a) protected Key (`_`-Präfix → `auth_callback` = favoriten-spezifische Zugriffspolitik);
  (b) reiner Business-State (Like-Counter), kein editorialer Content; (c) Modul-Kohäsion +
  Split-Tauglichkeit (Registrierung + Logik beim selben Owner). meta-registry registriert
  **alle anderen** Discovery-Felder.

## 4. Field-Registry (Herzstück)

### 4.1 Registry-Eintrag (Datenstruktur, config-as-code)
Die Registry ist ein PHP-Array (kein DB-State), ein Eintrag pro Meta-Key. Ein Registrar
iteriert einmal und erzeugt **beide** Seiten (§ 3.3): `register_*_meta` **und** die
ACF-Group-Zugehörigkeit.

    array(
      'name'       => 'author_jobtitle',      // Meta-Key (= ACF-Field-Name, ADR-5/E5 strikt)
      'key'        => 'field_64a68771f9008',  // ACF-Field-Key aus Discovery (Reuse, § 4.5)
      'label'      => 'Job Title',
      'acf_type'   => 'text',                  // → Sanitize + REST-Schema (Map § 4.2)
      'object'     => 'user',                  // post | user | term  (auch Array: post+term)
      'subtypes'   => array(),                 // post_types / taxonomies; [] = global (user)
      'group'      => 'author_fields',         // ACF-Group-Zugehörigkeit (§ 4.4)
      'default'    => '',
      'editor_ui'  => true,                    // false = nur register_meta (Orphan, § 4.5)
      'acf'        => array(),                 // typ-spez. ACF-Settings (choices/min/max/return_format/…)
    )

### 4.2 Typ → Sanitize / REST-Schema (eine Map, keine Per-Feld-Wiederholung)
| acf_type | sanitize_callback | REST-Schema | single/multi |
|---|---|---|---|
| text | `sanitize_text_field` | string | single |
| url | `esc_url_raw` | string (format uri) | single |
| wysiwyg | `wp_kses_post` | string | single |
| number | `absint` (+ Clamp bei min/max) | integer | single |
| true_false | `rest_sanitize_boolean` | boolean | single |
| select | Whitelist gegen `choices` → `sanitize_text_field` | string | single |
| post_object | `absint` | integer | single |
| user | `absint` | integer | single |
| taxonomy (multi_select) | `array_map('absint')` | array{items:integer} | **multi** (`single=false`) |
| link | je Subfeld (`esc_url_raw`/`sanitize_text_field`) | object{title,url,target} | single (Array) |

### 4.3 Vollständige Feld-Tabelle (34 Meta-Keys; `_my_favorite_post_likes` → P4, § 3.8)

**USER-META** (`register_user_meta`, Group `author_fields`, Location `user_role==all`):
| Meta-Key(s) | acf_type | sanitize | Anmerkung |
|---|---|---|---|
| `same_as`, `same_as_2` | text | sanitize_text_field | sameAs-Schema |
| `author_knowabout` (+`_2`..`_5`, 5×) | text | sanitize_text_field | `_5` ungenutzt (P1 § 4.1), trotzdem registriert (billig) |
| `author_jobtitle` | text | sanitize_text_field | |
| `author_alumniof` | text | sanitize_text_field | |
| `author_alumniof_url` | url | esc_url_raw | |
| `author_description` | wysiwyg | wp_kses_post | |
| `facebook/linkedin/instagram/twitter/youtube/website_profile` (6×) | url | esc_url_raw | lokal unbefüllt (P1 § 4.4), registriert |
| `email_profile` | text | **`sanitize_email`** | *ACF-Typ text, aber semantisch E-Mail → sanitize_email (derive+disclose)* |
| `static_page_for_author` | post_object (page) | absint | return id |

**TERM-META** (`register_term_meta`):
| Meta-Key | acf_type | sanitize | Taxonomie(n) | Group |
|---|---|---|---|---|
| `static_page` | post_object (page) | absint | category | kategorie_custom |
| `WPRM` | text | sanitize_text_field | category | kategorie_custom |
| `tag_group` | select | Whitelist | post_tag | tag_settings (required) |

**POST-META** (`register_post_meta`):
| Meta-Key | acf_type | sanitize | Post-Types (subtypes) | Group |
|---|---|---|---|---|
| `reviewed_by` | user | absint | post, page | reviewed_by |
| `rezept_art_tags` | taxonomy (art) | array absint | page | rezeptkategorie |
| `rezept_tags` | taxonomy (post_tag) | array absint | page | rezeptkategorie |
| `rezept_anlass_tags` | taxonomy (anlass) | array absint | page | rezeptkategorie |
| `rezept_herkunft_tags` | taxonomy (herkunft) | array absint | page | rezeptkategorie |
| `rezeptkategorie_titel` | text | sanitize_text_field | page | rezeptkategorie |
| `rezept_tag` (Orphan) | taxonomy (post_tag) | array absint | page | — (`editor_ui=false`, § 4.5) |
| `show_newsletter_form` | true_false | rest_sanitize_boolean | page, blog, tests | newsletter_* |
| `show_app_promo` | true_false | rest_sanitize_boolean | page, blog, tests | newsletter_* |
| `newsletter_position` | number | absint (Clamp 1–20) | page, blog, tests | newsletter_* |

**MIXED — POST + TERM** (`register_post_meta` **und** `register_term_meta`):
| Meta-Key | acf_type | sanitize | Locations | Group |
|---|---|---|---|---|
| `link_de`, `link_en` | link | Array-Sanitize | post,page,tests,blog (Post) + category (Term) | uebersetzungen |

### 4.4 ACF-Field-Group-Definitionen (8 Registrierungen)
| Group-Key (Reuse, § 4.5) | Location | Mitglieder |
|---|---|---|
| `group_64a6871dc3795` author_fields | user_role==all | 19 User-Felder |
| `group_6516b8d64a7b3` kategorie_custom | taxonomy==category | static_page, WPRM |
| `group_64a3ef3013119` reviewed_by | post_type==post OR page | reviewed_by |
| `group_682f1db019e50` rezeptkategorie | post_type==page | rezept_art_tags, rezept_tags, rezeptkategorie_titel, rezept_anlass_tags, rezept_herkunft_tags |
| `group_5f29db788a4f8` uebersetzungen | post/page/tests/blog + taxonomy category | link_de, link_en (position acf_after_title) |
| `group_spotlight_options_pages` | post_type==page OR page_template==single-rezeptkategorie-template.php | show_newsletter_form (d1), newsletter_position (d4), show_app_promo (**d1**) |
| `group_spotlight_options_cpt` | post_type==blog OR tests | show_newsletter_form (d1), newsletter_position (d4), show_app_promo (**d0**) |
| `group_tag_settings` | taxonomy==post_tag | tag_group |

### 4.5 Sonderfälle in der Registry
- **ACF-Key-Reuse → Override (Migrations-Mechanik, entschieden 2026-06-13):** Code-Groups
  verwenden die **exakten** Group-/Field-Keys aus der Discovery. **Warum:** ACF lässt
  PHP-registrierte („local") Field-Groups die DB-/UI-Groups **mit gleichem Key überschreiben**
  → das Plugin übernimmt lautlos, **kein** Doppel-Rendering im Editor während der
  E5-Koexistenz. Neue Sites: keine UI-Group vorhanden → sauber. Bestehende Sites: UI-Group
  wird geschattet, Jonas löscht sie später risikolos → die ACF-Feldgruppen-**Verwaltung wird
  leer** (Struktur lebt im Plugin-Code, nicht versehentlich löschbar; die Eingabefelder
  rendern weiter im Editor). Trade-off: Struktur danach nur im Plugin-Code änderbar, nicht
  mehr per ACF-UI — gewollt (versioniert + geschützt). *(Verifikation „kein Doppel-Render" +
  „Management-Liste leer" → Smoke § 12; Fallback falls ACF doch doppelt rendert → Skip-Guard,
  § 9.2.)*
- **`rezept_tag` (Orphan):** `editor_ui=false` → nur `register_post_meta` (Daten-/REST-
  Erhalt + Legacy-Backward-Compat-Read), **kein** neues ACF-Editor-Feld (es gibt keine
  Discovery-Group dafür, und es ist Legacy-Kompat, kein gepflegter Content).
- **Newsletter = 2 ACF-Groups, aber 1 Meta-Registrierung je Key:** Die Default-Differenz
  (`show_app_promo` 1 vs. 0) lebt nur im ACF-UI-Default der jeweiligen Group; das
  Meta-Schema (`register_post_meta`) wird je Key **einmal** mit Subtypes page+blog+tests
  registriert. `post` bewusst **nicht** dabei (P1 § 2.1; P5-BRIEF klärt Nachzug).
- **„taxonomy==all" (Kategorie-Custom):** als `category` registriert (Default); weitere
  Taxonomien via `depeur_food/meta/groups`-Filter ergänzbar (§ 5), nicht hardcoded.
- **Nicht-existente Post-Types (`tests`/`blog` lokal):** `register_post_meta` ist harmlos
  für (noch) nicht registrierte Typen — die Registrierung ruht, bis P3/ACF den CPT anlegt.

## 5. Public API

- **Filter `depeur_food/meta/registry`** `( array $fields )` — **zentraler Erweiterungspunkt.**
  Konsumenten-Module (und der Future-Wizard) fügen Felder hinzu / überschreiben / entfernen,
  **bevor** `register_*_meta` + ACF-Groups gebaut werden. Einziger legitimer Cross-Module-
  Berührungspunkt (kein Klassen-Import, Splitting-konform).
- **Filter `depeur_food/meta/groups`** `( array $groups )` — ACF-Field-Group-Definitionen
  (Locations etc.). Trägt die 3.4-Filterbarkeit (Locations site-/wizard-anpassbar).
- **Action `depeur_food/meta/registered`** `()` — feuert nach Abschluss aller
  `register_*_meta` (auf `init`). Konsumenten/Wizard hängen Post-Registrierungs-Logik daran.
- **Wizard-Vorbereitung (Future, § 3.7 — NICHT gebaut):** der `registry`-Filter (Befüllung)
  + eine read-only Helper-Funktion `depeur_food_meta_get_registry()` (Status-Abfrage für
  Diagnose-Tab + späterer Wizard) sind die designierten Andock-Punkte. Heute nur dokumentiert.

## 6. Datenstrukturen

- **Keine Custom Tables, keine Cache-Keys, kein persistenter State.** Reine
  Definitions-/Registrierungs-Schicht.
- **Daten-Storage = `wp_postmeta` / `wp_usermeta` / `wp_termmeta`** mit **identischen
  Meta-Keys** wie ACF heute (E5-Koexistenz, ADR-5). Keine neuen Keys erfunden; Daten werden
  durch die Migration **nicht** angefasst.
- **Registry-Config = Code** (`config/fields.php` + `config/groups.php`, geben Arrays
  zurück) — versionierbar, kein DB-Zustand.
- **Modul-Option** `depeur_food_meta-registry` (ADR-1-Konvention): minimal/leer — das Modul
  hat keinen eigenen persistenten Settings-State außer dem Aktiv-Flag in
  `depeur_food_modules`. (Diagnose-Tab § 7 ist read-only, berechnet zur Laufzeit.)
- **ACF-Field-/Group-Keys:** Reuse der Discovery-Keys (§ 4.5) — eine Quelle, kein Drift.

## 7. Settings-UI (minimal)

Pro § 6.2 (Admin-UI-Doku): das Modul-Tab trägt ein **Modul-Intro** (was meta-registry tut,
Doppel-Owner-Pattern in 1–2 Sätzen) + read-only Diagnose. **Keine** editierbaren Settings
außer dem Modul-Toggle.

- **7.1 Modul-Toggle:** über das Core-Tab-System (Task 4, existiert). Kein neuer Code im Modul.
- **7.2 Diagnose-Tab (read-only):** zeigt zur Laufzeit berechnet, **was registriert ist** —
  damit man bei Problemen (Feld fehlt in REST, ACF rendert nicht) sofort sieht, woran es liegt:
  - Tabelle: `Meta-Key | Object (post/user/term) | Subtypes | ACF-Group | show_in_rest ✓/✗ | Editor-UI ✓/✗ | Sanitize`.
  - Summary: „N Felder registriert · M REST-sichtbar · ACF aktiv: ja/nein · ACF-Groups gerendert: K".
  - Zweck: Smoke-/Support-Hilfe, keine Aktion (kein Schreiben).
- **7.3 Core-Voraussetzung für 7.2 (entschieden 2026-06-13):** Der Tab-Renderer (Task 4) kennt
  heute nur `checkbox/text/select/password`. Eine read-only Tabelle ist kein Formularfeld.
  **Entscheidung:** einen kleinen, generisch nützlichen Feldtyp `'html'` (read-only Markup) zum
  Core-`render_field()` ergänzen — exakt der in CLAUDE.md › Architecture Notes vorgesehene
  „Field_Renderer-Wachstumsfuge"-Fall. meta-registry generiert die Tabelle und reicht sie als
  `html` durch. Klein, einmalig, anderen Modulen nützlich. (Core-Mini-Task, § 11/§ 13.)

## 8. Lifecycle

- **Laden:** `ModuleManager::init()` lädt `module.php` nur wenn aktiv (Kanon). Konstruktor
  der Bootstrap-Klasse verdrahtet die Hooks (wordpress.md § 1.1).
- **`init`:** Registry bauen (`depeur_food/meta/registry`-Filter anwenden) → `register_post_meta`
  / `register_user_meta` / `register_term_meta` je Eintrag → Action `depeur_food/meta/registered`.
- **`acf/init`:** `depeur_food/meta/groups`-Filter → `acf_add_local_field_group` je Gruppe
  (defensiv `function_exists`-gated; Timing-Edge § 9.10).
- **Aktivierung:** keine eigene Aktivierungsroutine nötig — alles sind Runtime-Hooks. **Keine
  Daten-Migration** (Meta-Keys identisch, `wp_postmeta` unangetastet).
- **Deaktivierung:** Registrierungen laufen nicht mehr → Felder verlieren REST-Schema/Sanitize
  + (wenn ACF-UI-Groups bereits gelöscht) verschwinden die Editor-Felder. **Daten bleiben**
  in `wp_postmeta`/usermeta/termmeta (verwaiste, aber sichere Content-Daten). ⚠️ Deaktivierung
  NACH UI-Group-Löschung = Felder im Editor weg bis Reaktivierung — im Diagnose-Tab/Doku
  hinweisen.
- **Uninstall:** `uninstall.php` (Task 1, LIKE-Purge `depeur_food_%`) räumt die Modul-Option.
  **Meta-DATEN werden NICHT gelöscht** (Content-Daten, kein Plugin-State — korrekt).

## 9. Edge-Cases

- **9.1 ACF nicht aktiv (Runtime):** Core-Dependency-Guards (§ 11) verhindern das im Normalfall.
  Defensiv trotzdem: `register_*_meta` läuft **ACF-unabhängig** (Datenschicht pur, ADR-5 →
  REST/Sanitize funktionieren auch ohne ACF), `acf_add_local_field_group` nur bei
  `function_exists`. Ohne ACF: Daten/REST ja, Editor-UI nein (graceful).
- **9.2 ACF-Koexistenz / Doppel-Render:** Key-Reuse → ACF-„local"-Override (§ 4.5) → **eine**
  gerenderte Group. Bestehende DB-/UI-Groups werden überschattet; nach manueller Löschung pro
  Site ist die ACF-Feldgruppen-**Verwaltung leer** (Struktur lebt im Plugin, nicht versehentlich
  löschbar — Jonas-Anforderung). **Smoke § 12 verifiziert „kein Doppel-Render".** Fallback bei
  unerwartetem Doppel-Render: Skip-Guard (Group nur registrieren, wenn keine aktive DB-Group
  gleichen Keys) — nur falls Smoke das nötig macht.
- **9.3 Site hat EIGENE zusätzliche ACF-Groups** (nicht in Discovery): unberührt — meta-registry
  registriert nur seine Keys; fremde Groups (andere Keys) koexistieren konfliktfrei.
- **9.4 `link`-Array-Serialisierung:** `link_de`/`link_en` speichern `{title,url,target}`.
  `register_*_meta` mit `type=object` + REST-`schema` (object, properties). Sanitize je Subfeld.
  Konsumenten via `get_post_meta` bekommen das Array; `get_field(return_format=url)` die URL.
  REST exponiert das Objekt (P7 language-selector liest daraus).
- **9.5 `rezept_tag` (Orphan):** meta-only (`editor_ui=false`), 9 Legacy-Werte erhalten,
  Backward-Compat-Read funktioniert; **keine** Neuschreibung via Editor (kein ACF-Feld) → eingefrorene
  Legacy-Daten. P8 entscheidet, ob die 9 Werte nach `rezept_tags` konsolidiert werden.
- **9.6 Dead-Code-Felder:** 5 `rezept_*` (0 Werte, P1 § 4.3) **nicht** registriert. Empirisch
  kein Datenverlust-Risiko (nie befüllt).
- **9.7 Taxonomie-/CPT-Felder ohne registrierte Taxonomie:** `register_post_meta` läuft immer;
  ein ACF-`taxonomy`-Feld rendert nur sinnvoll, wenn die Taxonomie (`art`/`anlass`/`herkunft`)
  existiert (ACF/P3). Fehlt sie → ACF-Feld leer/graceful, kein Fatal.
- **9.8 Nicht-existente Post-Types (`tests`/`blog` lokal):** Registrierung ruht harmlos bis P3/ACF
  den CPT anlegt (§ 4.5).
- **9.9 Graceful Degradation (§ 1.1):** leere Meta → keine Notices; Sanitize-Callbacks
  behandeln `''`/`null`/`[]` sauber.
- **⚠️ 9.10 `acf/init`-Timing (wichtiger Fund):** ACF feuert `acf/init` während des `init`-Hooks
  (früh, ~prio 5). Lädt `ModuleManager` das Modul **nach** diesem Zeitpunkt, ginge der
  `add_action('acf/init', …)` ins Leere → Field-Groups würden NICHT registriert. **Mitigation
  (bindend):** im Konstruktor `if ( did_action('acf/init') ) { groups sofort registrieren; }
  else { add_action('acf/init', …); }`. **In Smoke § 12 explizit prüfen** (Editor-Felder
  rendern?), weil die ModuleManager-Init-Priorität dies entscheidet.

## 10. Anti-Patterns (nicht tun)

- **KEINE Cross-Module-Direktimporte** (Splitting-Strategie). Konsumenten lesen via
  `get_post_meta`/`get_field`/REST oder erweitern via `depeur_food/meta/registry`-Filter —
  **nie** `meta-registry`-Klassen importieren.
- **KEINE CPT-/Taxonomie-Registrierung** im Modul (ACF free + P3, out of scope).
- **KEINE globale hardcoded Post-Type-Liste / kein `get_supported_post_types()`** in der
  Field-Registry — Locations sind migrierte Field-Definitions-Daten (§ 3.4), keine Feature-Logik.
- **KEINE ACF-Runtime-Dependency für die Datenschicht** — `register_*_meta` läuft ACF-frei
  (ADR-5); nur `acf_add_local_field_group` ist `function_exists`-gated.
- **KEINE Daten-Migration / kein Umschreiben** von `wp_postmeta`/usermeta/termmeta (Keys identisch).
- **KEINE neuen Meta-Keys erfinden** — 1:1 die ACF-Field-Namen aus der Discovery (E5/ADR-5).
- **KEIN Schreiben von Werten** — meta-registry **definiert** nur; Werte schreiben = Konsumenten.
- **KEINE parallele Zweit-Registrierung** desselben Keys in einem anderen Modul (außer der
  bewussten P4-Ausnahme `_my_favorite_post_likes`, § 3.8) — ein Registry-Eintrag = Source of Truth.
- **KEIN Logging im Hot-Path** (debug.log sauber halten).
- **KEIN eigenes Bootstrapping** außerhalb `module.php` (ModuleManager ist der einzige Einstieg).

## 11. Dependencies

- **ACF free = Hard-Dependency** (E6; Pro nicht nötig). **Implementierung im Plugin-Core,
  NICHT im Modul** — eigener Mini-Task **vor** der P2-Modul-Code-Phase:
  - **Aktivierungs-Hook** (`Core\Activation`): `class_exists('ACF')` prüfen; sonst
    `deactivate_plugins(plugin_basename())` + `wp_die`/`WP_Error` „depeur-food benötigt
    Advanced Custom Fields (Free oder Pro) — bitte zuerst installieren & aktivieren".
  - **Runtime-Check** (Plugin-Hauptdatei, `plugins_loaded` **prio 20**, ACF lädt prio 10):
    ACF inaktiv → alle Module **dormant** (Lazy-Load scheitert sauber) + Admin-Notice mit
    Link zum ACF-Plugin-Repository.
  - **Load-Order:** depeur-food lädt nach ACF (prio-20-Pattern sichert es).
- **Taxonomien/CPTs** (`art`/`anlass`/`herkunft`, `cocktails` …) müssen auf der Site via
  ACF/P3 existieren, damit `taxonomy`-Felder sinnvoll rendern — **soft** (graceful, § 9.7).
- **Core-`html`-Feldtyp** (§ 7.3) — kleine Core-`render_field`-Erweiterung, Prerequisite für
  den Diagnose-Tab.
- **Konsumenten** (P4–P9) hängen nur am Meta-Key, nicht an meta-registry-Klassen.

## 12. Smoke-Test

**Standard (alle Sessions):** `php -l` clean · phpcs Exit 0 · `wp plugin check` 0 neue Findings ·
debug.log frei · PHP 8.2 lokal (+ 8.4-Remote-Deprecation-Check bei Remote-Test) ·
Pre/Post-Option-Snapshot identisch (keine Daten-Migration).

**Aktiv** (`depeur_food_modules` enthält `meta-registry`):
- **Meta registriert:** `wp eval` → `get_registered_meta_keys('post')`/`('user')`/`('term')`
  enthalten die erwarteten Keys (Stichprobe: `link_de`, `reviewed_by`, `author_jobtitle`,
  `tag_group`, `rezept_tags`, `show_newsletter_form`).
- **REST-Sichtbarkeit:** `/wp-json/wp/v2/posts/{id}` exponiert `meta.link_de` etc.
  (`show_in_rest` greift); `link_de` als Objekt (`{title,url,target}`, § 9.4).
- **ACF-Render + kein Doppel-Render (§ 4.5/§ 9.2):** Post-/Page-/User-/Term-Editor öffnen →
  jedes Feld **genau einmal** sichtbar (Override greift); `acf_get_field_group('group_…')`
  liefert die local-Group.
- **`acf/init`-Timing (§ 9.10):** ACF-Groups tatsächlich registriert trotz ModuleManager-Init-
  Reihenfolge (`did_action`-Mitigation verifizieren).
- **Management-Liste sauber:** nach Löschung der DB-/UI-Group → Custom Fields → Feldgruppen
  zeigt die Group nicht (mehr) als editier-/löschbar (Jonas-Anforderung, § 9.2).
- **Sanitize:** ungültiger Wert via `update_post_meta`/REST → sanitisiert gespeichert.
- **Orphan/Dead-Code:** `rezept_tag` hat `register_post_meta` (REST), **kein** ACF-Editor-Feld;
  die 5 Dead-Code-`rezept_*` sind **nicht** registriert.
- **Diagnose-Tab:** rendert read-only Tabelle + korrekte Counts.

**Inaktiv** (`depeur_food_modules` leer): Keys nicht registriert, ACF-Groups weg (Editor-Felder
verschwinden, falls UI-Groups gelöscht), **Daten in `wp_postmeta` intakt**.

**ACF-Dependency:** ACF deaktivieren → Plugin-Aktivierung blockiert / Runtime dormant + Notice
(Core-Mini-Task-Verifikation).

## 13. Datei-Liste (anzulegen/ändern)

**Modul** (`plugins/depeur-food/modules/meta-registry/`, Kanon: kebab-Root, PascalCase-Subordner):
- `manifest.php` (neu, ~20) — name/version/description.
- `module.php` (neu, ~20) — `new …\MetaRegistry( basename( __DIR__ ) )`.
- `BRIEF.md` (dieser).
- `config/fields.php` (neu, ~180) — Field-Registry-Array (§ 4.3). *Daten, via `require` geladen,
  nicht autoloaded → lowercase-Ordner zulässig.*
- `config/groups.php` (neu, ~130) — ACF-Field-Group-Definitionen (§ 4.4).
- `Registry/Field_Registrar.php` (neu, ~140) — `register_*_meta`-Loop + Typ→Sanitize/REST-Map (§ 4.1/4.2).
- `Registry/Group_Registrar.php` (neu, ~90) — `acf_add_local_field_group`-Loop + `acf/init`-Timing (§ 9.10).
- `Admin/Settings.php` (neu, ~110) — SettingsRegistry-Anmeldung + Diagnose-Tab-Render (§ 7).
- *(optional)* `Support/Sanitizers.php` — Typ→Callback-Map, falls Field_Registrar zu groß.

**Core** (außerhalb Modul — eigene Mini-Tasks **vor/mit** P2, § 11/§ 7.3):
- `src/Core/Activation.php` (ändern, ~+15) — ACF-`class_exists`-Aktivierungs-Block.
- `depeur-food.php` (ändern, ~+20) — `plugins_loaded` prio 20 Runtime-Check + Admin-Notice.
- `src/Core/Settings/SettingsPage.php` (ändern, ~+25) — `render_field()` um read-only `html`-Typ
  ergänzen (Field_Renderer-Wachstumsfuge).

**LOC gesamt** ~750–850 · **Sessions:** 2–3 (Core-Mini-Tasks zuerst, dann config → Registrars → Admin → Smoke).
