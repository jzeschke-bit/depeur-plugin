# BRIEF.md — `content-types` (CPT- & Taxonomie-Definition + -Registrierung)

**BRIEF-Schema-Version: 1.1** — geschäftslogik-tragender Volltext-Brief nach § 12.2.
Erbt das geschäftslogik-Schema (1.1) von `cache-bridge`/`meta-registry`; die reine
Modul-Mechanik (Naming, FS-Safety, Discovery, Autoloader, Lifecycle-Grundmuster) steht
im `example-module/BRIEF.md` (v1.0) und wird hier **nicht** wiederholt.

> **§ 12.2-Pflicht-Brief (geschäftslogik-tragend).** Vor jedem Code zu schreiben und
> freizugeben (§ 12.3), lebende Doku (§ 12.4). **Migrations-Quelle = ACF free (E7)** —
> die live registrierten CPTs/Taxonomien werden empirisch enumeriert (§ 2), nicht aus
> einem Legacy-Plugin gelesen (Abweichung von § 12.2 #1, analog meta-registry/cache-bridge).

## 1. Meta / Einordnung

- **Plugin-Cluster:** **Depeur Food** (Content-Strukturschicht). **Fundamental für CPT-Sites** —
  P4/P6/P8/P9 operieren auf den hier registrierten Typen; `cocktails`/`bar-equipment`/`trinkspiele`
  existieren auf alkipedia.com nur, solange dieses Modul (oder noch ACF) sie registriert.
- **Default-Status:** **Default-OFF** (bewusst, ≠ meta-registry). Eine frische Installation
  registriert **keine** fremden CPTs (Invariante § 3.3). Aktivierung **und** befüllter
  Definitions-Store sind beide nötig, damit überhaupt etwas registriert wird. Auf
  einfachandersessen.de (reines `post`) bleibt das Modul leer/inaktiv.
- **Brief-Verwandtschaft:** Modul-Kanon aus `example-module/BRIEF.md` v1.0; config-/Provider-
  Mechanik + Schema-1.1-Struktur analog `meta-registry/BRIEF.md`.
- **Voraussetzung:** P0 (CPT-Quelle = ACF free, erfüllt). **Entsperrt:** P8/P9 (Kategorie-Seiten
  brauchen registrierte CPTs/Taxonomien) sowie die CPT-Feature-Anwendung in P4/P6.
- **Cross-Module-Disziplin (Splitting-Strategie):** `content-types` hat **keine** direkten
  Cross-Module-Importe. Die Verbindung zum Core-`PostTypeRegistry` läuft ausschließlich über WP
  (das Modul `register_post_type`'t, der Resolver sieht das Ergebnis via `get_post_types()`).
- **Status:** **BRIEF v1.0 freigegeben 2026-06-14.** Code-Phase next (§ 12.3). Lebende Doku (§ 12.4).

## 2. Zweck & Funktionalitäts-Inventar

**Zweck:** Die CPT-/Taxonomie-**Registrierung** von ACF ins Plugin holen (E7), **generisch**
(nicht cocktail-hardcoded) und **post-type-agnostisch** (ADR-4-treu): das Plugin registriert nur,
was eine Site explizit konfiguriert hat — nie automatisch.

**Empirie-Befund (live enumeriert 2026-06-14, localhost:8889) — die zu replizierende Realität:**

| CPT | hierarchical | has_archive | supports (Auszug) | menu_pos | Taxonomien |
|---|---|---|---|---|---|
| `cocktails` | true | `'cocktails'` (string) | title,editor,author,comments,revisions,thumbnail,custom-fields | 3 | post_tag, anlass, art, herkunft |
| `bar-equipment` | true | `true` (bool) | title,editor,thumbnail,comments,author,custom-fields | 6 | — |
| `trinkspiele` | true | `'trinkspiele'` (string) | + page-attributes, post-formats | 5 | — |

| Taxonomie | hierarchical | object_type | public |
|---|---|---|---|
| `anlass` | false | cocktails | true |
| `art` | false | cocktails | true |
| `herkunft` | false | cocktails | **false** ⚠️ |

**Schlüssel-Funde:** (1) `blog`/`tests` aus dem ACF-Field-Export sind **keine** CPTs — nur
Field-Locations. (2) Args variieren pro Typ (supports, menu_position, has_archive-Form, `herkunft`
nicht-public) → **1:1-Arg-Replikation ist Pflicht**, keine Uniform-Defaults. (3) Quelle sauber
auslesbar: `acf_get_acf_post_types()`/`acf_get_acf_taxonomies()` (ACF stored `import_source: cptui`),
und — quellenunabhängiger — die **Live-Objekte** (`get_post_type_object`/`get_taxonomy`), die der
Importer nutzt.

**Zwei klar getrennte Wirkungen aus einer Definitionsquelle:**
1. **Registrierung** — `register_post_type()`/`register_taxonomy()` auf `init`, **args-treu 1:1**.
2. **Discovery/Import** — einmaliges Auslesen der live registrierten Typen → Normalisierung auf
   saubere register-Args → Übernahme in den Plugin-Definitions-Store. Danach läuft die
   Registrierung **ACF-frei** aus Plugin-Daten.

**Funktionalitäts-Inventar:**
- **Definitions-Store** — Plugin-Option (ADR-1) mit den CPT-/Taxonomie-Definitionen pro Site (§ 4/§ 6).
- **Seed-Pack** (Code) — die 3 alkipedia-CPTs + 3 Taxonomien als versioniertes, **nicht**
  auto-registriertes Import-Set (Recovery-Quelle, § 3.4/§ 4.4).
- **Type_Provider / Taxonomy_Provider** — registrieren auf `init` aus dem Store (leerer Store = No-Op).
- **Importer** (Admin) — Scan der live registrierten Typen, Vorschau, opt-in-Übernahme in den Store.
  **Sicherheits-Pfad:** Nonce + `manage_options` (volles Gate beim Code, § 7/§ 11).
- **Orphan-Detektor** — erkennt publizierte Posts nicht (mehr) registrierter Typen (§ 3.4/§ 9.2).
- **Settings-UI** — read-only Diagnose + Importer-Sektion + § 6.2-Intro/Field-Descriptions.
- **Core-Anbindung (ADR-4):** neu registrierte öffentliche CPTs erscheinen als **Feature-Kandidaten**
  in `PostTypeRegistry::get_available()` — Registrierung ≠ Feature-Aktivierung (zwei Optionen, § 3.6).
- **Public API:** Filter zur programmatischen Definitions-Erweiterung + Wizard-Andockpunkte (§ 5).

## 3. Eingefrorene Architektur-Entscheidungen

- **3.1 Modul-Name `content-types` + Core/Modul-Split (Weggabelung B).** Core-`PostTypeRegistry`
  (ADR-4) = *welche registrierten Typen bekommen Features* (Selektor/Resolver, liest
  `depeur_food_supported_post_types`). Modul `content-types` = *welche Typen existieren*
  (Definer + Registrar). **Begründung der Namenswahl** (statt `post-type-registry`/`cpt-manager`):
  (a) null lexische Kollision mit Core-`PostTypeRegistry`; (b) deckt beide Hälften ab (CPTs **und**
  Taxonomien); (c) trifft das Operator-Mentalmodell (ACF labelt den Bereich „Inhaltstypen").
  **Klassennamen-Prinzip:** **kein** `PostType…`- und **kein** `…Registry`-Token im Modul (Vokabular:
  `Type_Provider`/`Taxonomy_Provider`/`Store`/`Importer`/`Orphan_Detector`) → maximale Divergenz,
  kein Echo auf die Core-Klasse. Keine direkte Klassen-Kopplung Modul↔Core (Verbindung nur über WP).
- **3.2 Persistenz = DB-Option-getrieben + Code-Seed-Pack (Weggabelung A).** Die Definitionen leben
  in einer Plugin-Option (ADR-1, § 6) — operator-/wizard-beschreibbar, generisch. Das Seed-Pack im
  Code ist **Import-Vorlage**, kein Auto-Registrar.
- **3.3 Harte Invariante: leerer/fehlender Store ⇒ nichts registrieren (A.1).** Kein Default-Seed in
  der Option, keine Auto-Anlage bei frischer Installation. Die Provider iterieren über den Store; ist
  er leer/abwesend → No-Op. Explizit getestet (§ 12), gegen „Bequemlichkeits-Defaults" geschützt (§ 10).
- **3.4 Recovery-Pfad bei Option-Verlust (A.2).** Geht die Option verloren/korrumpiert **und** ist ACF
  bereits abgeschaltet, verschwinden die CPTs → cocktails-Posts würden 404'en. Mitigation: (a) das
  Seed-Pack ist jederzeit **per Ein-Klick re-importierbar** (Importer kennt „alkipedia-Definitionen
  wiederherstellen"); (b) **Orphan-Detektor** — das Modul erkennt „es existieren publizierte Posts
  eines nicht (mehr) registrierten Post-Types" und zeigt eine Admin-Notice mit Re-Import-Link, bevor
  der Operator es im Frontend bemerkt (§ 9.2). **Das Seed-Pack ist ein Snapshot zur Migrationszeit;
  bei späterer Arg-Drift ist der Store die Wahrheit** — der Seed ist nur der Recovery-Boden,
  überschreibt nie automatisch.
- **3.5 ACF = einmalige Discovery-/Import-Quelle, kein Runtime-Coupling (Weggabelung C).** Der Importer
  liest den live registrierten Zustand **nur** beim Scan; die laufende Registrierung ist
  ACF-unabhängig. **Zwei verschiedene ACF-Beziehungen im Plugin — bewusst gegensätzlich:**
  - `meta-registry` (P2) **behält ACF** als Editor-UI-Renderer (Doppel-Owner-Pattern) — Felder werden
    weiter in ACF editiert.
  - `content-types` (P3) **löst ACF ab** für die CPT-/Taxonomie-Registrierung — nach Import +
    Verifikation werden die ACF-CPT-/Taxonomie-Definitionen pro Site deaktiviert.
  - Begründung des Unterschieds: Editor-UI nachzubauen ist teuer (E6 verschoben), CPT-Args zu
    replizieren ist billig + entkoppelt das Plugin von ACF für die Struktur (E7). Explizit
    gegenübergestellt, damit niemand rätselt, warum ein Modul ACF braucht und das andere es loswird.
- **3.6 ADR-4-Integration: Registrierung ≠ Feature-Aktivierung (entkoppelt).** `content-types`
  registriert den Typ in WP. Ob der Typ Features (Favoriten/Schema/…) bekommt, entscheidet weiterhin
  `depeur_food_supported_post_types` (Core, separates Setting). Ein neu registrierter öffentlicher CPT
  taucht automatisch in `PostTypeRegistry::get_available()` als wählbarer Kandidat auf — aktiviert wird
  er erst durch die Core-Auswahl. **Kein Auto-Coupling**; der Importer zeigt nach Import nur einen
  dezenten Wegweiser zum Core-Tab (§ 6/§ 7). Zwei Optionen, zwei Zuständigkeiten.
- **3.7 Args-Treue 1:1.** Migrierte Definitionen replizieren die Live-Args exakt (rewrite-slug,
  has_archive-Form string/bool, per-Typ-`supports`, `hierarchical`, `menu_position`, `public` je
  Taxonomie). Keine vereinheitlichten Defaults — sonst brechen URLs/Archive/Editor-Verhalten.
- **3.8 ACF-Abschaltung = Deployment-Schritt, kein Commit.** Nach Import + Render-/URL-Verifikation
  deaktiviert Jonas die ACF-CPT-/Taxonomie-**Definitionen** pro Site (das ACF-Plugin bleibt aktiv —
  § 11). Nicht im selben Commit wie der Code, nicht im Sprint-Task-Scope. Reihenfolge: § 8-Checkliste.
- **3.9 Setup-Wizard-Future (deferred — NICHT bauen).** API-First: Store über Options-API erreichbar,
  Import programmatisch auslösbar, Definitions-Filter vorhanden. Nur Architektur-Note (§ 5), kein
  Wizard-Code jetzt.

## 4. Definitions-Datenstruktur (Herzstück)

### 4.1 Store-Schema (die Plugin-Option)
Eine Option `depeur_food_content-types` (ADR-1-Konvention, Bindestrich wie `meta-registry`),
**autoload=yes** (wird auf jedem `init` zur Registrierung gelesen, klein, keine Secrets — § 4.5-Bibel):

    array(
      'version'    => 1,                 // Definitions-Schema-Version (Future-Migration)
      'post_types' => array( '<slug>' => <cpt-def>, … ),
      'taxonomies' => array( '<slug>' => <tax-def>, … ),
    )

**Leer/abwesend = nichts registrieren** (Invariante § 3.3). Kein Default-Seed in der Option.

### 4.2 CPT-Definitions-Eintrag (kuratiert-vollständig, = die CPT-UI/ACF-Stellschrauben)
Nicht der opake `WP_Post_Type`-Dump, sondern genau die Args, die ACF/CPT-UI exponieren und die
Verhalten beeinflussen — beim Import aus dem **Live-Objekt** erfasst (§ 4.5):

| Key | Quelle (Live) | Beispiel cocktails |
|---|---|---|
| `labels` | `$obj->labels` (voll, wie importiert) | 33-Key-Array |
| `public` / `publicly_queryable` / `exclude_from_search` | direkt | true / true / false |
| `hierarchical` | direkt | true |
| `show_ui` / `show_in_menu` / `show_in_nav_menus` / `show_in_rest` | direkt | true… |
| `rest_base` | direkt (false ⇒ Slug-Default) | false |
| `menu_position` / `menu_icon` | direkt | 3 / dashicons-admin-post |
| `supports` | `get_all_post_type_supports()` | title,editor,author,… |
| `has_archive` | direkt (string **oder** bool — formtreu) | `'cocktails'` |
| `rewrite` | direkt (slug, with_front, feeds, pages) | {slug:cocktails,…} |
| `query_var` / `can_export` / `delete_with_user` | direkt | … |
| `taxonomies` | `get_object_taxonomies()` | post_tag,anlass,art,herkunft |

Capabilities bleiben Default, **außer** umbenannt (`capability_type`/`map_meta_cap` nur erfassen wenn
non-default — alkipedia: nicht umbenannt → weglassen). Begründung der Key-Auswahl: das ist exakt die
Knopf-Oberfläche, die wir von ACF ablösen — nicht mehr, nicht weniger. **Callbacks** (z. B.
`register_meta_box_cb`) sind durch die Allowlist ausgeschlossen → Store bleibt serialisierbar (§ 7.3).

### 4.3 Taxonomie-Definitions-Eintrag
| Key | Beispiel | Anmerkung |
|---|---|---|
| `labels` | (voll) | |
| `object_type` | `['cocktails']` | **welche CPTs** die Taxonomie trägt (Verknüpfung!) |
| `hierarchical` | false | (tag-artig) |
| `public` / `publicly_queryable` | **herkunft: false** ⚠️ | per-Taxonomie verschieden — Uniform-Default wäre ein Bug |
| `show_ui`/`show_in_menu`/`show_in_nav_menus`/`show_in_rest`/`rest_base`/`show_admin_column`/`show_tagcloud` | direkt | |
| `rewrite` | {slug, with_front, hierarchical} | |
| `query_var` | direkt | |

### 4.4 Seed-Pack (`config/seed.php`, Code)
Gefrorenes, **nicht auto-registriertes** Import-Set der 3 alkipedia-CPTs + 3 Taxonomien — aus dem
empirisch gedumpten Live-Zustand authored (§ 2). Zwei Verwendungen: (a) **Recovery** (Store-Verlust,
ACF schon weg → Ein-Klick-Restore, § 3.4), (b) Offline-Bootstrap einer neuen Site ohne ACF-Quelle.
**Seed = Snapshot zur Migrationszeit; bei späterer Arg-Drift ist der Store die Wahrheit** — der Seed
ist nur der Recovery-Boden, überschreibt nie automatisch.

### 4.5 Provider- & Importer-Mechanik
- **Provider** (`Type_Provider`/`Taxonomy_Provider`) auf `init`: iterieren über den Store, registrieren
  **post types zuerst, dann taxonomien** (object_type-Verknüpfung greift). **Skip-Guard:**
  `if ( post_type_exists($slug) ) continue;` bzw. `taxonomy_exists()` — registriert nie doppelt. Trägt
  die Koexistenz + Deployment-Order (§ 8/§ 9.3).
- **Importer** (Admin, Nonce + `manage_options`): zwei Quellen — **(1) Live-Scan** (Primärpfad während
  ACF noch aktiv): enumeriert nicht-`_builtin` Typen, Vorschau, **public + nicht-Denylist**
  (`acf-*`/`kadence_*`/`wprm_*`/`rm_*`/`kb_icon`) vorangehakt, Operator bestätigt → normalisiert
  Live-Args → schreibt Store. **(2) Seed-Pack-Restore** (Recovery). Idempotent; Re-Import überschreibt
  den jeweiligen Slug-Eintrag.
- **⚠️ Taxonomie-Scan-Falle (`herkunft`):** Taxonomien **nicht** nach `public` filtern — `herkunft` ist
  `public=false` und würde sonst stillschweigend wegfallen → cocktails-Taxonomie-Queries brechen.
  Scan-Regel: Taxonomien, deren `object_type` einen der gewählten CPTs schneidet, **unabhängig von
  public**, minus Plugin-Denylist.

### 4.6 Sonderfälle
- **`has_archive` form-treu** (string-Slug bei cocktails/trinkspiele, bool bei bar-equipment) — Typ
  erhalten, nicht zu bool normalisieren.
- **Hierarchische CPTs** (alle 3 `hierarchical=true`) — bewusst so erfasst (page-artiges Verhalten),
  nicht „korrigiert".
- **CPT ohne eigene Taxonomien** (bar-equipment/trinkspiele) — `taxonomies` leer, kein Problem.

## 5. Public API
Splitting-konform (nur Hooks, kein Klassen-Import):
- **Filter `depeur_food/content_types/post_types`** `( array $defs )` — finale CPT-Defs **vor** der
  Registrierung (Site/Wizard/Modul ergänzt/überschreibt/entfernt). Trägt § 3.2-Filterbarkeit.
- **Filter `depeur_food/content_types/taxonomies`** `( array $defs )` — analog Taxonomien.
- **Filter `depeur_food/content_types/import_definition`** `( array $args, string $slug, string $object )`
  — eine erfasste Definition vor dem Speichern justieren (Importer-Normalisierungs-Haken).
- **Filter `depeur_food/content_types/importable`** `( array $candidates )` — Scan-Vorschlagsliste
  anpassen (Denylist erweitern / Typ erzwingen).
- **Action `depeur_food/content_types/registered`** `( array $post_types, array $taxonomies )` — nach
  allen `register_*`-Aufrufen auf `init`.
- **Read-only Helper `depeur_food_content_types_get_store()`** — Status-Abfrage (Diagnose-Tab § 7 +
  Future-Wizard).
- **Wizard-Vorbereitung (deferred, § 3.9):** Import programmatisch auslösbar (Methode/Action), Store
  options-API-erreichbar, Defs filterbar — die designierten Andockpunkte, heute nur dokumentiert.

## 6. Datenstrukturen
- **Option `depeur_food_content-types`** (§ 4.1, autoload=yes). **Einziger** Schreibpfad = Importer
  (eigener Nonce/Cap-Handler); der SettingsPage-Formular-Save (Task 4) rührt sie **nicht** an, weil der
  Modul-Tab read-only ist (Diagnose) — kein Clobber-Risiko (analog meta-registry).
- **Keine Custom Tables.** **Ein Transient** `depeur_food_content_types_orphan_check` cached das Ergebnis
  des § 3.4-Detektors, invalidiert bei Store-Write **und** Modul/Plugin-Akt/Deakt (§ 9.9) — damit der
  `admin_notices`-Scan nicht jeden Admin-Load kostet.
- **Seed-Pack = Code** (`config/seed.php`, gibt Array zurück) — versioniert, kein DB-State.
- **`wp_posts` unangetastet:** die 217 cocktails-Posts u. a. liegen mit `post_type='cocktails'` in
  `wp_posts`; das Modul sorgt nur dafür, dass der Typ registriert ist (damit sie abfrag-/sichtbar
  bleiben). **Keine** Daten-Migration (Stance wie meta-registry).
- **Importer-UI nach Import (Punkt-2-Wegweiser, kein Auto-Coupling):** dezenter Hinweis „Typ registriert.
  Für Plugin-Features (Favoriten, Schema …) im **Core-Tab → Unterstützte Post-Types** aktivieren."

## 7. Settings-UI + Importer-Flow

Pro § 6.2 (Admin-UI-Doku); Diagnose-Muster + read-only `html`-Feldtyp aus `meta-registry`/Core
(`3b83b4d`) wiederverwendet.

- **7.1 Modul-Toggle** — über das Core-Tab-System (Task 4). Kein neuer Code.
- **7.2 Modul-Tab (read-only Diagnose):** keine editierbaren Formularfelder → Submit unterdrückt (wie
  meta-registry, kein Clobber des Store).
  - **Intro (§ 6.2):** was `content-types` tut (registriert die von der Site konfigurierten
    CPTs/Taxonomien, löst ACF für die *Struktur* ab — § 3.5-Gegenüberstellung in 1–2 Sätzen), wann
    aktivieren (CPT-Sites wie alkipedia.com), wann nicht (reine `post`-Sites → leer lassen).
  - **Diagnose-Tabelle (`html`):** `Slug | CPT/Tax | registriert (post_type_exists ✓/✗) | Quelle
    (Store/ACF/Fremd) | Posts-Count | Taxonomien bzw. object_type`. + **Status-Zeile:** „Store: N CPTs /
    M Taxonomien · ACF-CPTs aktiv: ja/nein · Skip-Guard ruht für: [Slugs, die noch ACF/Fremd liefert]".
    Koexistenz-Lage auf einen Blick (Smoke-/Support-Hilfe, keine Aktion).
  - **Einstieg zur Importer-Sektion** (gleicher Tab, unterhalb).
- **7.3 Importer (Sektion im selben Modul-Tab `&tab=content-types`, eigenes Form/Nonce — Sicherheits-Pfad
  → volles Gate beim Code):**
  - **Reihenfolge strikt (§ 3.3-Bibel):** Cap (`manage_options`) → Nonce → Sanitize → Process → Escape.
  - **User-Input = nur die Slug-Auswahl** (Checkboxen). Slugs via `sanitize_key` + **Whitelist gegen die
    gescannten Kandidaten**. Die **Args selbst sind kein User-Input** — sie werden serverseitig aus dem
    Live-Objekt (`get_post_type_object`/`get_taxonomy`) für die gewählten Slugs erfasst. Kein
    Array-Injection in `register_post_type`.
  - **Kuratierte Key-Allowlist (§ 4.2/4.3) schließt Callbacks aus** (`register_meta_box_cb` etc.) → keine
    Closures in der Option, Store serialisierbar + sicher.
  - **Flow:** „Scannen" → Vorschau-Tabelle (public + nicht-Denylist vorangehakt; Taxonomien per
    object_type-Schnitt **inkl. non-public** `herkunft`; „bereits im Store"-Marker) → „Importieren"
    schreibt den Store + `flush_rewrite_rules()` (Admin-Kontext, einmalig) → Erfolgs-Notice.
  - **Recovery-Button „Seed-Pack wiederherstellen"** (§ 4.4) — Restore der gefrorenen alkipedia-
    Definitionen, wenn ACF schon weg + Store verloren.
  - **Post-Import-Wegweiser (Punkt 2, kein Auto-Coupling):** „Typ registriert. Für Plugin-Features
    (Favoriten/Schema …) im **Core-Tab → Unterstützte Post-Types** aktivieren."

## 8. Lifecycle

- **Laden:** `ModuleManager::init()` lädt `module.php` nur wenn aktiv (Kanon); Konstruktor verdrahtet
  Hooks (§ 1.1).
- **`init` (Provider):** Store lesen → `depeur_food/content_types/{post_types,taxonomies}`-Filter →
  **post types zuerst, dann taxonomien** (je Skip-Guard `*_exists`) → Action
  `depeur_food/content_types/registered`. **Leerer Store = No-Op** (Invariante § 3.3).
- **Rewrite-Rules:** `flush_rewrite_rules()` **nur** bei Store-Write (Import) + Modul-Aktivierung/
  -Deaktivierung — **nicht** auf `init` (zu teuer). Der Toggle läuft über das Core-Tab-System (kein
  klassischer Plugin-Activation-Hook) → leichtes `init`-once-Transient-Flag (`needs_flush`) statt
  Core-Erweiterung. Ohne Flush: 404 trotz registriertem Typ.
- **Aktivierung:** keine Routine, die etwas registriert (Invariante). Aktivierung macht nur den Provider
  lauffähig; leerer Store → nichts passiert (sicher bei frischer Installation).
- **Deaktivierung:** Provider läuft nicht mehr → Typen weg (außer ACF/Fremd liefert sie) + Rewrite-Flush.
  **Posts bleiben** in `wp_posts` (verwaist, sicher); Orphan-Detektor (§ 3.4) warnt.
- **Uninstall:** `uninstall.php` (Task 1, LIKE-Purge `depeur_food_%`) räumt die Option. **Posts NICHT
  gelöscht** (Content-Daten).

**Deployment-Checkliste (§ 3.8, erweitert — kompakt, kein separates Runbook):**
1. Modul aktivieren (Store leer → No-Op).
2. Importer → Live-Scan → CPTs/Taxonomien wählen → Import (Store befüllt; Provider **ruht** noch per
   Skip-Guard, ACF bedient weiter).
3. Verifikation: Diagnose-Tab zeigt Store; Frontend/Archive unverändert (ACF).
4. **Re-Import unmittelbar vor ACF-Abschaltung** (Sync etwaiger ACF-Arg-Änderungen seit Schritt 2 →
   Drift-Fenster ≈ 0).
5. ACF-CPT-/Taxonomie-**Definitionen** deaktivieren (ACF-UI; ACF-Plugin bleibt aktiv).
6. Modul übernimmt (`*_exists`=false → Provider registriert aus Store; Rewrite-Flush lief bei Import).
7. **Re-Verifikation:** Archive (`/cocktails/`), Single-Permalinks, Term-Archive (`/anlass/<term>/`),
   Editor, REST (`/wp-json/wp/v2/cocktails`), Post-Counts unverändert; Orphan-Detektor still.

## 9. Edge-Cases
- **9.1 Leerer/abwesender Store** → No-Op (Invariante § 3.3), explizit getestet (§ 12).
- **9.2 Store korrupt/verloren + ACF schon aus** → Orphan-Detektor (§ 3.4): erkennt publizierte Posts
  nicht-registrierter Typen, `admin_notices` mit Re-Import-Link, Recovery via Seed-Pack. **Das
  Albtraum-Szenario (217 cocktails still 404) ist genau hier abgefangen.**
- **9.3 Koexistenz/Skip-Guard:** ACF + Modul aktiv → ACF gewinnt, Modul ruht, keine Doppel-Registrierung.
  Nach ACF-aus übernimmt Modul.
- **9.4 Fremd-Plugin-Pathologie:** anderes Plugin registriert denselben Slug mit **abweichenden** Args →
  Modul ruht (Skip-Guard), stille Divergenz möglich. Diagnose-Tab zeigt „Quelle: Fremd". **Doku-Notiz:**
  im Konfliktfall das Fremd-Plugin prüfen.
- **9.5 Args-Drift ACF→Store (Deployment-Fenster)** → durch Re-Import (§ 8 Schritt 4) geschlossen;
  bewusst **kein** Drift-Detektor-Code (schmales Einmalfenster).
- **9.6 Rewrite-Rules nicht geflusht** → 404 trotz Registrierung; Mitigation § 8 (Flush bei
  Import/Akt/Deakt).
- **9.7 Taxonomie vor CPT / Reihenfolge** → post types zuerst; Taxonomie für (noch) fehlenden CPT bleibt
  graceful (greift, sobald CPT da), kein Fatal.
- **9.8 `herkunft` non-public** → Scan per object_type-Schnitt statt public-Filter (§ 4.5) verhindert
  stillen Verlust.
- **9.9 Orphan-Transient-Invalidierung** → bei Store-Write **und** Modul/Plugin-Akt/Deakt invalidieren
  (sonst veraltete Warnung). Code-Detail.
- **9.10 autoload-Größe (Future-Awareness)** → bei sehr vielen CPTs/Taxonomien künftig autoload=no +
  gezieltes Laden neu bewerten; heute (3+3) unkritisch.
- **9.11 Graceful Degradation (§ 1.1):** unvollständiger Store-Eintrag → übersprungen + admin-sichtbar im
  Diagnose-Tab geloggt (nicht im Hot-Path), kein Fatal/Notice.
- **9.12 Builtin-/Internal-Schutz:** Importer-Denylist (`acf-*`/`kadence_*`/`wprm_*`/`rm_*`/`kb_icon`) +
  `_builtin`-Ausschluss → post/page/attachment nie importierbar; Plugin-eigene Typen nicht re-importiert.
- **9.13 Ehrliche Grenze (deaktiviertes Modul):** bei *deaktiviertem* Modul läuft auch der Orphan-Detektor
  nicht — das Abschalten des Moduls, das die Typen liefert, ist eine bewusste Operator-Aktion (wie das
  Deaktivieren irgendeines CPT-liefernden Plugins) und liegt naturgemäß außerhalb der Detektor-Reichweite.
  Der Detektor deckt den *Store-Verlust-bei-aktivem-Modul*-Fall (§ 9.2).

## 10. Anti-Patterns (nicht tun)
- **KEINE Auto-Registrierung bei Installation / kein Default-Seed in der Option** (Invariante § 3.3) —
  die zentrale „trau-mich-nicht"-Schutzlinie.
- **KEINE hardcoded CPT-Liste** im Provider — alles aus dem Store (generisch, ADR-4-treu).
- **KEINE Cross-Module-Direktimporte** (Splitting): Verbindung zum Core-Resolver läuft über WP
  (`get_post_types()`), **nie** Klassen-Import; Konsumenten lesen registrierte Typen via WP-API.
- **KEINE ACF-Runtime-Dependency für die Registrierung** — ACF nur einmalige Import-Quelle (§ 3.5).
- **KEINE `PostType…`/`…Registry`-Tokens im Modul** (Naming-Divergenz § 3.1).
- **KEINE Doppel-Registrierung** — Skip-Guard `*_exists` (§ 4.5).
- **KEINE Uniform-Default-Args** — args-treu 1:1 (§ 3.7), `herkunft` non-public bleibt non-public.
- **KEINE Callbacks/Closures im Store** (kuratierte Allowlist, § 7.3) → Option serialisierbar.
- **KEIN `flush_rewrite_rules` auf jedem `init`** (Performance) — nur Import/Akt/Deakt.
- **KEINE Daten-Migration / kein Löschen in `wp_posts`** — Posts unangetastet.
- **KEIN Auto-Coupling Import → `depeur_food_supported_post_types`** (Entkopplung § 3.6, nur Wegweiser).
- **KEIN eigenes Bootstrapping** außerhalb `module.php` (ModuleManager = einziger Einstieg).
- **KEIN Logging im Hot-Path** (debug.log sauber halten).

## 11. Dependencies
- **ACF free = nur einmalige Import-Quelle**, **kein** Runtime-Dependency für die Registrierung
  (Gegensatz zu meta-registry, das ACF als Editor-UI behält — § 3.5). **Präzisierung:** „ACF
  deaktivieren" (§ 3.8) heißt die ACF-**CPT-/Taxonomie-Definitionen** abschalten, **nicht** das
  ACF-Plugin — das bleibt aktiv (Core-ACF-Hard-Dependency aus P2 `704d3c6` + meta-registry brauchen es).
  `content-types` läuft nach Import strukturell ACF-frei.
- **Core `html`-Feldtyp** (`3b83b4d`) — für die Diagnose-Tabelle. **Bereits vorhanden, kein neuer
  Core-Task.**
- **Core `PostTypeRegistry`** (ADR-4) — lose über WP gekoppelt (sieht das Registrierungs-Ergebnis),
  kein Import.
- **Keine Drittplugin-Deps** für die Registrierung (CPT UI nicht nötig — der Importer scannt generisch
  live-registrierte Typen, quellenunabhängig).
- **WP ≥ 6.5** (Plugin-Minimum) deckt `register_post_type`/`register_taxonomy`/`show_in_rest`.
- **⭐ Bemerkenswert: KEINE Core-Datei-Änderungen nötig** (anders als P2). Reines Modul.

## 12. Smoke-Test
**Standard:** `php -l` clean · phpcs Exit 0 · `wp plugin check` 0 neue Findings · debug.log frei ·
PHP 8.2 (+ 8.4-Remote-Deprecation-Check bei Remote). `wp_posts` Pre/Post **identisch** (Store-Änderung
beim Import ist erwartet — kein Daten-Eingriff).

**Aktiv** (`depeur_food_modules` enthält `content-types`):
- **Invariante (§ 3.3) — kritisch:** Modul aktiv + Store leer → registriert **NICHTS**; frische
  Installation legt keine fremden CPTs an.
- **Import:** Scan listet cocktails/bar-equipment/trinkspiele (public, vorangehakt) + anlass/art/
  **herkunft** (object_type-Schnitt, trotz non-public dabei); Denylist (wprm_*/kadence_*/acf-*)
  ausgeschlossen. Store korrekt geschrieben, Flush lief.
- **Args-Treue:** nach Import + ACF-Defs-aus → `get_post_type_object('cocktails')` aus Store:
  hierarchical=true, has_archive='cocktails', rewrite.slug, supports-Set, menu_position=3;
  `get_taxonomy('herkunft')->public===false`. Vergleich gegen Live-Dump (§ 2).
- **Skip-Guard:** ACF an + Store befüllt → `cocktails` genau **einmal** registriert (kein
  `_doing_it_wrong`); Diagnose „Quelle: ACF, Skip-Guard ruht".
- **Koexistenz→Übernahme:** ACF-Defs aus → Modul übernimmt (Quelle: Store); Posts/URLs/REST unverändert.
- **Orphan-Detektor (§ 3.4):** Store leeren bei publizierten cocktails + ACF aus → `admin_notice` +
  Re-Import-Link; Seed-Restore stellt wieder her.
- **URLs/REST:** `/cocktails/` Archive, Single-Permalink, `/anlass/<term>/` → 200; `/wp-json/wp/v2/cocktails`
  erreichbar (show_in_rest erhalten).
- **Core-Entkopplung (§ 3.6):** cocktails erscheint als Kandidat in `get_available()`;
  `depeur_food_supported_post_types` **nicht** automatisch verändert.
- **Diagnose-Tab** rendert read-only + korrekte Counts/Quelle/Skip-Guard. **Importer-Security:** ohne
  Nonce/Cap → reject; Slug nicht in Scan-Kandidaten → Whitelist-reject.

**Inaktiv** (`depeur_food_modules` leer): Provider läuft nicht; bei aktivem ACF bleiben die CPTs (ACF),
bei ACF-aus verschwinden sie + Posts verwaisen. Orphan-Detektor-Grenze: § 9.13 (deaktiviertes Modul →
Detektor läuft nicht; bewusste Operator-Aktion).

**ACF-Dependency (Core, aus P2):** unverändert — das ACF-Plugin muss aktiv bleiben; `content-types`
schaltet nur die ACF-*CPT-Definitionen* ab, nicht das Plugin (§ 11).

## 13. Datei-Liste (anzulegen)
**Modul** (`plugins/depeur-food/modules/content-types/`, Kanon: kebab-Root, PascalCase-Subordner):
- `manifest.php` (~20) — name/version/description.
- `module.php` (~28) — direkte Multi-Instanziierung: `Registration\Type_Provider` +
  `Registration\Taxonomy_Provider` (immer); `Registration\Orphan_Detector` + `Admin\Settings` (nur
  `is_admin`). Kein Root-Wrapper (FS-Safety § 2.7).
- `config/seed.php` (~120) — gefrorenes Seed-Pack (3 CPTs + 3 Taxonomien, aus § 2-Live-Dump). Daten,
  `require`-geladen → lowercase-Ordner zulässig.
- `Definitions/Store.php` (~100) — Option read/write, Entry-Sanitize/Normalisierung, Seed-Restore.
  Einziger Options-Zugriffspunkt.
- `Definitions/Importer.php` (~130) — Live-Scan, Args-Normalisierung (kuratierte Allowlist,
  Callback-Strip), Kandidaten-Vorschau, Schreiben gewählter Defs.
- `Registration/Type_Provider.php` (~90) — `init`, Store→`register_post_type` (Skip-Guard), Flush-Flag.
- `Registration/Taxonomy_Provider.php` (~70) — `init`, Store→`register_taxonomy` (Skip-Guard, object_type).
- `Registration/Orphan_Detector.php` (~80) — `admin_notices`-Scan, Transient-Cache (§ 9.9-Invalidierung).
- `Admin/Settings.php` (~140) — SettingsRegistry-Anmeldung (Intro + read-only `html`-Diagnose),
  Importer-Sektion (Render + cap→nonce→sanitize→process-Handler), Post-Import-Wegweiser.
- `BRIEF.md` (dieser).

**Core:** **keine Änderungen** (§ 11). **LOC gesamt ~870–1.000 · Sessions: 2–3**
(config/seed → Store → Provider → Importer/Detector → Admin → Smoke).
