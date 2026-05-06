# TODO

Offene Punkte und spätere Erweiterungen (ohne BunnyCDN in diesem Dokument).

## Core

- Optional: „Daten bei Deinstallation löschen“ als Einstellung (readme.txt dokumentieren)
- Später: Filter für Modul-Liste / Modul-Infos (z. B. für Pro/Agentur-Kennzeichnung)

## Module

- BunnyCDN-Modul in separatem Prompt implementieren
- BunnyCDN: Optional Cache-Control-Header für anonymes Frontend (public, max-age) – HIT erfordert aber Edge Rule „Override Cache Time“ für text/html
- Weitere Module nach Bedarf

## Entwicklung

- Unit-Tests (PHPUnit) optional
- CI (PHPCS, Lint) optional
