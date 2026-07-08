# BRIEF — `acf-cleanup` (Migrations-Aufräumer)

**Status:** v1.0 (2026-07-08) · Schema 1.1 · Klassifikation: geschäftslogik-tragend + **destruktiver Pfad** → § 12-pflichtig.

## 1. Zweck (ein Satz)
Nachdem das Plugin die Feld-Anlage übernommen hat, entfernt dieses Modul über eine geführte Admin-Seite **nur die redundanten ACF-Feldgruppen-DB-Duplikate**, die das Plugin bereits als `local` (PHP) liefert — mit Vorschau, Auto-Backup und Cap+Nonce-Gate.

## 2. Problem / Kontext
Nach dem Live-First-Pivot legt das Plugin die ACF-Feldgruppen per `acf_add_local_field_group` (Key-Reuse-Override) an. Die gleichnamigen **DB-Gruppen werden dadurch überschattet** und sind reine Duplikate → sie verstopfen die ACF-Oberfläche („Chaos"). Das manuelle Aufräumen per wp-cli (Session 2026-07-08) ist der Deployment-Schritt, den dieses Tool **wiederholbar und sicher** macht — insbesondere für den Live-Cutover, ohne dass jemand destruktive SQL-/wp-cli-Befehle auf Produktion tippt.

## 3. Sicherheits-Modell (die eingefrorene Invariante)
- **Nur `acf-field-group`-Posts werden gescannt/gelöscht.** CPT-/Taxonomie-Definitionen sind `acf-post-type`/`acf-taxonomy` und werden **nie** angefasst (Brandmauer gegen das „Cocktails 404"-Szenario).
- **Löschbar ⟺ „covered":** der Key der DB-Gruppe ist aktuell als **lokale (PHP) Gruppe** registriert (das Plugin überschattet ihn schon). Nicht abgedeckte Gruppen (z. B. *Rezeptkategorie*, *Tag Addons*) → **`keep`**, nie zum Löschen angeboten.
- **Backup zuerst:** vor jeder Löschung Export der betroffenen Gruppen **inkl. Feld-Posts** als JSON nach `uploads/depeur-food-backups/`.
- **Gate-Reihenfolge:** `current_user_can('manage_options')` → `check_admin_referer` → **Server-seitiger Re-Scan** (der geposteten Liste NICHT vertrauen) → Backup → Löschen nur der neu berechneten `covered`-Menge.
- **Dry-Run ist Default:** Die Seite zeigt zuerst nur die Klassifikation; Löschen erfordert expliziten Button + Nonce.
- **Idempotent:** Nach dem Aufräumen zeigt ein erneuter Lauf „nichts zu entfernen".

## 4. Was es bewusst NICHT tut
- Keine Fremd-Plugins deaktivieren (Handarbeit, ein Klick in wp-admin; das Tool zeigt höchstens den Status).
- Keine CPT-/Taxonomie-Definitionen anfassen.
- Keine nicht-abgedeckten Feldgruppen löschen.
- Keine Feld-**Werte** in `wp_postmeta`/`wp_termmeta`/`wp_usermeta` verändern (nur Gruppen-*Definitionen*).

## 5. Dateien
- `manifest.php` — Metadaten (kein `slug`-Key, Modul-Kanon 4). Default-OFF.
- `module.php` — Bootstrap; instanziiert im Admin `Admin\Cleanup_Page`.
- `Support/Scanner.php` — reine Klassifikation: `report()` → `{ available, covered[], keep[] }`; `local_group_keys()`, `db_groups()`.
- `Support/Backup.php` — JSON-Export der Gruppen + Feld-Posts (rekursiv) via `WP_Filesystem`.
- `Admin/Cleanup_Page.php` — Submenu unter `AdminMenu::MENU_SLUG`, Vorschau-Render + `admin_post`-Handler (Gate → Backup → Delete → PRG-Notice).

## 6. Cross-Plugin-Disziplin
Einzig erlaubte Core-Imports: `AdminMenu::MENU_SLUG` (Submenu). Klassifikation nur über ACF-Public-Funktionen (`acf_get_local_field_groups`/`acf_get_field_groups`) + WP-Post-API. Kein Import von `PostTypeRegistry`/`ModuleManager`/anderen Modulen.

## 7. Smoke
- Autoloader löst `Depeur\Food\Modules\AcfCleanup\Support\Scanner` auf (`AcfCleanup`→`acf-cleanup`).
- `php -l` clean, phpcs Exit 0.
- Vorschau klassifiziert korrekt (auf bereits-bereinigter Staging: `covered=[]`, `keep=[Rezeptkategorie, Tag Addons]`).
- Delete-Pfad (auf Site mit Duplikaten): Backup-Datei entsteht, nur `covered` gelöscht, `keep` unangetastet, `acf_get_field_groups()` zeigt die Keys weiter als `local:yes`.
