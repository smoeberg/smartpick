# SmartPick V3 - Vision & Strategy

SmartPick er ikke blot et "lager-modul" til Dolibarr; det er en fuldblods **Warehouse Execution Platform (WEP)**.

## Ansvarsfordeling (ERP vs. WMS)

```text
                Dolibarr ERP
────────────────────────────────────
Produkter · Kunder · Ordrer · Indkøb · Faktura · Økonomi

             │
             │ Events & REST API
             ▼

        SmartPick WMS Orchestrator
────────────────────────────────────
Inbound · Storage · Slotting · Picking · Packing · Shipping · Returns · Labor · AI Digital Twin
```

### Kerne-Principper
1. **Dolibarr ejer ERP-data**: Kunder, salgsordrer, købsordrer og finans.
2. **SmartPick ejer Lager-Execution**: Alle fysiske og logistiske lagerprocesser, opgaver, ruter, LPN, Put-Wall og automation.
3. **Event-Driven & Domain-Driven (DDD)**: Løs kobling med over 100 asynkrone events.
4. **Warehouse Digital Twin**: En kontinuerlig AI-simuleret model af lagerets fysiske tilstand.
