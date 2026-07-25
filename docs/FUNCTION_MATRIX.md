# SmartPick V3 - Funktionel Ansvarsmatrix (Dolibarr vs. SmartPick)

| Domæne | Dolibarr ERP Ansvar | SmartPick WMS Execution Ansvar |
| :--- | :--- | :--- |
| **Produkter** | Stamdata, priser, moms, ERP koder | Mål ($L \times B \times H$), vægt, stabelbarhed, ABC klassifikation, hazard |
| **Lager** | Finansiel lagerværdi & lagerrum | Bounded zones, rækker, fag, bins, LPN containers, Put-Wall slots |
| **Inbound** | Oprettelse af Købsordre (PO) | Truck dock, modtagekontrol, QC inspection, Putaway routing |
| **Ordrer** | Salgsordre oprettelse & fakturering | Wave planning, TSP ruteoptimering, PickTasks, Put-Wall konsolidering |
| **Shipping** | Salgsfaktura & betaling | Shipmondo booking, vægt/dimension check, fragtmandskriterier via Regelmotor |
