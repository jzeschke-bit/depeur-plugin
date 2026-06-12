# BRIEF-SKETCH.md — `cache-bridge` (Vorarbeit zu BRIEF.md, Schema 1.1)

> **⚠️ TEMPORARY.** Dieses File ist die Diskussions-Skizze VOR dem Volltext-BRIEF. Es wird beim BRIEF-Finalize **gelöscht/umbenannt**. Lebensdauer: bis `BRIEF.md` freigegeben ist. **Kein** Architektur-Kanon — nur Struktur + Stichworte + offene Entscheidungen.
>
> **Stand:** Session 2 (2026-06-12). Recon Tier 1+2 abgeschlossen. 18-Sektion-Struktur approved. 11 Pre-Decisions vom User vorgeschlagen + 4 Recon-Caveats. **Volltext-BRIEF: nächste Session.**

---

## Teil A — 18-Sektion-Struktur (Stichworte, KEIN Volltext)

### § 1 — Meta / Schema-Version & Einordnung
- Schema 1.1; Notiz „erster vollständiger § 12.2-BRIEF, basiert auf _example v1.0 + geschäftslogik-Sektionen"
- Cluster-Zugehörigkeit: **Depeur Speed** (Splitting-Strategie → CLAUDE.md Architecture Notes)
- Living-Doc-Hinweis (§ 12.4); Default-ON-Modul

### § 2 — Zweck & Funktionalitäts-Inventar
- Hook-First Cache-Purge-Fassade bei Content-Änderungen (Origin + CDN)
- **Keine Legacy-Plugin-Migrationsquelle** (Neu-Feature — explizit vermerken, weicht von § 12.2 #1 „Migrations-Quelle" ab)
- Recon-Quellen: Suite-`BunnyApi`/`PurgeService` (Inspiration), Vendor bunnycdn/runcloud-hub/wp-rocket (API-Patterns)
- Abgrenzung: purgt — generiert/cached selbst nichts

### § 3 — Eingefrorene Architektur-Entscheidungen (high-level)
- Hook-First: Konsumenten feuern `do_action('depeur_food/cache/purge', Purge_Context)`
- `Purge_Context` readonly Value-Object in `src/Cache/` (shared, **außerhalb** Modul) — Naming-Lean: snake_case `Purge_Context` beibehalten (nicht binding)
- Provider-Pattern + Registrierungs-Filter; Execution in registrierter Reihenfolge; Failure-Isolation (ein Fehler blockt Kette nicht)
- `Provider_Log_Only` Always-on
- Provider-Klassen PascalCase in `Providers/` (FS-Safety § 2.7)

### § 4 — Provider-Interface & -Katalog
- Interface: `is_available(): bool` · `purge( Purge_Context ): Purge_Result` · `get_bulk_threshold(): int`
- `is_available()`-Semantik **provider-spezifisch** (Creds-vorhanden vs. Env-erkannt) — explizit dokumentieren (Befund F1)
- BunnyCDN: Creds api_key + pull_zone_id; full=`POST /pullzone/{id}/purgeCache`, per-URL=`POST /purge?url=`; `AccessKey`-Header
- Cloudflare: Creds api_token + zone_id; `POST /zones/{zone}/purge_cache`, Body `purge_everything`|`files[]`|`tags[]`; `Authorization: Bearer`; **tags = Enterprise → capability-gated**
- RunCloud: **keine Creds**; lokaler Nginx-`PURGE`; `is_available()` = HTTP-Probe primär (Test-PURGE → `X-RunCache-Type`-Header), Filesystem `/etc/runcloud/` Fallback (Pre-Decision #11→D1)
- LogOnly: always-available, `error_log` Context-Dump, Creds-redacted

### § 5 — Transport- & Retry-Policy (cross-cutting)
- WP-HTTP-API (`wp_remote_request`), **nie** Guzzle/curl
- Timeout 12s default, per-Provider via Constant überschreibbar (Pre-Decision #3)
- **Retry-Matrix:** Retry bei 5xx + Network-Timeout/`WP_Error`; **kein** Retry bei 4xx
- 429-Sonderfall: 1 Retry mit `Retry-After`-Respekt **bis Cap ≤ 3s** (Caveat) — sonst skip+log
- Max-Retries 1 (RunCloud-Vorbild); nie Chain-Block; Final-Fail → nur loggen
- Creds nie loggen (redact/sanitize)

### § 6 — Listener
- Hooks: `save_post` + `transition_post_status` + `wprm_save_recipe` (WPRM-gated)
- Baut + befüllt `Purge_Context` (post_id, post_type, urls, tags, reason)
- **URL-Scope:** Permalink + Home + relevante Archive (Kategorie/Tag), Archive **auf erste Seite je Archiv begrenzt** (Caveat zu Pre-Decision #5)
- Filter `depeur_food/cache/register_listeners` (externe Module hängen eigene Trigger) — JA (Pre-Decision #6)
- Autosave/Revision/Bulk-Guards (→ § 13)

### § 7 — Debounce-Mechanismus (sitzt im Listener, VOR `do_action`)
- 30s-Transient (Suite-Vorbild), verhindert technische Doppel-Trigger
- Pipeline-Position: `save_post → Listener → Debounce-Check → do_action`
- Schützt auch die Pause-Queue vor Flutung mit Doppel-Triggern (orthogonal zu Pause)
- Fenster: Vorschlag fix 30s (BRIEF: konfigurierbar? global vs. per-Post?)

### § 8 — Pause-Mechanismus (Variante C, sitzt in cache-bridge, NACH `do_action`, vor Provider-Iteration)
- Pipeline-Position: `do_action → cache-bridge → Pause-Check → Queue ODER Provider-Iteration`
- Globaler Toggle: Admin-Bar-Button + Settings-Schalter; State in `depeur_food_cache_purge_paused`
- Queue: `depeur_food_cache_purge_queue` (autoload=no, **dedupliziert pro URL**)
- Admin-Bar-Anzeige: „pausiert seit HH:MM (X queued)"
- Resume-Modal: (1) **[empfohlen]** X URLs gebündelt purgen · (2) [sekundär] alle Caches leeren (Notfall + Warnung) · (3) Cancel/nur Resume-State
- Queue-Zerstörung erst NACH Modal-Entscheidung
- **24h-Auto-Resume** mit Notice (Timestamp-Check beim nächsten Purge + WP-Cron-Backup, Pre-Decision #8)

### § 9 — Public API
- Action `depeur_food/cache/purge` (Konsumenten-facing) — Signatur + Zweck
- Filter `depeur_food/cache/register_providers` (eigene Provider)
- Filter `depeur_food/cache/register_listeners` (s. § 6)
- Admin-Bar/Resume-Actions (BRIEF: öffentlich vs. intern)

### § 10 — Datenstrukturen
- Option `depeur_food_cache_bridge` (autoload=no): Provider-Flags + Order + Credentials (CF token/zone, Bunny key/pull-zone)
- Option `depeur_food_cache_purge_paused` (bool)
- Option `depeur_food_cache_purge_queue` (autoload=no, dedup-URL-Array) — Known-Limitation: Write-Race bei gleichzeitigen Saves, durch Debounce gemildert (Caveat zu Pre-Decision #7)
- `last_purge`-Status-Option (Admin-Anzeige, Suite-Vorbild)
- **Keine** Meta-Keys, **keine** Custom Tables

### § 11 — Settings-UI
- Pro Provider: Enable-Checkbox + Credential-Felder (Password-Typ, **Preserve-on-empty** wie Task 4) + Order
- RunCloud/LogOnly: keine Creds (nur Toggle)
- Pause-Toggle (spiegelt Admin-Bar)
- Cross-Modul-Dep: WPRM-Präsenz beeinflusst Listener (Hinweis, kein Hard-Req)
- `Field_Renderer`-Hinweis: ggf. neuer Feldtyp (Provider-Order-Sortierung) → CLAUDE.md Architecture-Note triggert

### § 12 — Lifecycle
- Wann registriert Listener Hooks (Konstruktor/`init`)
- Provider-Instanziierung: lazy bei Purge-Action vs. bei Modul-Load (BRIEF — Pre-Decision offen)
- **Vollständige Pipeline-Order:** `save → Listener → Debounce → do_action → cache-bridge → Pause → Provider-Iteration → Retry/Log`
- „loaded ⟺ active" (Modul-Kanon § 9)

### § 13 — Edge-Cases
- WPRM inaktiv (Hook fehlt) · Provider unavailable (Creds fehlen / Env nicht erkannt) · leere `urls` → full-purge-Policy
- **Bulk-Edit-Sturm:** `ctx.urls` > Threshold → chunked bis 5× Threshold, dann Full-Purge-Fallback (Pre-Decision #9 + Caveat: destruktiv → laut loggen, per-Provider opt-in erwägen)
- CF-`tags` ohne Enterprise → graceful skip
- Queue überlebt WP-Restart, User vergisst Pause → 24h-Auto-Resume
- Multi-CPT; i18n/Mehrsprachigkeit (URL-Varianten je Sprache)

### § 14 — Anti-Patterns (Provider darf NICHT)
- Fatal werfen / Kette blocken · Credentials loggen · synchron ohne Timeout blocken
- Cross-Module-Direktimport / Suite referenzieren (Splitting!)
- Eigenes Bootstrapping außerhalb `module.php`

### § 15 — Dependencies
- Extern: api.bunny.net, api.cloudflare.com (Creds); RunCloud lokal (keine Creds)
- WP-HTTP-API; **kein** Suite-`class_exists`, **keine** Cross-Module-Klassen
- WPRM optional (nur Listener-Hook)

### § 16 — Bekannte Risiken / Annahmen (Langfrist, bleiben NACH BRIEF-Approval offen — KEINE Pre-Approval-Decisions)
- **Annahme (bewusst, nicht jetzt gelöst):** synchron-im-Request reicht für MVP; async-via-Cron erst wenn API-Limits erreicht werden
- **Annahme (unverifiziert):** Cloudflare `purge_cache` ist idempotent
- **Annahme (unverifiziert):** BunnyCDN `purgeCache` 2xx = tatsächlich invalidiert (kein Verify-Callback)
- **Annahme:** RunCloud Nginx-`PURGE` auf eigene URL ist no-op/sicher, wenn kein FastCGI-Cache aktiv
- **Risiko:** Rate-Limits bei großen Bulk-Operationen trotz Threshold-Chunking
- **Risiko:** Pause-Queue-Option-Write-Race (s. § 10, akzeptiert)

### § 17 — Smoke-Test-Definition
- LogOnly feuert auf `save_post` → error_log-Eintrag
- Provider-Toggles; CF/Bunny: **Mock im Plugin-Smoke (CI)**, echter API-Call im Manual-Smoke am Live-Server (Pre-Decision #10)
- RunCloud `is_available()` HTTP-Probe auf `runcloud-test`-Server (Item-1)
- Failure-Isolation (ein Provider down → andere laufen)
- Debounce-Skip; Pause→Queue→Resume-Modal-Pfade; 24h-Auto-Resume
- phpcs / `php -l` / debug.log frei / `wp plugin check` 0 neue Findings

### § 18 — Konkrete Datei-Liste
- `src/Cache/Purge_Context.php` (**neu, shared, außerhalb Modul** — Scope-Erweiterung über Modul-Files hinaus)
- `src/Cache/Purge_Result.php` (**neu, shared** — Return-Value-Object, Pre-Decision #2)
- `modules/cache-bridge/`:
  - `manifest.php`, `module.php`
  - `Hooks/Listener.php` *(Platzierung tentativ — zentraler vs. pro-Hook + Lifecycle final im BRIEF)*
  - `Providers/{ProviderInterface,LogOnly,BunnyCDN,Cloudflare,RunCloud}.php`
  - `Pause/{Controller,AdminBar,Queue}.php` (Pre-Decision #11 — Pause/-Subordner, drei Klassen)
  - `Admin/Settings.php`
- ggf. `assets/admin/` für Admin-Bar/Resume-Modal-JS

---

## Teil B — OPEN DECISIONS (Tracking-Liste, Pre-Approval-Diskussionspunkte)

Diese Punkte sind die **Pre-Approval-Diskussion** für den Volltext-BRIEF. Status-Spalte zeigt, ob Pre-Decision (Teil C) sie schon vorläufig auflöst.

| # | Sektion | Frage | Pre-Decision (Teil C) |
|---|---|---|---|
| 1 | § 3 | Provider-Default-Reihenfolge | #1 |
| 2 | § 4 | Return-Typ: Result-Object vs. Array | #2 |
| 3 | § 5 | Timeout-Wert + per-Provider-Override | #3 |
| 4 | § 5 | 429-Handling | #4 (+ Caveat Cap ≤3s) |
| 5 | § 6 | URL-Berechnungs-Scope pro Post | #5 (+ Caveat Archive-Pagination) |
| 6 | § 6/§ 9 | Filter `register_listeners` ja/nein | #6 |
| 7 | § 10 | Queue-Storage: Option vs. Transient | #7 (+ Caveat Write-Race) |
| 8 | § 8 | Auto-Resume-Trigger | #8 |
| 9 | § 13 | Bulk-Threshold-Strategie + Full-Purge-Fallback | #9 (+ Caveat Destruktivität) |
| 10 | § 17 | Smoke Mock vs. echt | #10 |
| 11 | § 18 | Pause-Files-Subordner-Layout | #11 |
| — | § 12 | Provider-Instanziierung lazy vs. eager | offen (kein Pre-Decision) |

---

## Teil C — Pre-BRIEF-Decisions (Status: zu bestätigen am Anfang nächster Session)

User-Vorschläge aus Session 2, **nicht binding** — BRIEF-Diskussion final. Claude hat alle 11 als Arbeitsannahmen approved, mit 4 Recon-Caveats (markiert ⚠).

1. **Provider-Default-Order:** LogOnly → RunCloud → BunnyCDN → Cloudflare *(Origin vor Edge — Origin-Purge zuerst, damit Edge frisch re-fetcht; Log_Only first für Audit-Trail)*.
2. **Return-Typ:** `final readonly class Purge_Result` (success/code/message/provider_id/duration_ms), konsistent zum `Purge_Context`-Pattern.
3. **Timeout:** 12s default, per-Provider via Constant überschreibbar.
4. **429-Handling:** 1 Retry mit `Retry-After`-Respekt. ⚠ **Caveat:** nur bis Cap (≤ 3s) respektieren — sonst kein in-request-Sekunden-Block, skip+log.
5. **URL-Scope pro Post:** Permalink + Home + relevante Archive (Kategorie/Tag). ⚠ **Caveat:** Archive auf erste Seite je Archiv begrenzen (Pagination-Explosion ↔ Bulk-Threshold vermeiden).
6. **register_listeners-Filter:** JA (minimal-Aufwand, max. Erweiterbarkeit ohne BRIEF-Update).
7. **Queue-Storage:** Option autoload=no (einfacher als Transient, WP-Restart-stabil). ⚠ **Caveat:** Option-Write-Race bei gleichzeitigen Saves dokumentieren (read-modify-write, durch Debounce gemildert).
8. **Auto-Resume-Trigger:** Timestamp-Check beim nächsten Purge + WP-Cron-Backup (Hybrid, Cron-Fail-Safe).
9. **Bulk-Threshold:** provider-spezifisch via Interface-Methode `get_bulk_threshold()`; chunked bis 5× Threshold, dann Full-Purge-Fallback. ⚠ **Caveat:** Full-Purge ist destruktiv (widerspricht Pause-„Notfall"-Einstufung) → laut loggen + per-Provider opt-in erwägen (Default eher „skip + loud log" als stilles Nuke).
10. **Smoke Mock vs. echt:** Mock im Plugin-Smoke (CI), echter API-Call im Manual-Smoke am Live-Server (API-Limits schützen).
11. **Pause-Files-Subordner:** `Pause/`-Subordner — `Controller.php` + `AdminBar.php` + `Queue.php` (saubere Kapselung, FS-safe).

---

## Nächste Session — Einstieg
1. CLAUDE.md + dieses `BRIEF-SKETCH.md` lesen.
2. 11 Pre-Decisions bestätigen (oder einzeln anpassen) + die 4 Caveats entscheiden.
3. `src/Cache/Purge_Context.php` + `Purge_Result.php` Scope bestätigen (shared, außerhalb Modul).
4. **Volltext-`BRIEF.md` nach 18-Sektion-Struktur schreiben** → User-Approval → DANN Code (§ 12.3).
