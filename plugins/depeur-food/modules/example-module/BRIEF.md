# BRIEF.md — `example-module` (Modul-Architektur-Vorlage)

**BRIEF-Schema-Version: 1.0** — Format dieses Modul-Briefs. Bei späteren Struktur-Änderungen am Brief-Schema (neue Pflichtsektionen o. Ä.) auf 1.1 etc. bumpen, damit in 6 Monaten erkennbar ist, nach welchem Schema ein Modul-Brief verfasst wurde.

> **Freiwilliger Mini-BRIEF.** § 12.1 exemptiert Tasks 1–3, dieser Brief friert dennoch die Modul-Konventionen ein, weil `example-module` die **kopierte Vorlage für ALLE künftigen Module** ist (`cache-bridge` Task 5 ff.). Kanonische Quelle der Modul-Mechanik — spätere Modul-Briefs verlinken hierher.

## 1. Zweck
Triviales Referenz-/Template-Modul. Validiert **ModuleManager-Discovery + Lazy-Load + SettingsRegistry-Anmeldung** end-to-end. **Kein** Feature-Wert — reine Mechanik-Vorlage.

## 2. Eingefrorene Architektur-Entscheidungen

### 2.1 Naming & Layout — Option Y (Autoloader-aligned)
- **Modul-Root-Ordner = Slug = kebab-case:** `modules/example-module/`.
- **Ab Modul-Root: PascalCase wie PSR-4** — Klassen-Files PascalCase, Unterordner PascalCase (`Admin/`, `Frontend/`) passend zum Namespace. Die kebab-Schreibung gilt **nur** für den Root-Ordner; alles darunter ist PascalCase.
- **Namespace:** `Depeur\Food\Modules\ExampleModule\…`. Der Task-1-Autoloader mappt den PascalCase-Modulnamen → kebab-Root (`ExampleModule` → `example-module`, Autoloader Z. 68–82).
- **Warum Option Y statt Suite-`_ExampleModule`:** Task 1 hat einen kebab-Case-Modul-Autoloader gebaut — ihn nicht zu nutzen würde ihn zu **totem Code** machen und jedes Modul in Hand-Require-Boilerplate zwingen (Footgun: Reihenfolge-Abhängigkeiten, vergessene requires). Suite-Treue ist hier kein Wert an sich (andere Autoloader-Realität, plugin-spezifische Entscheidung). Der `_`-„steht-oben-im-Dateibrowser"-Vorteil ist Folklore — wer die Vorlage sucht, liest diesen BRIEF, nicht den Dateibrowser.

### 2.2 Pflicht-Files pro Modul
| File | Pflicht | Rolle |
|---|---|---|
| `manifest.php` (Root) | ja | Metadaten; gibt ein Array zurück (kein Code-Effekt). |
| `module.php` (Root) | ja | Bootstrap; von ModuleManager `require_once`'t — **nur wenn Modul aktiv**. Instanziiert die Bootstrap-Klasse. |
| `<Subordner>/<Pascal>.php` | ja | Modul-Klasse(n) in PascalCase-Subordner (z. B. `Admin/Settings.php`), via Autoloader geladen. **Nie** als Klasse am Modul-Root (§ 2.7). |

### 2.3 manifest.php-Format
Gibt ein assoziatives Array zurück.
- `name` (string, **Pflicht**) — Anzeigename.
- `version` (string, **Pflicht**).
- `description` (string, empfohlen).

### 2.4 Warum KEIN slug-Key im manifest?
Das manifest enthält **BEWUSST keinen `slug`-Key.** Discovery keyt allein nach **Ordnername**. Ein `slug`-Key im manifest würde eine **zweite Quelle der Wahrheit** erzeugen — wenn jemand den Ordner umbenennt, aber den slug stehen lässt (oder umgekehrt), reagiert der Code je nach Lese-Pfad inkonsistent. **Wenn du dich versucht fühlst, einen `slug`-Key zur „Robustheit" hinzuzufügen: STOPP.** Das erhöht Komplexität ohne realen Robustheits-Gewinn.

### 2.5 Anmeldung bei SettingsRegistry
- **Wo:** im **Konstruktor** der Bootstrap-Klasse (Hook-/Settings-Wiring gehört laut wordpress.md § 1.1 in den Konstruktor).
- **Wie:** `SettingsRegistry::register( $slug, $tab_label, $fields, $description )`.
- **Wann:** während des `init`-Hooks (ModuleManager::init lädt module.php dort). `init` < `admin_init` → vor jedem späteren Settings-Render.
- **Slug-Quelle:** Der Slug wird der Klasse per **Konstruktor-Argument aus `module.php`** hereingereicht: `new \Depeur\Food\Modules\ExampleModule\Admin\Settings( basename( __DIR__ ) )`. **Nicht** via `basename( __DIR__ )` in der Klasse selbst — die liegt in `Admin/`, dort wäre `basename` = `"Admin"`. `module.php` liegt garantiert im Modul-Root, also liefert sein `basename( __DIR__ )` den korrekten Slug (= Ordnername). Single Source of Truth, kein hartkodierter Literal.
- **Kanonische Doku:** wordpress.md § 1.1 (Multi-Option-Pattern) und ADR-1 (PLAN.md § 4).

### 2.6 Hook-Wiring
Im **Konstruktor** der Bootstrap-Klasse (`add_action`/`add_filter`, Callback als `array( $this, 'method' )` — hält die Instanz am Leben). `module.php` enthält KEINE Logik außer der Instanziierung.

### 2.7 FS-Safety-Konvention (case-insensitive Dateisysteme)
**Pflicht: Modul-Root enthält NUR Lowercase-Reserved-Files** (`manifest.php` + `module.php`). Alle Klassen-Files liegen in PascalCase-Subordnern.

**Warum:** Auf case-insensitive Dateisystemen (macOS APFS, Windows NTFS) würden `module.php` (Bootstrap, lowercase) und `Module.php` (Klasse, PascalCase) zu **derselben Datei** kollabieren. Linux ist case-sensitive und würde sie trennen — Dev-on-Mac vs. Deploy-on-Linux hätten verschiedene Realitäten, git würde inkonsistent tracken.

**Lösung durch Struktur, nicht Disziplin:** Keine `*.php`-Klassen am Modul-Root. Punkt. Klassen leben in `Admin/`, `Frontend/`, `Providers/`, `REST/` — Subordner, die mit PascalCase-Files niemals mit den Lowercase-Reserved-Files am Root kollidieren können. *(Surfaced beim Bau des `_Example`: ein flaches `Module.php` ließ sich auf dem macOS-Dev-Rechner physisch nicht neben `module.php` anlegen.)*

## 3. Subfolder-Konvention (DEMONSTRIERT im `_Example`)
Modul-Klassen liegen in PascalCase-Subordnern (zwingend, § 2.7). Das `_Example` zeigt das Muster selbst mit `Admin/Settings.php`. Sobald ein Modul mehrere Klassen-Gruppen hat (Admin-UI, Frontend-Rendering, REST-Endpoints), kommen weitere Subordner dazu:

```
modules/cache-bridge/
├── manifest.php
├── module.php
├── Admin/
│   └── Settings.php       → \Depeur\Food\Modules\CacheBridge\Admin\Settings
└── Providers/
    └── Cloudflare.php     → \Depeur\Food\Modules\CacheBridge\Providers\Cloudflare
```

Der Autoloader (`src/Helpers/Autoloader.php`) löst diese Pfade automatisch via PSR-4 auf — **kein Hand-Require nötig**. Merksatz: kebab nur am Modul-Root, ab dort PascalCase wie der Namespace.

## 4. Lifecycle
- **Laden:** `ModuleManager::init()` (am `init`-Hook) liest `depeur_food_modules`, schneidet gegen Discovery, `require_once`'t `module.php` der aktiven Module.
- **Aktivieren/Deaktivieren:** = Slug in `depeur_food_modules` aufnehmen/entfernen. **Keine** eigenen Modul-Aktivierungs-Hooks heute (UI dafür → Tab-System-Task, § 7). „Geladen" ⟺ „aktiv": ModuleManager lädt module.php NUR für aktive Module, das Modul prüft die Master-Liste nicht selbst nach.
- **Uninstall:** `uninstall.php` purged `depeur_food_%` per LIKE (Task 1) → Modul-Option `depeur_food_example-module` wird automatisch mit-aufgeräumt. Module mit **anderem** Storage (Custom Tables, Post-Meta) müssen eigene Cleanup-Routine registrieren — heute n/a.

## 5. Demo-Funktionalität (maximal trivial)
- **Ein** Checkbox-Setting „Beispiel aktiv" via SettingsRegistry (kein UI-Render heute → § 7).
- **Ein** demonstrativer Filter `depeur_food/example/greeting` (Callback `array( $this, 'filter_greeting' )`, gibt einen Demo-String zurück) — zeigt Hook-Wiring, CLI-verifizierbar, **keine** Seiteneffekte. Name bewusst neutral: NICHT `active` (das wäre Wert-Verdopplung zur Modul-Aktivierungs-Mechanik in `depeur_food_modules`).
- Settings-Anmeldung UND Demo-Hook leben beide in `Admin/Settings`. Der Demo-Filter ist nicht admin-spezifisch; für ein 1-Filter-Demo ist eine eigene `Frontend/Hooks.php` Über-Engineering. **Künftige Module mit echter Nicht-Admin-Logik legen dafür separate Subordner an** (`Frontend/`, `REST/` …).
- Observables „geladen"-Signal: Schema in `SettingsRegistry::get_all_schemas()` **und** `class_exists()`.

## 6. Anti-Patterns (nicht tun)
- **KEIN Logging** im Demo-Hook (bräche die „debug.log frei"-Smoke).
- **KEIN eigenes Bootstrapping** außerhalb `module.php` (kein `plugins_loaded`/eigener Früh-Hook im Modul — der ModuleManager ist der einzige Einstieg).
- **KEINE Cross-Module-Dependencies in der Bootstrap-Phase** (Module dürfen sich beim Laden nicht gegenseitig voraussetzen; Interaktion frühestens nach `init`).
- **KEIN `*.php` am Modul-Root** außer `manifest.php`/`module.php` (FS-Safety, § 2.7).

## 7. Bewusst NICHT im Scope von Task 3
- Modul-Settings-**Render** im Admin-UI → **Tab-System-Task**.
- **Toggle-Checkbox** (Modul an/aus) in der SettingsPage → selbe Tab-System-Task.
- Cache-Bridge-Integration → Task 5.
- Frontend-Hooks → modul-spezifisch je Feature.

**Tab-System ist Voraussetzung für `cache-bridge`:** Das nächste echte Modul braucht eine UI für seine Settings — ohne Tab-System keine Modul-Settings-UI. Die Tab-System-Task **muss zwischen Task 3 und dem cache-bridge-Modul implementiert werden** (Konsequenz: alter Task 4 `cache-bridge` → Task 5, neuer Task 4 = Tab-System). Sprint-Liste in CLAUDE.md ist entsprechend anzupassen (Open Item beim Session-End-Handoff).

## 8. Datei-Liste (anzulegen/ändern)
- `modules/example-module/manifest.php` (neu, ~20 LOC)
- `modules/example-module/module.php` (neu, ~20 LOC) — `new …\Admin\Settings( basename( __DIR__ ) )`.
- `modules/example-module/Admin/Settings.php` (neu, ~60 LOC) — Bootstrap-Klasse: Konstruktor meldet Settings an + verdrahtet Demo-Hook.
- *(Minor)* `src/Core/Settings/SettingsRegistry.php` — Docblock-Beispiel `_ExampleModule` → `example-module` angleichen + Verweis auf diesen BRIEF (Option-Y-Konvention).

## 9. Smoke (CLI/Option, kein UI)
- **Aktiv** (`depeur_food_modules=["example-module"]`): `get_all_schemas()` enthält `example-module` + `class_exists( \Depeur\Food\Modules\ExampleModule\Admin\Settings )` true + `apply_filters('depeur_food/example/greeting', '')` liefert den Demo-String.
- **Inaktiv** (`[]`): Schema weg, Klasse nicht geladen, Filter liefert den unveränderten Default (`''`).
- **FS-Safety (§ 2.7):** `ls modules/example-module/*.php` zeigt **exakt** `manifest.php` + `module.php` (keine Klasse am Root); `ls modules/example-module/Admin/*.php` zeigt `Settings.php`.
- **Autoloader-only (§ 2.1):** `module.php` enthält KEIN `require_once`/`include` für Klassen-Files (höchstens den ABSPATH-Check). `class_exists( …\Admin\Settings, false )` ist VOR dem Modul-Load `false` (autoload-Param aus); nach dem `new …` in module.php findet der PSR-4-Autoloader die Klasse. Macht den Option-Y-Vertrag („Load via Autoloader, kein Hand-Require") explizit nachprüfbar — sonst verkommt der Autoloader wie bei der Suite zu totem Code.
- phpcs Exit 0 · `php -l` · debug.log frei · `wp plugin check` 0 neue Findings.
- **Bricht die „keine ModuleManager-Änderung nötig"-Annahme → STOPP + melden, kein Drive-by-Fix.**
