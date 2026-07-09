# Kadence Child (Depeur Food)

Schlankes Kadence-Child-Theme. **Es trägt nur Präsentation.** Alle Fach-, Query-,
Migrations-, REST- und SEO-Logik liegt im Plugin `depeur-food`. Dieses Theme löst das
Alt-Theme **„Alkipedia 3.0"** (functions.php ~928 Zeilen) ab.

> **Merksatz:** Kommt es aus einer Datenbank-Abfrage, einem REST-Call, einem hreflang-Tag
> oder einem Meta-Feld → **Plugin**. Ist es eine CSS-Regel oder eine Kadence-Template-Part-
> Ausgabe → **Theme (hier)**.

---

## 1) Was ist HIER im Theme (und warum)

| Datei | Inhalt | Warum Theme statt Plugin |
|---|---|---|
| `style.css` | Tag-Pillen, Author-Box-Layout, Rundungen, Sidebar-/Archiv-Kosmetik, Fremd-Plugin-Kosmetik (WPRM/Rank Math/Social Warfare) | CSS ist per Definition Präsentation. |
| `functions.php` | 3 Glue-Hooks: (1) Child-CSS laden, (2) Kadence „Ähnliche Beiträge" auf Plugin-Post-Types erweitern, (3) Sprachumschalter im Footer platzieren | Alle drei hängen an **Kadence-spezifischen** Hooks — ein theme-agnostisches Plugin darf sich daran nicht binden. |
| `template-parts/content/entry_loop_thumbnail.php` | Kadence-Loop-Bild **+ Herz-Button** (`[thumbnail_favorite_button]`) | Kadence-Template-Parts kann nur ein Child-Theme über den gleichen Pfad ersetzen. Der Button selbst kommt aus dem Plugin. |

Jede CSS-Regel und jede Funktion hat einen `WOFÜR / WARUM / PRÜFEN`-Kommentar im Code.

---

## 2) Was kommt vom PLUGIN `depeur-food` (Dedup — bewusst NICHT mehr im Theme)

Diese Funktionen lagen im Alt-Theme in der `functions.php` und wurden ins Plugin verlagert.
Das Theme ruft sie nur noch per Shortcode/Hook auf oder profitiert automatisch davon.

| Alt-Theme (functions.php) | Jetzt im Plugin-Modul | Zugriff aus dem Theme |
|---|---|---|
| `LanguageLink()` (hreflang im `<head>`) | `language-selector` | automatisch (Modul hängt selbst in `wp_head`) |
| `lang_tag()` (Footer-Umschalter) | `language-selector` → `[df_language_switcher]` | `functions.php` platziert den Shortcode im Footer |
| Favoriten-Herz + Like-Zähler + AJAX | `favorites` → `[thumbnail_favorite_button]`, `[df_favorites_archive]` | Template-Part + Shortcode |
| Multi-Taxonomie-Query, Pagination-Rewrites, „Seite 1 = Content + Vorschau" | `category-pages` → `[df_category_page]` (+ Auto-Render geflaggter Seiten) | Shortcode / Auto-Render, keine Theme-Templates nötig |
| „Was koche ich heute" (Tag-Filter + AJAX) | `category-pages` → `[df_recipe_filter]` (REST) | Shortcode |
| Tag-Gruppen (`tag_group` Term-Meta, Admin-Spalte) | `category-pages` (Step 5, teils offen) | — |
| CPTs in Standard-Archiven (`pre_get_posts`: Tag/Autor/…) | `archive-types` | automatisch (post-type-agnostisch, ADR-4) |
| Schema-Anreicherung (Autor, Rank Math, WPRM) | `schema-engine` | automatisch |
| Newsletter-Inserter + App-Promo (+ Grau-Overlay) | `newsletter` → `[df_newsletter]`, `[df_app_promo]` | automatisch / Shortcode |
| Gleich hohe Loop-Karten (equal-height) | Core `Frontend_Assets` → `assets/df-loop-grid.css` | automatisch (frontend-weit) |

**Deshalb NICHT im Child-CSS doppeln:** hreflang, Sprachumschalter, Favoriten-JS/CSS,
Newsletter-Overlay, equal-height-Karten. Die liefert das Plugin.

---

## 3) Bewusst (noch) NICHT portiert — offene Lücken

Ehrlich dokumentiert, damit nichts still verloren geht:

- **`rest_allow_anonymous_comments`** (Alt-Theme) — bewusst weggelassen; nur nötig, falls
  anonyme Kommentare über REST gebraucht werden.
- **Weitere Template-Part-Overrides** (`entry_author.php`, `entry_tags.php`) — das CSS dafür
  ist in `style.css` schon vorhanden. Die Template-Parts selbst werden erst ergänzt, wenn
  das Grundgerüst freigegeben ist (siehe Abschnitt 5).

---

## 4) Installation / Aktivierung

Dieses Theme wird **im Plugin mitgeliefert** (Ordner `plugins/depeur-food/bundled-theme/kadence-child/`).
Es muss NICHT manuell hochgeladen werden — der Migrations-Assistent installiert es.

1. Voraussetzung: Parent-Theme **Kadence** installiert (nicht zwingend aktiv).
2. Voraussetzung: Plugin `depeur-food` aktiv mit Modulen `favorites`, `language-selector`,
   `schema-engine`, `newsletter`, `category-pages`.
3. **Depeur Food → Migrations-Assistent**, Abschnitt „4. Theme installieren & aktivieren":
   - **„Child-Theme installieren"** kopiert die Dateien nach `wp-content/themes/kadence-child/`.
   - **„Child-Theme aktivieren"** schaltet um und übernimmt Menüs/Logo (`theme_mods`) vom Alt-Theme.
4. Danach **Seiten-Cache leeren** (RunCache/Cloudflare). Bei Bedarf Permalinks neu speichern.

> **Live-Vorsicht:** Neuer `<link>` aufs Child-CSS steckt in gecachtem HTML — bei
> RunCache/Cloudflare den Seiten-Cache leeren, sonst lädt der Browser das alte Theme-CSS.

> **Manuell (Fallback):** Ordner `bundled-theme/kadence-child/` nach `wp-content/themes/kadence-child/`
> kopieren (Trailing-Slash am rsync-Quellpfad!), dann normal aktivieren.

---

## 5) Wie man weitere Kadence-Template-Parts überschreibt

1. Original aus dem **Parent-Theme** kopieren: `wp-content/themes/kadence/<pfad>`.
2. Unter identischem Pfad in dieses Child legen: `kadence-child/<pfad>`.
3. **Nur** die nötige Zeile ändern und mit `// <<< EINZIGE ÄNDERUNG` markieren (Muster:
   `entry_loop_thumbnail.php`), damit ein späterer Kadence-Update-Abgleich leicht bleibt.
4. Plugin-Ausgaben immer per `shortcode_exists()`-Guard einbinden, damit das Theme ohne
   das jeweilige Plugin-Modul nicht bricht.

Bekannte Kandidaten (CSS liegt schon vor):
- `entry_author.php` — Jobtitel-Zeile aus User-Meta (`author_jobtitle`).
- `entry_tags.php` — `#`-Präfix als `<span class="tag-hash">` im Single-Post.
