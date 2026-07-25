# SmartPick V3 - System Architecture

```text
smartpick/
├── docs/                 # Arkitektur, domænemodel, flows, events, regelmotor, ADR'er
│   └── adr/
├── domain/               # Forretningslogik pr. bounded context (namespace: SmartPick\Domain\*)
│   ├── storage/
│   ├── inventory/
│   ├── planning/
│   ├── picking/
│   ├── packing/
│   ├── shipping/
│   ├── labor/
│   └── analytics/
├── application/          # Services, Handlers, Commands & Queries (CQRS) — tom endnu, se ROADMAP
├── infrastructure/       # AI-providers, logging, migration bridge (namespace: SmartPick\Infrastructure\*)
│   ├── ai/
│   ├── logging/
│   └── migration/
├── interfaces/           # Contract-interfaces (namespace: SmartPick\Interfaces)
├── events/                # Event Bus (namespace: SmartPick\Events)
├── rules/                # Regelmotor (namespace: SmartPick\Rules)
├── legacy/               # Bekræftet døde/placeholder-klasser, bevaret men flagget til oprydning
├── core/                 # Dolibarr-konventionsfiler — MÅ IKKE namespaces, se ADR-005
│   ├── modules/           # Modulregistrering (modSmartPick.class.php)
│   ├── triggers/          # Dolibarr trigger auto-discovery
│   └── hooks/             # Dolibarr hook auto-discovery
├── admin/, api/, script/  # Dolibarr entry-points — global namespace, bruger `use` til at referere domæneklasser
├── sql/, templates/, img/, lib/
├── composer.json          # Classmap-autoload (se ADR-005 for hvorfor ikke PSR-4 sti-opslag)
└── smartpick_autoload.php # Fallback-autoloader hvis Composer ikke er kørt på miljøet
```

## Namespace-konvention

Alle klasser under `domain/`, `application/`, `infrastructure/`, `events/`, `rules/`, `interfaces/` bruger `SmartPick\...`-namespaces der følger mappestrukturen (f.eks. `domain/picking/SmartPickQueue.class.php` → `namespace SmartPick\Domain\Picking;`).

**Filer under `core/`, samt `admin/*.php`, `api/*.php`, `script/*.php` forbliver unamespaced** — de er Dolibarrs egne integrationspunkter, og Dolibarr scanner dem efter fast filsti + globalt klassenavn. Se `docs/adr/ADR-005-Dolibarr-Namespace-Boundary.md`.

## Autoload-strategi

Klassefilerne følger Dolibarrs `.class.php`-navnekonvention med lowercase mappenavne, hvilket ikke er kompatibelt med strikt PSR-4 sti-opslag (der kræver `ClassName.php` og case-matchende mapper). Løsningen er **Composer classmap-autoload** (`composer.json`), suppleret af en selvstændig fallback-autoloader (`smartpick_autoload.php`) til miljøer uden Composer. Se `docs/adr/ADR-005-Dolibarr-Namespace-Boundary.md` for detaljer.

Eksisterende entry-points bruger fortsat eksplicitte `require_once` + `use`-statements (verificeret med `php -l` på hele kodebasen) — autoloaderen er et supplement til ny kode, ikke en forudsætning for at det eksisterende virker.
