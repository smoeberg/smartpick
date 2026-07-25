# SmartPick V3 - Domænemodel (Bounded Contexts)

## Bounded Context Entiteter:
- **Warehouse**: Fysisk lagerenhed.
- **Zone / Location / Bin**: Fysiske lagerlokationer.
- **LPN (License Plate Number / Container)**: Unik stregkodet kasse, palle eller plukkerkasse.
- **PickTask / Wave**: Enkelt plukopgave og batch-bølge.
- **Worker / Equipment**: Lagerarbejder (koblet til Dolibarr `User`) og udstyr.
- **Shipment**: Forsendelsesobjekt koblet til Shipmondo.
