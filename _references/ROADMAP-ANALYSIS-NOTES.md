# Roadmap-Analyse-Notizen – Performance- und Management-Plugin-Vorbilder

> **Zweck dieser Datei:** Externe Plugins dokumentieren, deren Features für 
> zukünftige Module von `depeur-food` (oder ein separates Depeur-Plugin) 
> relevant sind. Diese Datei dient als Input für eine spätere gezielte 
> Analyse-Session mit Claude Code im Plan Mode. Sie ist **nicht** Teil von 
> Phase A oder Phase B des aktuellen Sprints – es ist Material für später.

## Wie diese Datei nutzen

In einer zukünftigen Session, wenn du an Performance-Modulen oder einem 
zweiten Depeur-Plugin arbeiten willst, pastest du Claude Code folgenden 
Auftrag:

```
Wir öffnen eine separate Analyse-Phase für die nächste 
Plugin-Generation/Module. Bitte lies _references/ROADMAP-ANALYSIS-NOTES.md 
komplett, dann lies stichprobenartig die jeweiligen Plugin-Quellen aus 
_references/plugin-references/wp-rocket/ und die in der Notes-Datei 
verlinkten Kadence-Docs.

Erstelle ein Architektur-Brief-Dokument (analog wordpress.md § 12 
Pre-Implementation-Review) das pro analysiertem Plugin folgendes 
strukturiert:
1. Welche Features sind in unseren Stack übernehmenswert
2. Welche sind explizit NICHT übernehmenswert (mit Begründung)
3. Welche existieren schon in unseren Modulen (Doppelarbeit vermeiden)
4. Vorgeschlagene Modul-Mapping in depeur-food oder neue Plugin-Struktur
5. Architektur-Risiken / Lizenz-Konflikte

Bleib im Plan Mode, ich entscheide danach was wirklich in die Roadmap geht.
```

Damit hat Claude einen klaren Auftrag plus genug Context für eine 
produktive Plan-Session.

---

## 1. Kadence Performance

### Was es ist
Performance-Modul, das **Teil des kostenlosen Kadence Blocks Plugins** ist 
(seit ~Anfang 2026, davor Beta). Kein eigenes Plugin – wird in Kadence 
Blocks aktiviert.

### Konzept
- **Per-Post-Optimierung statt Site-Wide:** Du optimierst Posts/Pages 
  einzeln über den "Run Optimizer"-Button in der All-Posts-Liste.
- **Komplementär zu Caching-Plugins:** Ersetzt explizit keinen Page-Cache, 
  ergänzt ihn.
- **Bulk-Aktionen** für mehrere Posts gleichzeitig.
- **Optimizer-Status-Filter** in der Posts-Liste (Optimized, Outdated, 
  Excluded, Not Optimized).
- **Auto-Re-Optimization** bei Inhaltsänderung im Editor.
- **Markiert "Outdated"** wenn Änderungen außerhalb des Editors passieren 
  (Header/Footer-Änderungen, Kadence Elements, globale Layout-Changes).

### Konkrete Features
1. **Lazy Load Background Images** – CSS-Background-Bilder unterhalb des 
   Folds werden lazy geladen.
2. **Smart Image Optimization** – richtige Bildgröße pro Screen 
   (Mobile/Desktop liefert nicht dieselbe Auflösung).
3. **Lazy Load HTML Below the Fold** – nicht-kritische HTML-Sektionen 
   verzögert nachladen, sobald gescrollt wird.

### Was für Depeur lernen wir daraus
- **Per-Post-Optimierung als UX-Modell** ist clever: du gibst dem User 
  granulare Kontrolle ohne "alles oder nichts"-Schalter. Das passt zu 
  unserem Toggle-Pattern.
- **"Optimizer Status"-Spalte in All-Posts** ist eine elegante 
  Admin-UI-Lösung, die wir für eigene Module übernehmen sollten – z.B. 
  "Schema-Status" beim schema-engine-Modul oder "Cache-Status" beim 
  cache-bridge.
- **Outdated-Erkennung bei externen Änderungen** ist eine echte Stärke. 
  Wenn unser schema-engine pre-generierte JSON-LD-Snippets cached, sollten 
  wir analog markieren wenn Author-Meta sich ändert.
- **Lazy Load HTML below the fold** ist eine fortgeschrittene Technik, die 
  WP Rocket nicht out of the box hat. Könnte ein eigenständiges Modul 
  werden.

### Was NICHT übernehmen
- Kadence Performance ist an Kadence Blocks gekoppelt (analysiert die 
  Block-Struktur). Wir wollen post-type-agnostisch und block-agnostisch 
  bleiben.
- Die Bulk-Action-UI ist Kadence-spezifisch hübsch, aber die 
  Persistenz-Logik (was wird wo gespeichert wenn ein Post optimiert ist) 
  brauchen wir nicht 1:1 zu kopieren.

### Quellen
- Docs: https://www.kadencewp.com/help-center/docs/kadence-blocks/getting-started-with-kadence-performance/
- Kadence Blocks Plugin: https://wordpress.org/plugins/kadence-blocks/

---

## 2. Kadence Central

### Was es ist
**Multi-Site-Management-Dashboard**. Konzeptionell verwandt mit ManageWP, 
MainWP, InfiniteWP. Nicht "ein Plugin pro Site" sondern ein zentraler Hub 
für Agencies/Entwickler, die viele Sites verwalten.

Verfügbar im **Kadence Elite Plan**, nicht im Free oder Pro.

### Konzept
- **Zentrale Schaltzentrale** für mehrere WordPress-Sites.
- **Site-spezifische Plugins** auf jeder verwalteten Site, die mit dem 
  zentralen Dashboard kommunizieren.
- **Integrationen mit Drittanbieter-Tools** (Google Analytics, Search 
  Console, Gravity Forms, Yoast).

### Konkrete Features
1. **Backup-Management** zentral für alle Sites.
2. **Security-Integration** (Kadence Security Whitelist, Lockout-Release 
   remote).
3. **Google Analytics-Übersicht** – Traffic, Landing-Pages, 
   Referrer-URLs.
4. **Google Search Console-Übersicht** – Suchanalyse, Crawl-Errors, 
   Sitemap-Status.
5. **Yoast SEO-Status-Checker** – SEO-Status für Content pro Site.
6. **Gravity Forms-Übersicht** – Forms und Responses zentral sichtbar.
7. **WP101-Tutorial-Integration** – Onboarding-Videos für Client-Sites.
8. **Two-Factor-Auth** für den Central-Login.
9. **Maintenance-Reports** (Pro-Feature) – wieviel Arbeit in 
   Site-Wartung floss.
10. **Site-Notes und Tags** zur Organisation.

### Was für Depeur lernen wir daraus
**Das ist eine völlig andere Kategorie als depeur-food.** Eher Vorbild für 
ein zukünftiges **"Depeur Hub"** – Mac Mini oder ein eigener Server, der 
zentral deine 5-10 Sites (einfachanders.es, alkipedia.de, Kunden-Sites) 
überblickt.

Konkret übernehmen für ein zukünftiges Hub-Projekt:
- **Phone-Home-Pattern:** kleines Client-Plugin auf jeder Site, das per 
  REST API Status/Stats an den zentralen Hub meldet. Architektonisch sauber 
  und gut für unseren modularen Stack adaptierbar.
- **Maintenance-Reports** könnten ein nettes Verkaufsargument für 
  Agency-Kunden sein – "monatlicher Report, was Depeur an deiner Site 
  gemacht hat".
- **SEO-Status-Aggregation** über mehrere Sites ist genau das was du als 
  Solo-Operator brauchst, wenn du nicht in jede Site separat einloggen 
  willst.

### Was NICHT übernehmen
- Kadence Central will eine **All-in-One Wartungsplattform** sein. Das ist 
  Scope-Creep für uns. Wir brauchen keine eigene Backup-Lösung – RunCloud 
  macht das, BorgBackup macht das.
- Die Integrationen (Yoast, Gravity Forms) sind 
  Tool-Anbindungen-pro-Tool – wenn wir sowas mal angehen, dann mit eigenem 
  fokussiertem Scope (nur Recipe-Sites, nur Cocktail-Sites).
- Two-Factor-Auth und Login-Hardening: gibt's bei RunCloud + Cloudflare 
  Zero Trust schon, nicht doppelt bauen.

### Roadmap-Überlegung
**Wenn überhaupt, ist "Depeur Hub" ein eigenständiges Projekt, kein 
Modul in depeur-food.** Bei sowas würden wir architektonisch zwei Teile 
brauchen:
1. **Client-Plugin** (oder Modul in depeur-food: "hub-client") – sendet 
   Daten an Hub.
2. **Hub-Server** – eigenes WordPress-/Laravel-/Node-Projekt mit 
   Dashboard-UI.

Erst angehen, wenn du wirklich >5 Sites parallel betreust und der 
Wartungsaufwand sich lohnt.

### Quellen
- WP.org Plugin-Page: https://wordpress.org/plugins/ithemes-sync/ (alter 
  Name ihrer Reihe, ist mittlerweile Kadence Central)
- Liquid Web Übersicht: https://www.liquidweb.com/software/kadence/add-ons/

---

## 3. WP Rocket

### Was es ist
Kommerzielles Performance-Plugin, eines der ausgereiftesten am Markt. 
Liegt bereits in `_references/plugin-references/wp-rocket/` (gitignored, 
nur als Code-Referenz).

### Architektur-Patterns die wir lernen können

**1. Page Cache (statischer HTML-Cache)**
- HTML-Snapshot wird beim ersten Request erstellt und unter `/wp-content/cache/wp-rocket/{host}/{path}/index.html` abgelegt.
- Bedient via .htaccess-Rewrite oder PHP-Loader, je nach Server.
- Logged-in Users + Cookies (woocommerce, wp_postpass, comment_author) 
  bypassen Cache.
- Cache-Invalidierung via `save_post`, `comment_post`, 
  `wp_update_term`-Hooks.

**Übernehmen für Depeur:** kein eigenes Page-Cache-Modul (RunCache + 
Cloudflare APO machen das), aber die **Invalidierungs-Logik** ist genau 
das was unser `cache-bridge`-Modul braucht. Pattern: zentrale Liste von 
Triggern, Map auf URLs, Provider-Dispatch.

**2. Critical CSS Generation**
- Async Background-Job (via Action-Scheduler) holt sich pro Post-Type das 
  Layout, schickt es an externen Service (criticalcss.com API oder 
  äquivalent), speichert das Ergebnis als Datei.
- Im Frontend wird das Critical CSS inline im `<head>` eingebunden, der 
  Rest der Stylesheets deferred via `preload` mit JS-Fallback.

**Übernehmen für Depeur:** zur **Build-Time** generieren, nicht zur 
Laufzeit. Wir können WP Rocket's API-Aufruf-Patterns übernehmen, aber den 
Trigger auf "User klickt Generate-Button im Admin" verlagern (Background 
Worker bauen wir nicht). Ergebnis als Theme-Datei in 
`kadence-child/assets/critical-css/`.

**3. Lazy Load (Bilder, iframes, Videos)**
- IntersectionObserver-basiert, Fallback für ältere Browser.
- Conditional: nicht für above-the-fold Images (LCP-relevant).
- Eigene JS-Library `lazyload.min.js` (~5 KB).

**Übernehmen für Depeur:** native HTML `loading="lazy"` reicht heute, 
außer für Background-Images (die liefert native HTML nicht). Für 
Background-Images können wir IntersectionObserver-Pattern aus WP Rocket 
1:1 lernen.

**4. Delay JS / Defer JS**
- JS-Skripte werden mit `data-rocket-deferred` markiert, erst nach 
  User-Interaktion (mousemove, click, scroll, touchstart, keydown) geladen.
- Eigene Liste pro-Site konfigurierbarer Scripts.

**Übernehmen für Depeur:** sehr nützlich für Werbe-Skripte (Quality Media 
Network) und Analytics-Skripte (Plausible, GA4). Das wäre ein eigenes 
Modul **`recipe-extras` → `JsDelay.php`** oder ein eigenständiges Modul 
**`script-loader`**. Code-Pattern aus WP Rocket adaptieren.

**5. Database Optimization**
- Cron-Job, der `wp_posts_revisions`, `wp_options` (transients), 
  `wp_comments` (spam/trash) periodisch aufräumt.
- UI in Plugin-Settings, manuelle "Optimize Now"-Buttons.

**Übernehmen für Depeur:** vermutlich nicht. Das macht WP-Optimize besser 
und es ist Off-Topic für ein Content-/Recipe-Plugin.

**6. CDN-Integration (URL-Rewrite)**
- Filter `the_content` und Asset-URLs, ersetzt `wp-content/uploads/...` 
  durch `cdn.domain.com/...`.
- Konfigurierbare Exclude-Liste.

**Übernehmen für Depeur:** BunnyCDN macht das im depeur-wp-suite-Modul 
schon. Cloudflare APO ersetzt es eh. Nicht doppelt bauen.

**7. Preload (Sitemap-basiert)**
- Liest die XML-Sitemap, ruft jede URL einmal selbst auf um den Cache zu 
  warmen.
- Cron-getriggert + manueller "Preload Now"-Button.

**Übernehmen für Depeur:** Cache-Warming nach Purge ist genau das was 
unser `cache-bridge` machen sollte. Das WP Rocket-Pattern ist die richtige 
Vorlage. Nach einem Purge feuert `cache-bridge` einen Worker, der die 
betroffenen URLs nochmal via `wp_remote_get` aufruft → warmer Cache für 
den nächsten echten User.

### Was NICHT aus WP Rocket übernehmen
- **Eigenen Page-Cache:** RunCache + Cloudflare reichen.
- **License-Validation und Phone-Home:** WP Rocket ruft jeden Tag 
  api.wp-rocket.me an. Wollen wir nicht.
- **Heartbeat-Reduction:** macht Perfmatters einfacher und sauberer.
- **Cloudflare-API-Integration in WP Rocket:** unsere depeur-wp-suite hat 
  schon eine, wir nutzen die.

### Lizenz-Hinweis
WP Rocket ist **kommerzielles Closed-Source-Plugin unter GPL-kompatibler 
Lizenz**. Wir dürfen den Code studieren und Patterns lernen, aber 
**dürfen keinen Code 1:1 kopieren** (auch nicht "ähnlich genug"). 
Eigene Implementierung mit eigener Architektur ist OK – Pattern lernen 
und neu schreiben.

### Quellen
- Lokale Quelle: `_references/plugin-references/wp-rocket/`
- Offizielle Site: https://wp-rocket.me/

---

## Vorgeschlagene Modul-/Plugin-Mapping für die Roadmap

Diese Sektion gewichtet, was sich für Depeur lohnen könnte. Klassifikation:

**🟢 Direkt für depeur-food als Modul (Phase 2 nach dem Bootstrap):**
- **`html-lazyload`** – Lazy Load HTML below the fold (Pattern von Kadence 
  Performance, mit IntersectionObserver-Logik von WP Rocket).
- **`background-image-lazyload`** – CSS-Background-Bilder lazy laden 
  (Kadence Performance hat es, native HTML kann es nicht).
- **`js-delay`** – Skripte erst nach User-Interaktion laden (WP Rocket 
  Pattern). Wichtig für deinen Werbe-Stack.
- **`critical-css-builder`** – Build-Time Critical CSS Generation als 
  Admin-Tool. NICHT zur Laufzeit. Ergebnis im Theme.
- **`status-columns`** – Generischer Mechanismus für 
  "Optimizer-Status"-Spalten in All-Posts-Lists (Kadence-Pattern). 
  Wiederverwendbar pro Modul (Schema-Status, Cache-Status, 
  Lazy-Load-Status).

**🟡 Eigenes Plugin / Phase 3+ Projekt:**
- **`depeur-hub`** – Multi-Site-Management à la Kadence Central. Eigenes 
  Repo, eigene Architektur. Erst angehen wenn echter Wartungsschmerz 
  da ist.
- **Cache-Warming-Worker** (könnte auch in depeur-wp-suite als Modul, je 
  nachdem wo es architektonisch besser sitzt).

**🔴 Nicht übernehmen:**
- Eigener Page-Cache (RunCache + Cloudflare ist genug).
- Eigene Database-Optimization (Off-Topic).
- Eigene CDN-URL-Rewriting (BunnyCDN-Modul + APO macht das).
- 1:1-Klon von Kadence Performance (an Kadence Blocks gekoppelt, wollen 
  wir nicht).

---

## Offene Fragen für die Roadmap-Session

1. Soll `critical-css-builder` ein Modul in depeur-food sein oder besser 
   eine Theme-Funktionalität in `kadence-child/inc/performance.php`?
2. Ist `js-delay` per Modul-Toggle aktivierbar oder per-Post (Kadence 
   Pattern)?
3. Bei `depeur-hub`: WordPress als Hub-Backend oder besser ein eigenes 
   Laravel-/Node-Projekt? (Wenn nicht WP, fällt der Lock-in weg.)
4. Wann lohnt sich `depeur-hub`? Bei wie vielen verwalteten Sites?
5. Lizenz-Konflikt-Check: alles was wir aus WP Rocket lernen, müssen wir 
   eigenständig neu schreiben. Wer prüft das im Self-Review-Hook?

---

## Notizen / Updates

- **2026-XX-XX:** Datei initial angelegt mit Analyse zu Kadence 
  Performance, Kadence Central, WP Rocket.
- Updates hier eintragen wenn sich Plugin-Stand ändert oder neue 
  Erkenntnisse aus tatsächlicher Analyse-Session kommen.
