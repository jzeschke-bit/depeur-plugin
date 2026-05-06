# QA – Manuelle Tests

Kurze Checkliste vor Freigabe.

## Installation & Aktivierung

- [ ] Plugin aktivieren → kein PHP-Fehler (WP_DEBUG true)
- [ ] Menü „Depeur Suite“ erscheint im Admin
- [ ] Dashboard zeigt Plugin-Version und „Aktive Module: 0“ (oder mehr)

## Module

- [ ] Unter „Module“ wird das Beispiel-Modul (_ExampleModule) angezeigt
- [ ] Modul aktivieren, „Module speichern“ → Erfolgsmeldung
- [ ] Dashboard zeigt nun 1 aktives Modul
- [ ] Modul deaktivieren, speichern → Dashboard zeigt 0 aktive Module

## Einstellungen

- [ ] Tab „Allgemein“: Logging-Checkbox speichern → bleibt gespeichert
- [ ] Beispiel-Modul aktivieren, Tab „Beispiel-Modul“ erscheint
- [ ] Text, Checkbox, Select ändern und speichern → Werte bleiben

## Support

- [ ] Support-Seite öffnen: Systembericht wird angezeigt (WP, PHP, Theme, Plugins, Suite, Module)
- [ ] „Bericht kopieren“ → Inhalt in Zwischenablage

## BunnyCDN-Modul

- [ ] Modul „BunnyCDN Integration“ unter Module aktivieren und speichern
- [ ] Einstellungen → Tab „BunnyCDN“: Status-Box (API Key, Pull Zone, RunCloud, Letzter Purge)
- [ ] API Key (maskiert), Pull Zone ID, CDN-Hostname eintragen, BunnyCDN aktivieren, speichern
- [ ] „CDN Cache leeren“ klicken → Bestätigung → Redirect mit Meldung (Erfolg/Debounce/Fehler)
- [ ] Beitrag oder Seite speichern → Purge wird ausgelöst (Debounce 30 s)
- [ ] Mit RunCloud: RunCloud-Sync aktivieren → bei RunCloud Purge All wird BunnyCDN mitgeleert (konzeptionell wie Cloudflare-Integration)

## Autoload Inspector (Modul)

- [ ] Modul „Autoload Inspector“ unter Module aktivieren und speichern
- [ ] Menü „Depeur Suite → Autoload Inspector“ öffnen
- [ ] Tab **Autoload**: Tabelle mit Optionen (autoload=yes), Größe, Zuordnung, Warnstufe; „Select all“-Checkbox wählt alle Zeilen; Einzel-Löschen-Link mit Bestätigung
- [ ] Suche, Min. Bytes, Prefix-Filter setzen, „Filter anwenden“ → Ergebnis passt
- [ ] Pagination: Bei vielen Optionen Seitennummern unten, Klick wechselt Seite
- [ ] Tab **Alle Optionen**: wie Autoload, zusätzlich Filter „autoload“ (Alle / Nur yes / Nur no)
- [ ] **Einzel löschen**: Eine Option löschen → Bestätigung → Redirect mit Erfolgsmeldung
- [ ] **Bulk löschen**: Mehrere Checkboxen wählen, „Ausgewählte löschen“ → Bestätigung → summarische Meldung
- [ ] Tab **Verdächtig**: Nur Optionen mit Warnstufe ≥ Medium (z. B. Plugin deaktiviert/nicht installiert, theme_mods, groß/unbekannt)
- [ ] Tab **Regeln & Ignorieren**:
  - [ ] Prefix-Mapping: Zeilen Prefix → Plugin-Slug bearbeiten; „Regeln speichern“ → Werte bleiben
  - [ ] Plugin-Scan: Vorschläge angezeigt; Checkboxen „Übernehmen“ bei gewünschten Vorschlägen setzen, „Regeln speichern“ → Vorschläge in Prefix-Map übernommen
  - [ ] Ignorierte Prefixe / Ignorierte Optionen: Textareas (ein Eintrag pro Zeile), speichern → in Verdächtig-Ansicht berücksichtigt
  - [ ] **Bulk-Löschen nach Prefix**: Prefix eingeben, „Preview anzeigen“ → Anzahl + erste Optionen; Checkbox „Ich bestätige…“ setzen, „Jetzt löschen“ → Bestätigung → Meldung
  - [ ] UI-Einstellungen: Optionen pro Seite, Mindestgröße (Bytes) speichern → auf anderen Tabs wirksam

## Deinstallation

- [ ] Plugin deaktivieren → keine Fehler
- [ ] Plugin löschen (Uninstall) → Optionen mit Prefix `depeur_wp_suite_` sind gelöscht
