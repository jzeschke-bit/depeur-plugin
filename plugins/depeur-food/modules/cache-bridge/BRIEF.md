# BRIEF.md — `cache-bridge` (Hook-First Cache-Purge-Fassade)

**BRIEF-Schema-Version: 1.1** — Erster Volltext-Modul-Brief nach dem § 12.2-Pflichtkanon
(7 Pflichtsektionen) mit geschäftslogik-tragenden Vertiefungs-Sektionen über die reine
Modul-Mechanik hinaus. Schema-Bump 1.0 → 1.1 gegenüber `example-module/BRIEF.md`:
Das _example-Schema (1.0) deckte nur die Modul-Vorlage-Mechanik ab (Naming, Lifecycle,
Discovery); dieser Brief fügt geschäftslogik-Sektionen hinzu (Provider-Interface,
Transport/Retry, Listener, Debounce, Pause, Edge-Cases, Smoke). Künftige
geschäftslogik-Module erben Schema 1.1.

> **§ 12.2-Pflicht-Brief (geschäftslogik-tragend).** Anders als der freiwillige
> `example-module`-Brief ist dieser nach wordpress.md § 12.3 **vor jedem Code zu
> schreiben und freizugeben**. Er ist lebende Doku (§ 12.4) — bei Phase-2-Änderungen
> am Modul wird er mit-aktualisiert. Kanonische Modul-Mechanik (Naming, FS-Safety,
> Discovery, Autoloader): siehe `modules/example-module/BRIEF.md` — hier **nicht**
> wiederholt, nur die cache-bridge-spezifische Architektur.

## 1. Meta / Einordnung

- **Plugin-Cluster:** **Depeur Speed** (Plugin-Splitting-Strategie, CLAUDE.md ›
  Architecture Notes). `cache-bridge` gehört semantisch nicht zu Depeur Food, sondern
  zur künftigen Performance-/Edge-Sparte. **Konsequenz, die hier schon bindet:** keine
  Cross-Module-Direktimporte, keine Suite-Referenz — sonst hätte Depeur Speed beim
  Split eine Abhängigkeit zu Food bzw. zur Suite (ADR-3-Supersede, PLAN.md § 4).
- **Default-Status:** **Default-ON-Modul.** Cache-Purge ist Infrastruktur, kein
  optionales Feature — ohne aktive Bridge würde stale Content auf Origin/CDN das
  Standard-Verhalten. (Aktivierung trotzdem via `depeur_food_modules`; „Default-ON"
  heißt: beim Onboarding aktiv vorgeschlagen, nicht hart erzwungen.)
- **Brief-Verwandtschaft:** baut auf `example-module/BRIEF.md` v1.0 (Modul-Kanon) auf;
  erweitert dessen Struktur, ersetzt sie nicht.
- **Status:** Volltext-BRIEF zur Freigabe (Session 2026-06-12c). Nach Approval →
  Code-Phase (Reihenfolge: shared Value-Objects + Transport → Provider-Interface →
  Listener/Purge_Runner/Skelett → vier Provider → Pause-Trio → Settings/Smoke), verteilt
  über mehrere Code-Sessions (5–6, s. § 18).

## 2. Zweck & Funktionalitäts-Inventar

**Zweck:** Eine **Hook-First-Fassade**, die bei Content-Änderungen die richtigen Caches
invalidiert — auf dem **Origin** (lokaler Server-Cache) **und** an der **Edge** (CDN).
Konsumenten feuern eine Action; das Modul übersetzt das in provider-spezifische
Purge-Calls. Das Modul **purgt** — es **generiert/cached selbst nichts** (kein
Page-Cache, kein Object-Cache; nur Invalidierung bestehender Caches Dritter).

> **Ausnahme zur „cached nichts"-Abgrenzung:** Die Pause-Queue (§ 8) persistiert
> temporär URLs für späteren Purge — das ist **kein Content-Cache**, sondern
> **Operations-State** der Pause-Mechanik. (Hinweis, damit „kein Storage außer Purge"
> nicht als Verbot der Queue-Persistierung fehlgelesen wird.)

**Funktionalitäts-Inventar:**
- **Listener** auf Content-Mutations-Hooks (`save_post`, `transition_post_status`,
  `wprm_save_recipe`), baut daraus ein `Purge_Context`-Value-Object und feuert die
  zentrale Purge-Action.
- **Debounce** (30 s) im Listener vor dem `do_action`, verhindert technische
  Doppel-Trigger.
- **Provider-Iteration** (`Purge_Runner`): vier eigenständige Provider (LogOnly,
  RunCloud, BunnyCDN, Cloudflare) in konfigurierbarer Reihenfolge, mit Failure-Isolation.
- **Pause-Mechanismus** (Variante C): globaler Toggle, URL-Queue, Admin-Bar-Anzeige,
  Resume-Modal, 24 h-Auto-Resume.
- **Settings-UI:** pro Provider Enable + Credentials + Reihenfolge; Pause-Toggle.
- **Public API:** Action `depeur_food/cache/purge` + Filter (`register_providers`,
  `register_listeners`, `purge_urls`) + Timeout-/TTL-/Multiplikator-Filter.

**Keine Legacy-Migrations-Quelle (Abweichung von § 12.2 #1):** cache-bridge ist ein
**Neu-Feature**, kein Port eines Legacy-Plugins. § 12.2 #1 nennt
`_references/legacy-plugins/` als Quelle — die gibt es hier nicht. Recon-Quellen waren
nur **Inspiration**, ausdrücklich **kein** Migrationsziel und **kein** `class_exists`-Bridge:
- Suite-`BunnyApi` / `PurgeService` — API-Aufruf-Muster (Endpoints, Header), NICHT als
  Dependency (Splitting-Strategie, § 1).
- Vendor `bunnycdn` / `runcloud-hub` / `wp-rocket` — API-Pattern-Referenz (Auth,
  Purge-Endpoints, RunCloud-Nginx-PURGE-Semantik).

## 3. Eingefrorene Architektur-Entscheidungen (high-level)

Detail je Entscheidung in den referenzierten Sektionen; hier die festgeschriebenen
Leitplanken.

- **3.1 Hook-First-Entkopplung.** Konsumenten rufen NIE einen Provider direkt, sondern
  `do_action( 'depeur_food/cache/purge', $context )`. Das Modul ist der einzige
  Provider-Kenner. Begründung: Konsumenten (Schema-Engine, Newsletter, Theme) bleiben
  CDN-agnostisch; Provider-Wechsel berührt keinen Konsumenten. (Detail § 9.)

- **3.2 Vier shared Klassen in `src/Cache/`** (außerhalb des Moduls, weil sie der
  öffentliche Vertrag bzw. cross-cutting sind und beim Plugin-Split mit dem Shared-Core
  wandern): `Purge_Context` (Eingabe), `Purge_Result` (Provider-Rückgabe, § 4),
  `Provider_Catalog` (statische Provider-Metadaten für die Settings-UI, § 4) und
  `Transport` (cross-cutting HTTP-Executor, § 5). Die drei Value-Objects `final readonly`.
  **Naming-Konvention** (bindend für ALLE cache-bridge-Klassen): `Word_Word`
  PascalCase-mit-Unterstrich, konsistent zum bestehenden `Purge_Context`. Gilt für
  Value-Objects, Provider-Klassen (`Provider_Cloudflare` …) und das Interface
  (`Provider_Interface`). **Konsistenz schlägt PSR-4-Idiom in diesem Projekt.**

- **3.3 Provider-Pattern mit FQCN-Registrierung.** Provider werden als
  **Klassen-Namen-Strings** (FQCN) registriert, nicht als Instanzen — das erlaubt
  statischen Catalog-Zugriff im Admin (`$fqcn::get_catalog()`) ohne Instanziierung
  UND lazy Instanziierung im Purge-Pfad (§ 12). Registrierungs-Filter
  `depeur_food/cache/register_providers` (Signatur § 4.5/§ 9). Execution in
  **registrierter Reihenfolge**; **Failure-Isolation**: ein Provider-Fehler bricht die
  Kette nicht (Detail § 5.4).

- **3.4 Provider-Default-Reihenfolge: `LogOnly → RunCloud → BunnyCDN → Cloudflare`.**
  Die Reihenfolge ist **semantisch, nicht kosmetisch** — *Origin vor Edge*:
  **Edge fragt Origin. Wenn die Edge zuerst gepurgt wird, während der Origin noch warm
  ist, re-cached die Edge sofort wieder die alte Antwort.** Also Origin-Purge (RunCloud,
  lokaler Nginx) zuerst, danach die CDN-Edges. `LogOnly` ganz vorn als
  Audit-Trail-Marker. Der User kann die Reihenfolge in den Settings überschreiben
  (§ 11), aber der Default muss Origin-first sein.

- **3.5 `Provider_LogOnly` ist always-on** (kein I/O, kein Creds, nie unavailable) —
  garantiert, dass jeder Purge mindestens einen Audit-Log-Eintrag erzeugt, auch wenn
  kein CDN konfiguriert ist.

- **3.6 Lazy Provider-Instanziierung** (Frontend-Footprint = null; Detail § 12). Nur
  Listener, Purge_Runner + Pause-Controller werden bei `init` gebaut, Provider erst bei
  Purge.

- **3.7 FS-Safety (example-module § 2.7):** alle Klassen in PascalCase-Subordnern
  (`Hooks/`, `Providers/`, `Pause/`, `Admin/`), Modul-Root nur `manifest.php` +
  `module.php`. Detail/Datei-Liste § 18.

## 4. Provider-Interface & -Katalog

### 4.1 Interface

```php
namespace Depeur\Food\Modules\CacheBridge\Providers;

use Depeur\Food\Cache\Purge_Context;
use Depeur\Food\Cache\Purge_Result;
use Depeur\Food\Cache\Provider_Catalog;

interface Provider_Interface {
    /** Ist der Provider einsatzbereit? Semantik provider-spezifisch (§ 4.4). */
    public function is_available(): bool;

    /** Führt den Purge aus. Wirft NIE — Fehler werden im Result codiert (§ 5). */
    public function purge( Purge_Context $context ): Purge_Result;

    /** Max. Einzel-URL-Count vor Bulk-Eskalation (§ 13). Provider-spezifisch. */
    public function get_bulk_threshold(): int;

    /** Statische Metadaten für die Settings-UI — OHNE Instanziierung (§ 12). */
    public static function get_catalog(): Provider_Catalog;
}
```

`is_available()` und `purge()` sind **Instanz**-Methoden (brauchen injizierte Config);
`get_catalog()` ist **statisch** (reine Metadaten, vom Admin via `$fqcn::get_catalog()`
ohne `new` gerufen — Lazy-Mechanik § 12).

### 4.2 `Purge_Result` (shared Value-Object, `src/Cache/`)

```php
namespace Depeur\Food\Cache;

final readonly class Purge_Result {
    public function __construct(
        public bool   $success,      // semantische Interpretation von $code
        public int    $code,         // HTTP-Status UNGEFILTERT; 0 = nie erreicht
        public string $message,
        public string $provider_id,
        public int    $duration_ms,  // microtime-Differenz im Provider gemessen
    ) {}
}
```

**Konvention `code` / `success` (diagnostische Dreiteilung — bindend):**
- `code` = **tatsächlicher HTTP-Status, ungefiltert** (auch 4xx/5xx werden durchgereicht).
- `code = 0` ist **reserviert** für `WP_Error` / Network-Fail (Request kam nie an).
- `success` = boolean, das den Status **semantisch interpretiert**: `200–299 → true`,
  sonst `false`.
- Damit unterscheidbar: **„erreicht & akzeptiert"** (`code 200, success true`) /
  **„erreicht & abgelehnt"** (`code 403, success false` — z. B. falscher Token) /
  **„nie erreicht"** (`code 0, success false` — Timeout/DNS). Diese Diagnose ist der
  Grund für das Value-Object statt eines nackten bool.

### 4.3 `Provider_Catalog` (shared Value-Object, `src/Cache/`)

```php
namespace Depeur\Food\Cache;

final readonly class Provider_Catalog {
    public function __construct(
        public string $id,                // 'cloudflare'
        public string $label,             // 'Cloudflare (Edge)' — i18n via __()
        public string $description,       // kurze UI-Beschreibung
        public array  $credential_fields, // Field-Specs für Settings-UI (§ 11)
        public bool   $default_enabled,   // LogOnly=true, Rest=false
    ) {}
}
```

`credential_fields` = Array von Field-Specs `array( 'key' => 'api_token', 'label' =>
…, 'type' => 'password'|'text', 'description' => … )`, das die Settings-UI (§ 11) direkt
rendert (Password-Felder mit Preserve-on-empty wie Task 4).

### 4.4 Provider-Katalog (vier Built-ins)

`is_available()`-Semantik ist **provider-spezifisch** (Recon-Fund F1: RunCloud ist
lokaler Nginx-PURGE, NICHT analog zu den CDN-APIs) — explizit pro Provider dokumentiert.

| Provider | `id` | default | Creds | `is_available()` | bulk_threshold |
|---|---|---|---|---|---|
| `Provider_LogOnly` | `log_only` | **true** | — | immer `true` | `PHP_INT_MAX` |
| `Provider_RunCloud` | `runcloud` | false | — | HTTP-Probe + FS-Fallback, 1 h transient-cached | ~200 (s. u.) |
| `Provider_BunnyCDN` | `bunnycdn` | false | `api_key`, `pull_zone_id` | Creds vorhanden | ~30 |
| `Provider_Cloudflare` | `cloudflare` | false | `api_token`, `zone_id` | Creds vorhanden | ~30 |

**`Provider_LogOnly`** — Always-on (§ 3.5). `purge()` schreibt einen redacted
Context-Dump via `error_log` (Audit-Trail), liefert immer `success`. Nie unavailable,
kein I/O, kein Bulk-Eskalations-Risiko.

**`Provider_RunCloud`** — **Origin** (lokaler Nginx-FastCGI-Cache). Keine Creds.
- `is_available()`: **primär HTTP-Probe** (Test-`PURGE` auf eine bekannte URL →
  Response-Header `X-RunCache-Type` vorhanden?), **FS-Fallback** (`/etc/runcloud/`
  existiert). Ergebnis **1 h in Transient** `depeur_food_cache_runcloud_available`
  gecacht (sonst Probe auf jedem Purge). Cache wird bei Settings-Save geleert.
- `purge()`: HTTP-`PURGE`-Methode pro URL gegen den lokalen Nginx.
- **bulk_threshold geschätzt ~200** — **Schätzung, kein hartes externes Limit**
  (RunCloud-PURGE ist lokal); Soft-Guard gegen Hunderte synchroner Local-Requests pro
  Save; **exakter Wert im Smoke/Betrieb zu verifizieren**.
- **Kein atomarer Full-Purge** (Nginx kennt nur per-URL/Wildcard) → die destruktive
  Full-Purge-Eskalation (§ 13) betrifft RunCloud **nicht**; bei Überlauf chunked es
  per-URL weiter oder skippt+loggt.

**`Provider_BunnyCDN`** — **Edge, eigenständig (kein Suite-Bridge).** Base
`https://api.bunny.net`. Header `AccessKey: {api_key}`.
- per-URL: `POST /purge?url={url}` · full: `POST /pullzone/{pull_zone_id}/purgeCache`.
- `is_available()`: `api_key` UND `pull_zone_id` nicht leer.
- per-URL-Endpoint ohne Batch → niedriger Threshold (~30), darüber Bulk-Logik (§ 13).

**`Provider_Cloudflare`** — **Edge.** Base `https://api.cloudflare.com/client/v4`.
Header `Authorization: Bearer {api_token}`.
- `POST /zones/{zone_id}/purge_cache`, Body `purge_everything` | `files[]` (max ~30/Call)
  | `tags[]`.
- `is_available()`: `api_token` UND `zone_id` nicht leer.
- **`tags[]` = Cloudflare-Enterprise-only → capability-gated**: ohne Enterprise
  graceful skip auf `files[]`-Purge (§ 13), kein Fehler.

### 4.5 `register_providers`-Filter (Signatur)

```php
$provider_classes = apply_filters(
    'depeur_food/cache/register_providers',
    array(                              // Default = 4 Built-ins in Default-Order (§ 3.4)
        Provider_LogOnly::class,
        Provider_RunCloud::class,
        Provider_BunnyCDN::class,
        Provider_Cloudflare::class,
    )
);
```

Liefert **FQCN-Strings**, keine Instanzen (§ 3.3). **Pflicht-Validation vor jeder
Instanziierung** (§ 14): `class_exists()` + `is_subclass_of( $fqcn,
Provider_Interface::class )`; Mismatch → skip + `debug.log` mit FQCN + Reason, **kein
Fatal**.

## 5. Transport- & Retry-Policy (cross-cutting)

Die **gesamte § 5-Policy** lebt in der shared Klasse `Transport`
(`src/Cache/Transport.php`, § 18.1) — Single Point of Maintenance. Provider rufen:

```php
Transport::request(
    string $method, string $url, array $headers, ?string $body,
    string $provider_id, Purge_Context $context
): Purge_Result;
```

`$body` nullable (RunCloud-`PURGE` ist body-less). Provider mappen nur Endpoint + Auth;
Timeout/Retry/429/Redaction werden **nicht** in jedem Provider dupliziert.
`Provider_LogOnly` nutzt `Transport` **nicht** (kein HTTP, nur `error_log`).

### 5.1 Transport

- **Ausschließlich WP-HTTP-API** (`wp_remote_request`) — **nie** Guzzle/cURL direkt.
  Warum: WP-verwaltet (Proxy-Config, Filter), und der `pre_http_request`-Filter erlaubt
  deterministisches Mocking im Smoke (§ 17).
- **Timeout: 12 s Default**, überschreibbar per **Filter** (nicht Constant):

```php
$timeout = apply_filters( 'depeur_food/cache/timeout', 12, $provider_id, $context );
```

  Warum Filter statt Constant: Hook-First-Konsistenz (das ganze Modul exponiert
  Architektur via Action/Filter), Test-Schreibbarkeit (im PHPUnit-Test setz-/entfernbar),
  Multisite (per-Site verschiedene Timeouts), kein `wp-config.php`-Zugriff nötig. Drittes
  Arg `$context` zukunfts-offen (Bulk darf anderes Timeout als single `save_post`).

### 5.2 Retry-Matrix

| Response | Retry? | Verhalten |
|---|---|---|
| `2xx` | nein | Erfolg (`success=true`) |
| `4xx` (außer 429) | **nein** | Endgültig abgelehnt — nur loggen (`code` = Status) |
| `5xx` | ja (max 1) | 1 Retry, dann Final-Fail loggen |
| Network-Timeout / `WP_Error` | ja (max 1) | 1 Retry, dann `code=0` loggen |
| **`429`** | **Sonderpfad** | siehe § 5.3 |

**Max-Retries = 1** (RunCloud-Vorbild). Kein Provider blockiert je die Kette; Final-Fail
wird **nur geloggt**. *Hinweis:* MVP behandelt alle `WP_Error`-Codes uniform —
differenzierte Behandlung (SSL/Parse deterministisch, Retry sinnlos) → § 16.3.

### 5.3 429-Sonderpfad (MVP-Annahme)

- 1 Retry **nur** wenn `Retry-After ≤ 3 s` (`Transport::RETRY_AFTER_CAP_SECONDS = 3`).
- `Retry-After > 3 s` **oder** Header fehlt → **kein** in-request-Block → **skip + log**.
- **`Transport::RETRY_AFTER_CAP_SECONDS = 3` ist eine bewusste MVP-Annahme** (Querverweis
  § 16): Wir bleiben im MVP **synchron im Request** (kein async-Cron) — ein „komm in 60 s
  wieder" darf den `save_post`-Request nicht hängen lassen. Mit der async-Cron-Migration
  (§ 16) kann der Cap angehoben/entfernt werden. **Ohne diese Notiz ist `3` in 6 Monaten
  eine willkürliche Magic Number.**

### 5.4 Failure-Isolation & Credential-Schutz

- **Failure-Isolation:** Jeder Provider-Aufruf in `try/catch`; eine Exception (oder ein
  Fehler-Result) wird geloggt, die Iteration läuft beim **nächsten** Provider weiter. Ein
  Provider-Fehler bricht die Kette **nie** (Anti-Pattern „Kette blocken", § 14).
- **Credentials nie loggen:** Vor jedem Log-Eintrag (LogOnly-Dump, Fehler-Logs)
  `Authorization`/`AccessKey`-Header + Creds **redacted** (`***`). Gilt auch für den
  `Purge_Result`-`message`-String. Zentrale Redaction in `Transport`.

## 6. Listener

`Hooks/Listener.php`, bei `init` instanziiert (eager — verdrahtet Hooks im Konstruktor,
example-module § 2.6).

### 6.1 Native Trigger-Hooks (hartkodiert, spezifische Signaturen)

| Hook | Signatur | Guard / Zweck |
|---|---|---|
| `save_post` | `($post_id, $post, $update)` | Skip bei Autosave (`wp_is_post_autosave`), Revision (`wp_is_post_revision`), `auto-draft`; nur **supported Post-Types** (ADR-4, `depeur_food()->get_supported_post_types()`) |
| `transition_post_status` | `($new, $old, $post)` | Fängt Publish/Trash/Unpublish — purgt, wenn ein public Status¹ beteiligt ist (alte ODER neue URL wird stale) |
| `wprm_save_recipe` | `($recipe_id)` | **WPRM-gated** (`has_action`/`function_exists`-Check); purgt die Recipe-URLs. Fehlt WPRM → Hook nie registriert (§ 13) |

¹ **„public Status"** = `get_post_status_object( $status )->public === true`. Trifft auf
`publish` zu, nicht auf `draft`/`auto-draft`/`private` (deren URL-Sichtbarkeit ändert sich
nicht für anonyme Besucher).

### 6.2 `register_listeners`-Filter (zusätzliche Hooks)

```php
$additional_hooks = apply_filters( 'depeur_food/cache/register_listeners', array() );
```

Jeder Hook-Name wird auf den generischen Handler `purge_post( $post_id )` geroutet.
**Vertrag:** der Hook muss `post_id` als erstes Argument liefern. **Defensive Validation**
in `purge_post()`: `is_numeric( $post_id )` + `get_post( $post_id ) instanceof WP_Post`
— Mismatch → kein Fatal, kein Purge, optional `debug.log` mit Hook-Name + erhaltenem
Arg-Typ (diagnostizierbar statt silent broken). **Nicht-post-zentrierte Purges**
(Settings-Change, Theme-Update) laufen NICHT über register_listeners, sondern direkt via
`do_action( 'depeur_food/cache/purge', $ctx )` (§ 9) — vermeidet „zwei APIs für dasselbe".

### 6.3 `Purge_Context` (shared Value-Object, vom Listener gebaut)

```php
namespace Depeur\Food\Cache;

final readonly class Purge_Context {
    public function __construct(
        public ?int   $post_id,   // null bei nicht-post-zentrierten Purges
        public string $post_type, // '' wenn n/a
        public array  $urls,      // absolute URLs, dedupliziert
        public array  $tags,      // Cloudflare cache-tags (leer wenn keine)
        public string $reason,    // 'save_post' | 'transition:publish' | …
    ) {}
}
```

### 6.4 URL-Berechnungs-Scope (ADR-4-Treue — bindend)

Die URL-Berechnung ist **post-type-agnostisch** — eine **Konsequenz von ADR-4**, kein
Implementierungs-Detail. „Kategorie/Tag" darf **nicht hartkodiert** sein:
- Default-Set: **Permalink** + **Home** + **Term-Archive der public Taxonomien des
  Post-Types** (`get_object_taxonomies( $post_type )` → zugewiesene Terms →
  `get_term_link()`, **nur erste Seite** je Archiv) + **CPT-Archiv** falls `has_archive`.
- **alkipedia.com-Beispiel:** dort muss cache-bridge die Cocktail-Taxonomien
  (`cocktail_category`/`cocktail_ingredient` o. ä.) korrekt erkennen — hartkodiertes
  `category`+`post_tag` würde sie stillschweigend verfehlen, **Symptom: „Cache wird
  invalidiert, aber Archive bleiben stale".**

**Default-URL-Set explizit dokumentiert (drin vs. bewusst NICHT):**
- **Drin:** Permalink, Home, public-Taxonomie-Term-Archive (erste Seite), CPT-Archiv.
- **Bewusst NICHT** (Auslassung ≠ vergessen): Author-Archive, Datums-Archive,
  Pagination-Seiten 2+ (Caveat: Pagination-Explosion ↔ Bulk-Threshold).

**`purge_urls`-Filter** (eigene Verantwortung — *was* gepurgt wird, getrennt von
register_listeners = *wann*):
```php
$urls = apply_filters( 'depeur_food/cache/purge_urls', $default_urls, $context );
```
Designierter Erweiterungsort für Feed-URLs (`/feed/`), Sprach-Varianten (i18n, § 13),
oder die bewusst ausgelassenen Archiv-Klassen — ohne BRIEF-Update.

## 7. Debounce-Mechanismus

**Position:** im Listener, **VOR** `do_action` (orthogonal zum Pause-Mechanismus § 8,
der NACH `do_action` sitzt).

- **30 s-Transient** pro Post: `depeur_food_cache_debounce_{post_id}`. Existiert →
  **skip** (in den letzten 30 s schon gefeuert); frisch → setzen + `do_action`.
- **Zweck:** technische Doppel-Trigger abfangen (`save_post` feuert doppelt;
  `transition_post_status` + `save_post` beide beim selben Publish). Schützt zusätzlich
  die Pause-Queue vor Doppel-Trigger-Flutung.
- **Fenster filterbar** (Magic-Number-Pattern):
  `apply_filters( 'depeur_food/cache/debounce_window', 30 )`, Default 30 s, als MVP-Wert
  dokumentiert. Keying bleibt **per-Post** (globaler Wert, aber per-Post-Fenster).
- **Mechanik (präzise):** Der Debounce ist im **Listener** implementiert, **nicht** im
  `do_action`-Handler. Direkte Konsumenten umgehen den Debounce nicht aktiv — er
  **existiert technisch nicht für sie** (sie umgehen den Listener komplett, unabhängig
  vom `post_id`-Wert). Läge der Debounce im Handler, würden direkte `do_action`-Aufrufe
  stillschweigend gefiltert. Konsumenten verantworten ihre eigene Dedup-Logik.

## 8. Pause-Mechanismus (Variante C)

**Position:** in cache-bridge, **NACH** `do_action`, **VOR** Provider-Iteration. Drei
Klassen im `Pause/`-Subordner mit klarem Verantwortungs-Schnitt:

- **`Pause/Queue.php`** — Datenschicht: read/add/dedup/clear der Queue-Option. Pure
  Storage, keine Pipeline-Logik.
- **`Pause/Controller.php`** — Orchestrierung: Pause-Check, Enqueue-oder-Weiterreichen,
  Resume-Verarbeitung, Auto-Resume-TTL-Logik, Cron-Handler, `admin-post`-Handler.
- **`Pause/AdminBar.php`** — UI: Admin-Bar-Node + Toggle + Resume-Modal-Markup +
  JS-Enqueue.

### 8.1 State & Queue

- Toggle: Option `depeur_food_cache_purge_paused` (int, § 10.1) — **eine** Quelle der
  Wahrheit für Admin-Bar UND Settings-Schalter (§ 11).
- Queue: Option `depeur_food_cache_purge_queue` (`autoload=no`, **dedup pro URL,
  URL-only**). Tag-Invalidierungen werden NICHT gequeued (CF-Enterprise-Edge-Case,
  dokumentierte Limitation, § 10). Write-Race-Caveat → § 10.
- Admin-Bar-Anzeige: „pausiert seit HH:MM (X queued)".

### 8.2 Resume-Modal (Queue-Zerstörung erst NACH Entscheidung)

1. **[empfohlen]** Die X gequeueten URLs **gebündelt purgen**.
2. [sekundär] **Alle Caches leeren** (Notfall — Full-Purge, mit Warnung; destruktiv).
3. Cancel / nur Resume-State (Queue bleibt).

### 8.3 24 h-Auto-Resume (Hybrid)

- **Timestamp-Check beim nächsten Purge** (aktive Sites) **+ Daily-WP-Cron** (idle Sites,
  >24 h kein Purge). 24 h filterbar:
  `apply_filters( 'depeur_food/cache/pause_auto_resume_ttl', DAY_IN_SECONDS )`.
- **Auto-Resume-Aktion = Option (1) Batch-Purge** (NICHT Full-Purge, NICHT verwerfen —
  kein User da, der die destruktive Option bestätigt).
- **Admin-Notice:** dismissible (X), aber **kein** Auto-Dismiss nach Zeit — sichtbar bis
  explizit weggeklickt. Grund: der User soll aktiv zur Kenntnis nehmen, dass ohne seine
  Aktion durchgepurged wurde; Auto-Dismiss würde diesen Awareness-Effekt zerstören.

### 8.4 Security & Assets

- Pause/Resume-Toggles sind **state-changing** → `manage_options`-Cap + Nonce (Disziplin
  wie Task 4: cap → nonce). Admin-Bar-Toggle + Modal-Submit laufen über `admin-post`/AJAX,
  **verarbeitet vom Controller** (nicht der Render-Klasse AdminBar).
- Asset-Convention (differenziert nach Kontext, plugin-weit anzustreben):
  - **Admin-Assets** (Settings, Admin-Bar, Resume-Modal): jQuery erlaubt (Core-Admin lädt
    es ohnehin), **nicht Pflicht**; Vanilla gleichwertig. Heuristik: jQuery nur, wenn es
    das Code-Volumen >30 % reduziert.
  - **Frontend-Assets** (cache-bridge hat keine; gilt für Folge-Module): **Vanilla
    strikt**, **kein** jQuery-Enqueue — Kadence ist Frontend-jQuery-frei, das nicht
    sabotieren (~30 KB Bloat/Page-Load).
- Konkret: `assets/admin/cache-bridge-pause.js` — **Vanilla** (kein Build-Step,
  `wp_enqueue_script()` mit `deps = array()`), in `AdminBar.php` enqueued, gegated auf
  `is_admin()` + `current_user_can( 'manage_options' )`.

### 8.5 Vollständige Pipeline-Order (beide Eintrittspfade)

```
TRIGGER-PFAD (Listener)                        DIREKTER PFAD (Konsument)
save_post / transition_post_status /           Schema-Engine / Newsletter / Theme:
wprm_save_recipe / {register_listeners}        baut Purge_Context selbst,
      │                                        feuert do_action direkt
      ▼                                        (umgeht Listener + Debounce — § 7)
   Listener ── baut Purge_Context                      │
   (URL-Scope § 6.4 + purge_urls-Filter)               │
      │                                                │
      ▼                                                │
   Debounce-Check (§ 7) ──skip──▶ Ende                 │
      │ frisch                                         │
      ▼                                                │
   do_action( 'depeur_food/cache/purge', $context ) ◀──┘
      │
      ▼
   Pause-Controller::check()   (Purge_Runner ruft is_paused_after_ttl_check)
      │
      ├─ paused? ─ja─▶ TTL-Check (>24h? → Auto-Resume Batch) ─noch paused─▶ Queue ─▶ Ende
      │                                                       └─ resumed ─┐
      └─ nein ──────────────────────────────────────────────────────────┤
                                                                         ▼
              Provider-Iteration (Purge_Runner, Default-Order § 3.4, Failure-Isolation
              § 5.4) ──▶ depeur_food_cache_last_purge schreiben (§ 10.1)
                  pro Provider: is_available()? → purge() via Transport (§ 5)
```

## 9. Public API

### 9.1 Action (Konsumenten-facing — DER Eintrittspunkt)

```php
do_action( 'depeur_food/cache/purge', Purge_Context $context );
```
Konsumenten (Schema-Engine, Newsletter, Theme, externe Module) bauen ein
`Purge_Context` und feuern diese Action. **Einziger** öffentlicher Trigger — sie kennen
weder Provider noch Pause/Debounce. Fire-and-forget (kein Return; Ergebnisse landen im
Log / `last_purge`-Status, § 10).

### 9.2 Filter (Erweiterungspunkte)

| Filter | Signatur | Zweck | § |
|---|---|---|---|
| `depeur_food/cache/register_providers` | `(array $fqcn)` | Provider-Set (FQCN-Strings) | 4.5 |
| `depeur_food/cache/register_listeners` | `(array $hooks)` | zusätzliche post_id-first Trigger-Hooks | 6.2 |
| `depeur_food/cache/purge_urls` | `(array $urls, Purge_Context $ctx)` | URL-Set pro Purge | 6.4 |
| `depeur_food/cache/timeout` | `(int $sec, string $provider_id, Purge_Context $ctx)` | HTTP-Timeout | 5.1 |
| `depeur_food/cache/debounce_window` | `(int $sec)` | Debounce-Fenster | 7 |
| `depeur_food/cache/pause_auto_resume_ttl` | `(int $sec)` | Auto-Resume-TTL | 8.3 |
| `depeur_food/cache/bulk_chunk_multiplier` | `(int $mult)` | Bulk-Eskalations-Faktor | 13 |

### 9.3 Admin-post/AJAX-Actions (intern — Pause-UI, NICHT Konsumenten-facing)

Pause-Toggle + Resume-Modal-Submit laufen über `admin-post`-Actions (Nonce +
`manage_options`, verarbeitet vom `Pause/Controller`, § 8.4). Bewusst **intern**: kein
Konsument soll Pause programmatisch toggeln — das ist Operator-UI, keine API.

## 10. Datenstrukturen

**Keine Meta-Keys, keine Custom Tables** (MVP). Nur Optionen + Transients.

### 10.1 Optionen

| Option | autoload | Inhalt |
|---|---|---|
| `depeur_food_cache_bridge` | **no** | Pro Provider: `enabled` (bool), `order` (int), Credentials (CF `api_token`/`zone_id`, Bunny `api_key`/`pull_zone_id`). **autoload=no** weil Secrets (ADR-1 / Task-4-Pattern). |
| `depeur_food_cache_purge_paused` | yes | **int** — `0` = nicht pausiert, sonst **Pause-seit-Unix-Timestamp**. Ein Feld trägt State UND „pausiert seit" UND TTL-Basis (kein separates Timestamp-Feld). autoload=yes, weil die Admin-Bar es auf jedem Admin/Frontend-Request für eingeloggte User liest (winziger Wert). |
| `depeur_food_cache_purge_queue` | **no** | Dedup-URL-Array (URL-only, § 8.1). autoload=no — kann groß werden, selten gelesen. |
| `depeur_food_cache_last_purge` | **no** | Letzter Purge-Status für Admin-Anzeige. **Write-Punkt:** am Ende der Provider-Iteration (nach allen Calls, vor Pipeline-Exit), Schreiber = `Purge_Runner`. Schema: `array( 'timestamp' => int, 'providers' => array<provider_id, success_bool> )`. Settings-UI zeigt: „Letzter Purge: vor X Min (LogOnly ✓, RunCloud ✓, BunnyCDN ✗, Cloudflare ✓)". |

**⚠ Write-Race (Queue):** `depeur_food_cache_purge_queue` ist read-modify-write — zwei
gleichzeitige Saves verschiedener Posts können einen Lost-Update erzeugen. Durch Debounce
gemildert (gleicher Post), nicht eliminiert (verschiedene Posts). Akzeptierte
MVP-Limitation; Worst Case = eine URL fällt aus der Queue → bleibt stale bis zum nächsten
Purge dieses Posts (Cache-Staleness, **kein** Datenverlust). Robuste Lösung (Custom-Table
mit INSERT) → § 16.

**⚠ Pause-Toggle-Race:** gleichzeitige Toggle-Actions können sich überschreiben.
Mitigation: **idempotente `update_option()`-Calls ohne Read-Compare** („pause now" = `set
timestamp`, „resume" = `set 0`). Da kein Read-Modify-Write nötig ist, ist die Race auf
„letzte Action gewinnt" reduziert — deterministisch, **ohne** Lost-Update-Risiko wie bei
der Queue. (Anti-Pattern „vor Pause-Set prüfen ob schon paused" → § 14.2.)

### 10.2 Transients

| Transient | TTL | Inhalt |
|---|---|---|
| `depeur_food_cache_debounce_{post_id}` | 30 s (filterbar) | Debounce-Marker (§ 7) |
| `depeur_food_cache_runcloud_available` | 1 h | RunCloud-`is_available()`-Probe-Ergebnis (§ 4.4); bei Settings-Save geleert |

## 11. Settings-UI

cache-bridge-Tab in der SettingsPage (via `SettingsRegistry`, Tab-System Task 4). Die
Felder sind **catalog-getrieben**: `Admin/Settings.php` iteriert beim `init` die
registrierten Provider-FQCNs, ruft `$fqcn::get_catalog()` **statisch** (keine Instanz,
§ 12) und baut daraus das Feld-Schema, das an `SettingsRegistry::register()` geht.

### 11.1 Felder

- **Pro Provider** (aus `Provider_Catalog`):
  - `enabled` — Checkbox (Default aus `default_enabled`: LogOnly=on, Rest=off).
  - `order` — Reihenfolge-Wert (Default-Order § 3.4).
  - **Credential-Felder** (nur CF/Bunny) — aus `credential_fields`: Password-Typ
    (`api_token`/`api_key`) mit **Preserve-on-empty** (leerer Submit behält gespeicherten
    Wert, Task-4-Pattern) + Text (`zone_id`/`pull_zone_id`).
  - LogOnly/RunCloud: **keine** Creds, nur `enabled` (+ `order`).
- **Pause-Toggle** — Checkbox, spiegelt die Admin-Bar (dieselbe Option
  `depeur_food_cache_purge_paused`, § 8.1).
- **Cross-Modul-Hinweis** (informativ, kein Hard-Req): WPRM-Präsenz beeinflusst den
  Listener — Hinweis-Text, wenn WPRM aktiv/inaktiv ist.

### 11.2 Feldtyp `order` — Field_Renderer-Trigger, bewusst deferred

Der `order`-Wert ist der erste Feldtyp jenseits der vier vorhandenen
(checkbox/text/select/password) und triggert laut CLAUDE.md › Architecture Notes die
**Erwägung** der `Field_Renderer`-Extraktion. **Entscheidung: MVP umgeht die Extraktion**
— `order` wird als **einfaches `number`-Feld** pro Provider umgesetzt (niedriger = früher),
wiederverwendbar nah am bestehenden `text`-Renderer. Drag-Sort-UI + `Field_Renderer`-
Extraktion = **§ 16-Future** (nicht MVP). So bleibt cache-bridge ohne Settings-Core-Umbau
implementierbar; der Architektur-Trigger ist notiert, aber bewusst nicht jetzt gezogen.

## 12. Lifecycle

### 12.1 Modul-Bootstrap (`module.php`, nur wenn Modul aktiv — Modul-Kanon § 9)

```php
// module.php — vom ModuleManager require_once'd, NUR für aktive Module
$slug = basename( __DIR__ );                            // Single Source of Truth (Kanon)
new Hooks\Listener( $slug );        // verdrahtet Trigger-Hooks + Debounce
new Hooks\Purge_Runner( $slug );    // verdrahtet do_action('…/cache/purge')-Handler
new Pause\Controller( $slug );      // verdrahtet Pause-Hooks + Admin-Bar + Daily-Cron
new Admin\Settings( $slug );        // catalog-getriebene Settings-Registrierung (init)
```

**Alle vier Konstruktoren sind billig** — sie rufen nur `add_action`/`add_filter` bzw.
`SettingsRegistry::register`. **Kein Provider wird hier gebaut.** (`add_action`
registriert nur den Callback; sein Body läuft erst bei `do_action`.)

### 12.2 Eager vs. Lazy (die Kern-Mechanik für die Code-Session)

| Komponente | Wann gebaut | Warum |
|---|---|---|
| Listener, Purge_Runner, Pause/Controller, Admin/Settings | **eager** (init) | müssen Hooks/Settings verdrahten |
| **Provider-Instanzen** | **lazy** (nur bei Purge) | Frontend-Footprint = null; `is_available()` macht I/O (RunCloud, § 4.4) |
| Provider-**Catalog** (Admin) | **statisch** (kein `new`) | reine Metadaten für die Settings-UI |

### 12.3 Catalog statisch (Admin) — KEINE Instanz-Konstruktion

```php
// Admin\Settings — beim init, baut das Feld-Schema
foreach ( $provider_classes as $fqcn ) {
    if ( ! class_exists( $fqcn )
        || ! is_subclass_of( $fqcn, Provider_Interface::class ) ) {
        continue;                          // skip + debug.log (§ 14)
    }
    $catalog = $fqcn::get_catalog();       // STATISCH — kein new, kein I/O
    // → enabled-Checkbox + credential_fields + order-Feld aus $catalog (§ 11)
}
```

### 12.4 Provider lazy (Purge-Pfad) — Config-Injection, side-effect-free

```php
// Hooks\Purge_Runner::run( Purge_Context $ctx ) — läuft NUR bei do_action
if ( $this->pause->is_paused_after_ttl_check() ) {   // lazy Auto-Resume-Check drin (§ 12.5)
    $this->pause->queue()->add( $ctx->urls );        // enqueue + Ende
    return;
}
$config  = get_option( 'depeur_food_cache_bridge', array() );   // EINMAL gelesen
$results = array();
foreach ( $this->ordered_enabled_classes() as $id => $fqcn ) {
    if ( ! class_exists( $fqcn )
        || ! is_subclass_of( $fqcn, Provider_Interface::class ) ) {
        continue;                                     // skip + debug.log (§ 14)
    }
    $provider = new $fqcn( $config[ $id ] ?? array() ); // Config-Slice injiziert,
                                                        // Konstruktor = pure Zuweisung (§ 14)
    if ( ! $provider->is_available() ) {
        continue;                                     // I/O erst HIER (RunCloud transient-cached)
    }
    try {
        $results[ $id ] = $this->purge_with_bulk_policy( $provider, $ctx ); // § 13-Eskalation
    } catch ( \Throwable $e ) {
        $results[ $id ] = /* fail-Result */;          // Failure-Isolation: log + continue (§ 5.4)
    }
}
$this->write_last_purge( $results );                  // depeur_food_cache_last_purge (§ 10.1)
```

### 12.5 Pause-Controller-Lifecycle

- **EAGER** (Konstruktor): `add_action` für Admin-Bar-Render, Resume-Modal-`admin-post`-
  Handler, **Daily-Cron-Event registrieren**.
- **LAZY**: Auto-Resume-TTL-Check (`paused`-Timestamp-Vergleich + Queue-Inspektion) läuft
  **nur** in `is_paused_after_ttl_check()`, d. h. nur bei einem anstehenden Purge —
  **nicht** auf jedem Frontend-Request (kumulativ unnötig ×99 % Page-Views).
- **Daily-Cron** = Idle-Fallback (idle Site, >24 h kein Purge): eager registriert, läuft
  1×/Tag, ruft denselben Auto-Resume-Pfad.

### 12.6 „loaded ⟺ active" & Uninstall
- ModuleManager lädt `module.php` nur für aktive Module (Kanon § 9). Modul prüft die
  Master-Liste nicht selbst.
- Uninstall: `uninstall.php` purged `depeur_food_%` per LIKE (Task 1) → alle
  cache-bridge-Optionen automatisch mit-aufgeräumt.
- **Cron-Deregistrierung beim Modul-Deactivate** (`wp_clear_scheduled_hook`, sonst Waise)
  ist **Task-4b-Verantwortlichkeit** (Modul-Toggle-UI designt das Deaktivierungs-Pattern
  einheitlich für alle Module): cache-bridge **stellt die Deregistrierungs-Funktion
  bereit**, der Manager ruft sie auf. Bis Task 4b existiert, ist das ein bekannter offener
  Punkt (§ 16).

## 13. Edge-Cases

- **WPRM inaktiv:** `wprm_save_recipe`-Hook wird nur registriert, wenn WPRM da ist
  (`function_exists`/`has_action`-Guard). Fehlt WPRM → Hook nie verdrahtet, kein Fehler.
- **Provider unavailable** (Creds fehlen / Env nicht erkannt): `is_available()` → `false`
  → dieser Provider übersprungen, **andere laufen** (Failure-Isolation).
- **Leere `urls` → sicherer No-Op + lautes Log** (NICHT Full-Purge). Korrigiert die
  Sketch-Annahme „leer = full-purge": ein versehentlich leerer Context darf **nie** das
  CDN nuken. **Es gibt im MVP keinen programmatischen Full-Purge-Trigger** — Full-Purge
  ist ausschließlich operator-initiiert (Resume-Modal Option 2, mit Warnung).
- **Bulk-Edit-Sturm** (Szenario 1: ein Context, viele URLs) — Eskalations-Leiter:

  | URL-Count | Strategie |
  |---|---|
  | `≤ threshold` | per-URL Einzel-Purge |
  | `threshold < n ≤ 5× threshold` | chunked Batch-Purge |
  | `> 5× threshold` | **Default: skip + loud log** (kein Auto-Nuke); Full-Purge nur per-Provider **opt-in** |

  `5×` filterbar (`depeur_food/cache/bulk_chunk_multiplier`). **Szenario 2** (Bulk-Edit
  von 200 Posts = 200 kleine Contexts) fängt der Threshold **nicht** — dafür existiert der
  **Pause-Mechanismus** (Operator pausiert vor Bulk-Operation). Auto-Detect via
  `$_REQUEST['bulk_edit']` = **§ 16-Future** (nicht 100 % reliable, eigenes Heuristik-
  Engineering).
- **CF `tags[]` ohne Enterprise:** graceful skip auf `files[]`-Purge, kein Fehler.
- **Queue überlebt WP-Restart**, User vergisst Pause → 24 h-Auto-Resume (§ 8.3).
- **Multi-CPT:** post-type-agnostischer URL-Scope (§ 6.4) trägt CPTs ohne Sonderfall.
  Konkret auf alkipedia.com: ein Cocktail-Post-Type mit eigenen Taxonomien (z. B.
  `cocktail_category`) wird **identisch zum Standard-`post`** behandelt — Permalink + Home
  + Cocktail-Taxonomie-Term-Archive + Cocktail-Archiv falls `has_archive`.
- **i18n/Mehrsprachigkeit:** Sprach-URL-Varianten sind **nicht** im Core-Default-Set;
  WPML/Polylang hängen ihre übersetzten URLs via `purge_urls`-Filter an (§ 6.4). cache-
  bridge hartkodiert keine i18n-Logik.

## 14. Anti-Patterns (Hard-Constraints)

### 14.1 Ein Provider darf NICHT …
- **Fatal werfen / die Kette blocken.** `purge()` wirft nie — Fehler werden im
  `Purge_Result` codiert (§ 4.2). Failure-Isolation ist sonst gebrochen.
- **Credentials loggen.** Vor jedem Log redacten (§ 5.4).
- **Synchron ohne Timeout blocken.** Jeder HTTP-Call läuft über `Transport` (Timeout-
  Filter, § 5).
- **I/O im Konstruktor.** Kein Netzwerk, **kein** `is_available()`-Probe, keine
  Hook-Registrierung im Konstruktor — Konstruktor = pure Config-Zuweisung (§ 12.4).
- **Cross-Module-Direktimport / Suite referenzieren** (Splitting-Strategie, § 1).
- **Eigenes Bootstrapping außerhalb `module.php`** (kein eigener Früh-Hook).

### 14.2 Das Modul darf NICHT …
- **Provider-FQCN blind instanziieren.** `class_exists()` + `is_subclass_of(…,
  Provider_Interface::class)` sind **Pflicht** vor jedem `new`. **Blindes Instanziieren
  externer Klassen-Strings ist ein Code-Injection-Vektor (via Filter) UND
  Stabilitäts-Risiko (Fatal bei nicht-existentem FQCN).**
- **Debounce in den `do_action`-Handler legen** statt in den Listener — direkte
  Konsumenten würden sonst stillschweigend gefiltert (§ 7).
- **Pause-Toggle als Read-Modify-Write** implementieren („erst prüfen ob schon paused,
  dann setzen") — erzeugt die Toggle-Race. Stattdessen idempotente `update_option()`
  (§ 10.1).
- **`category`/`post_tag` hartkodieren** statt post-type-agnostisch (ADR-4, § 6.4).
- **`register_listeners`-Hooks ohne `post_id`-Validation** routen (§ 6.2).
- **Bei leeren `urls` still full-purgen** (§ 13).

## 15. Dependencies

- **Externe Endpoints** (nur bei aktiviertem Provider + vorhandenen Creds):
  `https://api.bunny.net`, `https://api.cloudflare.com/client/v4`. **RunCloud lokal**
  (kein externer Endpoint, kein Cred — Nginx-`PURGE` auf dem eigenen Host).
- **WP-HTTP-API** (via `Transport`, § 5) — **kein** Guzzle/cURL.
- **PHP 8.2+** (ADR-2 — `final readonly class` für die Value-Objects), **WP 6.5+**.
- **WPRM optional** — nur der `wprm_save_recipe`-Listener-Hook, `function_exists`-gated
  (§ 6.1). Modul funktioniert vollständig ohne WPRM.
- **Interne shared Deps:** `src/Cache/`-Value-Objects + `Transport` (heute selbes Plugin,
  beim Split → Shared-Core, § 3.2).
- **KEINE Suite-Referenz, KEINE Cross-Module-Klassen-Imports** (Splitting-Strategie, § 1).
  Kommunikation mit anderen Modulen ausschließlich via Hook-Fassade.

## 16. Bekannte Risiken / Annahmen / Open Questions

Bleiben **nach** BRIEF-Approval offen — genuine Langfrist-Punkte, **keine**
Pre-Implementation-Entscheidungen.

### 16.1 Annahmen (bewusst, nicht jetzt gelöst)
- **Synchron im Request reicht für MVP.** Async-via-Cron erst, wenn API-Limits real
  erreicht werden. Der 429-`RETRY_AFTER_CAP_SECONDS = 3` (§ 5.3) ist an diese Annahme
  gekoppelt — bei async-Migration anhebbar/entfernbar.
- **(unverifiziert)** Cloudflare `purge_cache` ist idempotent.
- **(unverifiziert)** BunnyCDN `purgeCache` 2xx = tatsächlich invalidiert (kein
  Verify-Callback).
- RunCloud-Nginx-`PURGE` auf die eigene URL ist no-op/sicher, wenn kein FastCGI-Cache
  aktiv ist.

### 16.2 Akzeptierte Risiken
- **Rate-Limits** bei großen Bulk-Operationen trotz Threshold-Chunking.
- **Pause-Queue-Write-Race** (§ 10, durch Debounce gemildert, nicht eliminiert).

### 16.3 Deferred to v2 / Future (mit §-Verweis)
- **Differenzierte `WP_Error`-Code-Behandlung** (§ 5.2): SSL/Parse-Errors sind
  deterministisch, Retry sinnlos. MVP behandelt alle `WP_Error` uniform für Simplizität
  — bei operativen Erfahrungswerten in v2 differenzieren.
- **Bulk-Edit-Auto-Detect** (§ 13, Szenario 2): `$_REQUEST['bulk_edit']` nicht 100 %
  reliable (manche Plugins triggern Bulk anders) → robuster Auto-Detect wäre eigenes
  Heuristik-Engineering, nicht MVP. Heute: manuelles Pause.
- **`Field_Renderer`-Extraktion + Drag-Sort-UI** für Provider-`order` (§ 11.2).
- **Custom-Table für die Queue** (§ 10) als robuster Write-Race-Fix.
- **Programmatischer Full-Purge-Trigger:** MVP hat **keinen** (Full-Purge nur
  operator-initiiert, § 13). Revisit, falls ein Konsument echten programmatischen
  Full-Purge braucht.
- **Cron-Deregistrierungs-Pattern** (§ 12.6): bis Task 4b (Modul-Toggle-UI) existiert,
  ist die einheitliche Deaktivierungs-Deregistrierung offen.
- **Split-Boundary** `src/Cache/` vs. Modul (§ 3.2): `Transport`/`Purge_Result`/
  `Provider_Catalog` wandern beim Plugin-Split ggf. nach Depeur Speed; `Purge_Context`
  bleibt Shared-Core-Kontrakt.

## 17. Smoke-Test-Definition

**CI / Plugin-Smoke (gemockt, deterministisch, offline):**
- LogOnly feuert auf `save_post` → `error_log`-Eintrag (redacted).
- **Mock = `pre_http_request`-Filter** via wiederverwendbare Utility
  `tests/utilities/MockedHttpResponses.php` (`::mock_cloudflare_success()` /
  `::mock_bunny_rate_limited()` / `::mock_runcloud_timeout()` — jede hängt den Filter,
  liefert Reset-Callback; `tearDown()` ruft Reset). **cache-bridge legt dieses Pattern
  als Erst-Konsument** — Schema-Engine/Newsletter erben es.
- **Failure-Isolation:** ein Provider mockt einen Fehler → andere laufen.
- **`code`/`success`-Dreiteilung** (§ 4.2): Vektoren 200 / 403 / 0 (Timeout) verifizieren.
- **Bulk-Eskalations-Leiter** (§ 13): per-URL / chunked / skip+loud-log; **Caveat-Pfad
  gemockt** (Full-Purge opt-in OFF → skip+log; ON → Full-Purge-Call ausgelöst).
- **Debounce-Skip**; **Pause → Queue → Resume-Modal-Pfade**; **24 h-Auto-Resume**
  (TTL-Manipulation im Test).

**Manual-Smoke (echte Calls, am Live-/Test-Server):**
- RunCloud-`is_available()`-HTTP-Probe gegen `runcloud-test`
  (`/home/runcloud/webapps/Food-Blog_Template/`, Item-1) — echt, risikoarm (keine Creds).
- CF/Bunny echte Purges **nur gegen Test-Zonen** — **nie** gegen Produktion
  (einfachandersessen.de / alkipedia.com), sonst echtes Cache-Nuke + API-Quota-Verbrauch.

**Statisch:** phpcs Exit 0 · `php -l` clean · `debug.log` frei von depeur-Zeilen ·
`wp plugin check` 0 neue Findings.

## 18. Konkrete Datei-Liste

Sichtkontrolle gegen §§ 1–17 durchgeführt; gegenüber der Sketch-Liste **ergänzt**:
`src/Cache/Transport.php` (§ 5) + `Hooks/Purge_Runner.php` (§ 12). Alle neu, außer der
Autoloader-Verifikation.

### 18.1 Shared (`src/`, außerhalb Modul) — 4 Files
| File | Rolle | ~LOC |
|---|---|---|
| `src/Cache/Purge_Context.php` | Eingabe-Value-Object (§ 6.3) | 30 |
| `src/Cache/Purge_Result.php` | Rückgabe-Value-Object (§ 4.2) | 30 |
| `src/Cache/Provider_Catalog.php` | statische Provider-Metadaten (§ 4.3) | 30 |
| `src/Cache/Transport.php` | cross-cutting HTTP-Executor + Retry/Timeout/429/Redaction (§ 5) | 140 |

### 18.2 Modul (`modules/cache-bridge/`) — 14 Files
| File | Rolle | ~LOC |
|---|---|---|
| `manifest.php` | Metadaten (Root, lowercase) | 20 |
| `module.php` | Bootstrap, instanziiert 4 Klassen (§ 12.1) | 25 |
| `Hooks/Listener.php` | Trigger-Hooks + Guards + Debounce + Context-Bau + URL-Scope (§ 6, 7) | 190 |
| `Hooks/Purge_Runner.php` | `do_action`-Handler: Pause-Check → Provider-Iteration → Bulk-Policy → `last_purge` (§ 12.4, 13) | 150 |
| `Providers/Provider_Interface.php` | Interface (§ 4.1) | 30 |
| `Providers/Provider_LogOnly.php` | always-on Audit-Log (§ 4.4) | 50 |
| `Providers/Provider_RunCloud.php` | Origin Nginx-PURGE + Probe + Transient (§ 4.4) | 120 |
| `Providers/Provider_BunnyCDN.php` | Edge, eigenständig (kein Suite-Bridge), api_key+pull_zone_id (§ 4.4) | 110 |
| `Providers/Provider_Cloudflare.php` | Edge, files[]/tags[]/everything (§ 4.4) | 110 |
| `Pause/Controller.php` | Pause-Logik, Resume, Auto-Resume, Cron, admin-post (§ 8) | 180 |
| `Pause/Queue.php` | Queue-Storage (read/add/dedup/clear) (§ 8.1) | 60 |
| `Pause/AdminBar.php` | Admin-Bar-Node + Resume-Modal-Markup + JS-Enqueue (§ 8.4) | 120 |
| `Admin/Settings.php` | catalog-getriebene Settings-Registrierung (§ 11) | 120 |
| `assets/admin/cache-bridge-pause.js` | Vanilla, Resume-Modal + Toggle (§ 8.4) | 80 |

### 18.3 Tests — Pattern-legend (Infra im Smoke-Code-Session konkretisiert)
| File | Rolle | ~LOC |
|---|---|---|
| `tests/utilities/MockedHttpResponses.php` | wiederverwendbare HTTP-Mock-Utility (§ 17) | 100 |
| `tests/smoke/…` | konkrete Smoke-Files (Code-Session) | TBD |

### 18.4 Verifikation (kein neuer Code erwartet)
- **Smoke-Step-0 (vor erstem Provider-Code, ZWINGEND):** Autoloader-Verifikation. Der
  Autoloader (`src/Helpers/Autoloader.php`, Task 1) muss `Word_Word`-Klassen-Files
  auflösen. PSR-4 behandelt Unterstriche literal → **sollte** ohne Änderung greifen, aber
  „sollte" ist hier teuer (bei Fehlschlag löst KEINE Provider-Klasse auf). Erster
  Code-Schritt:

  ```
  class_exists( 'Depeur\\Food\\Modules\\CacheBridge\\Providers\\Provider_LogOnly' ) === true
  ```

  Liefert das `false`, ist der Autoloader **vor jedem Provider-Code** zu fixen.

**Gesamt-Umfang:** ~19 neue Files (4 shared + 14 Modul + MockedHttpResponses; Smoke-Files
TBD), **~1.700–1.800 LOC** → bestätigt die Mehr-Session-Schätzung (5–6 Code-Sessions).
Implementierungs-Reihenfolge (Code-Phase): shared Value-Objects + Transport →
Provider_Interface → Listener + Purge_Runner + module.php-Skelett → vier Provider →
Pause-Trio + Assets → Admin/Settings → Smoke + MockedHttpResponses.
