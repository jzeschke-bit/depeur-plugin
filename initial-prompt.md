# Initial Prompt – Depeur Food Suite Bootstrap (Plan Mode)

> Diesen Text in Claude Code im Projektordner eingeben, idealerweise mit `claude --plan` gestartet oder direkt im Plan Mode (`Shift+Tab` zum Wechseln).

---

Hi Claude. Ich starte ein neues Projekt: ein Plugin (`depeur-food`) und ein Kadence-Child-Theme, die zusammen die Basis für meine WordPress-Content-Sites werden. Du bist hier mein Senior WordPress Developer und arbeitest mit mir über mehrere Sessions hinweg an diesem Projekt.

## Kontext

- Ich betreibe eine kleine Webagentur (Depeur UG) und mehrere eigene Content-Projekte, primär im Food/Rezept- und Cocktail-Bereich.
- Stack: Hetzner-Server, RunCloud, WordPress, Kadence Theme, Cloudflare. Ich nutze für alle Sites bereits ein Plugin namens **Depeur WP Suite**, das Cache-Management (Cloudflare/RunCloud/BunnyCDN), Security und Utility-Features bündelt – mit einem Toggle-System pro Feature.
- Über die Jahre habe ich diverse Mini-Plugins gebaut, die jetzt in das neue **Depeur Food** Plugin konsolidiert werden sollen.
- Im aktuellen Projektordner findest du:
  - `plugins/depeur-wp-suite/` – das bestehende Utility-Plugin (Cache, Security). Architektur-Vorlage für `depeur-food` (gleiches Toggle-Pattern, gleiche Coding-Standards). NICHT direkt anfassen, nur als Referenz lesen.
  - `plugins/depeur-food/` – Placeholder, das neue Plugin das wir bauen.
  - `themes/kadence-child/` – noch leer, wird in Phase B von dir gebaut basierend auf dem Legacy-Theme.
  - `_references/legacy-plugins/` – meine eigenen alten Mini-Plugins (`category-schema`, `my-favorite-posts-plugin`, `rest-api-wprm`, `spotlight-subscribe`), deren Funktionalität in `depeur-food` konsolidiert werden soll. Lies sie ALLE und identifiziere Duplikate, Lücken und Verbesserungspotenzial bevor du planst.
  - `_references/legacy-themes/alkipedia/` – aktueller Stand meines bestehenden Kadence-Child-Themes. Quelle für Customizations, die ins neue `themes/kadence-child` übernommen werden sollen.
  - `_references/plugin-references/` – Drittanbieter-Plugins (BunnyCDN, RunCloud Hub, WP Rocket) als Architektur-/Pattern-Inspiration. Nicht editieren, nur lesen, nicht in den Production-Code übernehmen.
- **`wordpress.md`** im Projektroot ist die verbindliche Standards-Referenz. Lies sie als allererstes und referenziere sie für jede Architekturentscheidung.

## Test-Umgebung

- wp-env läuft lokal unter `http://localhost:8888` (admin/password initial, danach mit Live-Site-Credentials nach Content-Import).
- Aktivierter Stack: WordPress + Kadence (Theme) + Kadence Pro + Kadence Blocks + Kadence Blocks Pro + WP Recipe Maker (Free + Premium) + Rank Math (Free + Pro) + Smush Pro + ACF (Free) + Depeur WP Suite + Depeur Food (Placeholder).
- Test-Content stammt von der Site `einfachanders.es` (Standard-`post`-Typ, keine CPTs). ACF-Field-Definitionen aus dem Alkipedia-Setup sind installiert, aber nicht auf allen Posts mit Werten befüllt. Das ist absichtlich der "frische Onboarding"-Zustand und testet die Graceful-Degradation-Anforderung aus `wordpress.md`.
- **Erstes Rollout-Ziel** des fertigen Plugins ist `einfachanders.es` (Standard-Posts), **zweites Ziel** `alkipedia.de` (mit Custom Post Type für Cocktails). Die Plugin-Architektur muss von Anfang an mit beiden Setups klarkommen → siehe Post-Type-Agnostik in `wordpress.md`.
- ContentPass-Cookie-Banner und Werbe-Plugins sind bewusst NICHT installiert auf der Test-Site. Sie laufen auf den Live-Sites zusätzlich und können dort Cache-Verhalten beeinflussen – beim Cache-Management-Design mitdenken auch wenn lokal nicht testbar.

## Ziel dieses Projekts

Am Ende sollen zwei Komponenten stehen:

1. **`depeur-food` Plugin** – konsolidiert die Funktionalität aller Plugins aus `_references/legacy-plugins/`, ergänzt um sinnvolle neue Features für Content-/Recipe-Sites (Schema-Erweiterungen, Cache-Hooks für WPRM, ggf. Pinterest-Integration, Custom Post Meta, etc.). Architektur und Toggle-Pattern identisch zu Depeur WP Suite. Greift wo möglich auf Depeur WP Suite Funktionalität zurück (Cache-API), statt sie zu duplizieren.
2. **`kadence-child` Theme** – aufgeräumt, performant, mit den Customizations aus `_references/legacy-themes/alkipedia/` + den Speed-Optimierungen aus `wordpress.md` Sektion 4.4. Theme macht nur Darstellung, Plugin macht Funktionalität.

## Workflow-Erwartungen

### Phase A – Discovery (jetzt, im Plan Mode)

1. **Lies `wordpress.md` komplett.** Das ist deine Bibel.
2. **Inventarisiere den Projektordner.** Verschaffe dir vollständigen Überblick:
   - Studier `plugins/depeur-wp-suite/` ausführlich – Architektur, Toggle-System, Coding-Stil. Das wird der Referenzrahmen für `depeur-food`.
   - Lies jedes Plugin in `_references/legacy-plugins/`: was macht es, welche Hooks nutzt es, welche Datenstrukturen, welche Settings, wo benutzt es ACF, wo fragt es Post-Types ab.
   - Lies das Legacy-Theme in `_references/legacy-themes/alkipedia/`: welche Customizations, welche Performance-Hacks bereits drin, welche Schema-Erweiterungen, welche Recipe-Templates.
   - Skim `_references/plugin-references/` nur auf Architektur-Pattern (du musst nicht den ganzen Code lesen, nur die Hauptklassen und wie sie ihre Module strukturieren).
3. **Erstelle/aktualisiere `CLAUDE.md`** mit: Projektzielen, gefundenen Komponenten, identifizierten Konsolidierungs-Möglichkeiten, offenen Fragen an mich, geplanter Modul-Struktur des neuen Plugins, geplanter Theme-Struktur.
4. **Erstelle einen Implementierungsplan** mit `TodoWrite` als geordnete Task-Liste. Granularität: jeder Task sollte in einer Session abschließbar sein. Reihenfolge: erst Plugin-Bootstrap mit Settings/Toggle-System, dann Feature für Feature aus den Legacy-Plugins migrieren, dann Theme.
5. **Stoppe und präsentiere mir** Inventar + Plan + offene Fragen, bevor du irgendetwas implementierst.

### Phase B – Implementierung (nach meinem Approval)

Pro Task:
1. Implementieren gemäß `wordpress.md`.
2. Lokal testen: `phpcs --standard=WordPress` auf geänderten Dateien, `php -l` Syntax-Check, wenn möglich `wp plugin check` im wp-env.
3. Wenn Tests grün: git commit mit Conventional-Commit-Message.
4. CLAUDE.md updaten: erledigte Tasks abhaken, neue Findings festhalten, nächsten Task ankündigen.
5. Erst dann zum nächsten Task.

### Self-Review-Hook (kritisch!)

Bevor du mir gegenüber jemals "Task abgeschlossen" oder "Bereit zum Review" sagst, durchläufst du **explizit und sichtbar** die Checkliste in `wordpress.md` Sektion 11. Listenform, jeden Punkt benannt, mit ✓ oder ✗ und Begründung. Wenn ein Punkt nicht erfüllt ist: kein Ready-Statement, sondern Behebung.

### Cross-Session-Konsistenz

Du wirst mit mir über mehrere Sessions arbeiten. Damit nichts verloren geht:
- **CLAUDE.md ist deine externalisierte Erinnerung.** Aktualisiere sie am Ende jeder bedeutsamen Aktion.
- **Git-Log ist die Source of Truth** für was bereits gebaut ist.
- **TodoWrite-Liste ist der aktuelle Sprint** und wird in CLAUDE.md gespiegelt.
- **Offene Fragen an mich sammelst du in CLAUDE.md** unter `## Open Questions`. Beantworte ich, streichst du raus.
- Bei Session-Start: Lies `wordpress.md` neu (kann sich geändert haben), dann CLAUDE.md, dann `git log --oneline -20` für Recent Activity.

### Live-Tests

Du hast Bash-Zugriff. Konkret kannst und sollst du nutzen:
- **Lokal:** `wp-env start`, `wp-env run cli wp ...`, `phpcs`, `php -l`.
- **Remote (RunCloud-Test):** SSH-Alias `runcloud-test` ist möglicherweise eingerichtet (frag mich falls unsicher). Falls ja, kannst du:
  - Push: `rsync -avz --delete plugins/depeur-food/ runcloud-test:/home/runcloud/webapps/test/wp-content/plugins/depeur-food/`
  - Aktivieren: `ssh runcloud-test "wp plugin activate depeur-food --path=/home/runcloud/webapps/test"`
  - Smoke-Test: `curl -sI https://test.depeur.de/ | head -1`
  - Logs: `ssh runcloud-test "tail -50 /home/runcloud/logs/test/php-error.log"`
- **Entscheidungsregel:** Lokal testen ist Standard. Remote-Push erst nach meinem Approval pro Feature, nicht für jeden Mini-Commit.

### Was du NICHT tust

- Keine ungefragten Architektur-Wechsel ("ich finde Composer wäre besser").
- Keine externen Dependencies ohne Rückfrage (kein `composer require xyz/abc` ohne mich zu fragen).
- Kein Code im `main`-Branch ohne sauberen Commit.
- Keine `git push` ohne mein Go.
- Keine Cloud-Service-Calls die Geld kosten (OpenAI, etc.) ohne explizite Zustimmung.
- Keine Edits an Dateien in `_references/` – das sind Read-Only-Quellen für Inspiration und Studium.

## Erste Aktion

Starte mit Phase A. Erste konkrete Schritte:
1. `cat wordpress.md` und voll verinnerlichen.
2. `find plugins themes _references -type f -name "*.php" | head -100` für Inventar-Überblick.
3. Lies die Hauptdateien jedes bestehenden Plugins/Themes (mindestens die jeweilige `*.php`-Hauptdatei, README falls vorhanden, und schau auf die Ordnerstruktur).
4. Erstelle `CLAUDE.md` initial.
5. Präsentiere mir das Inventar + die offenen Fragen + den Vorschlag für die Modul-Struktur des neuen Plugins und die Theme-Struktur.

Du gibst mir am Ende dieses ersten Durchlaufs:
- Den Inhalt der frischen `CLAUDE.md` zur Bestätigung.
- Eine `TodoWrite`-Liste mit den ersten 5-10 Tasks.
- Eine Liste mit Open Questions, ohne deren Antwort du nicht sinnvoll weiterarbeiten kannst.

Los geht's. Bleib im Plan Mode bis ich explizit zur Implementierung freigebe.
