# SmartPick V3 - System Architecture

```text
smartpick/
├── docs/             # Arkitektur, flows, decision records (ADR) og roadmap
├── domain/           # Bounded contexts (Inbound, Storage, Picking, Packing, Shipping, Returns, Labor)
├── application/      # Services, Handlers, Commands & Queries (CQRS)
├── infrastructure/   # Repositories, AI (DeepSeek-R1), Carriers (Shipmondo), Scanners
├── interfaces/       # Contract Interfaces & Dependency Injection
├── events/           # Event Bus & Event Catalog
├── rules/            # Generisk Regelmotor
└── workers/          # Background Cron & Queue Workers
```
