# ADR-005: Namespace-grænse mellem SmartPick-domænekode og Dolibarr-integration

## Status
Accepteret og implementeret (Sprint 1, repo-restrukturering)

## Kontekst
Under restruktureringen til domænedrevet mappestruktur opstod spørgsmålet: skal *alle* PHP-filer i modulet have namespaces, inklusive Dolibarr-integrationsfilerne (modulregistrering, triggers, hooks)?

Dolibarr scanner selv `core/modules/`, `core/triggers/` og `core/hooks/` efter faste filnavne-mønstre og instantierer klasser via deres **globale** (unamespaced) klassenavn. Dette er ikke konfigurerbart fra modulets side — det er en fast konvention i Dolibarrs kernearkitektur.

Derudover viste det sig at klassefilerne konsekvent bruger `.class.php`-endelsen og lowercase mappenavne (f.eks. `domain/picking/`), mens namespaces af PHP-konvention er PascalCase (`SmartPick\Domain\Picking`). Det betyder strikt PSR-4 sti-opslag (som Composer normalt bruger til at finde `Namespace\ClassName` → `Namespace/ClassName.php`) ikke kan bruges uden enten at omdøbe alle fysiske filer (høj risiko, rører alt) eller acceptere et alternativ.

## Beslutning
1. **To zoner:**
   - `domain/`, `application/`, `infrastructure/`, `events/`, `rules/`, `interfaces/`, `legacy/` → namespaced under `SmartPick\...`
   - `core/`, `admin/`, `api/`, `script/`, `module_descriptor.php` → forbliver i globalt namespace, uændret filstruktur, Dolibarr-konventionel
2. **Composer classmap i stedet for PSR-4 sti-opslag** — `composer.json` bruger `"classmap": [...]` som scanner filerne uanset navnekonvention/case, frem for at kræve en fysisk sti der matcher namespacet præcist.
3. **Selvstændig fallback-autoloader** (`smartpick_autoload.php`) til miljøer hvor `composer install` ikke er kørt — almindeligt på delt Dolibarr-hosting. Denne bygger sit eget classmap ved at scanne filerne for `namespace`- og `class`-deklarationer.
4. Eksisterende entry-points beholder deres eksplicitte `require_once`-kald (med opdaterede stier) + `use`-statements, som en robust fallback der ikke er afhængig af at autoloaderen er inkluderet korrekt.

## Konsekvenser
- **Positivt:** Forretningslogik får ren, organiseret namespace-struktur uden at bryde Dolibarrs auto-discovery af moduler/triggers/hooks.
- **Positivt:** Virker både med og uden Composer i produktionsmiljøet.
- **Negativt:** To autoload-mekanismer (Composer classmap + fallback) er mere at vedligeholde end én. Accepteret som nødvendig kompleksitet given Dolibarr-værtsmiljøers varierende Composer-understøttelse.
- **Verifikation:** Alle PHP-filer er syntaks-linted (`php -l`) efter flytning, og autoloaderen er testet med et simuleret Dolibarr-filtræ der bekræfter korrekt resolution af alle 9 flyttede klasser samt uændret funktion af `modSmartPick.class.php` og trigger-klassen.
