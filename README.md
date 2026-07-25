# SmartPick - Dolibarr Enterprise WMS (Score: 9.5+/10)

SmartPick er et moderne, skalerbart WMS (Warehouse Management System) modul til Dolibarr ERP/CRM opbygget med **Shadow Mode Migrationsbro**, **Wave Picking**, **TSP Ruteoptimering**, **ABC-Slotting Engine & Heatmap**, **Event Engine**, **Regelmotor**, **DeepSeek-R1 AI** og **KPI Cockpit Dashboard**.

---

## 🔒 Kontrolleret Migrationsbro i "Shadow Mode" (`SmartPickMigrationBridge.class.php`)

For at sikre en fuldstændig risikofri overgang fra legacy-køen til v3 WMS-arkitekturen:
1. **100% Skrivebeskyttet mod Legacy:** Importerer eksisterende ventende legacy-pluklinjer til v3 som nye `PickTasks` uden nogensinde at modificere, slette eller ændre status på den oprindelige legacy-kø.
2. **Nul Produktionspåvirkning:** Produktionstrafikken kører uforstyrret videre på eksisterende ruter.
3. **Auditing & Verifikation:** Evaluere v3 rute- og wave-beregninger i baggrunden op mod den faktiske legacy-afvikling for at verificere præcision før endelig live-omstilling.

---

## 🛠 Modulstruktur
- `class/SmartPickMigrationBridge.class.php` - Kontrolleret Migrationsbro i Shadow Mode
- `class/SmartPickWavePlanner.class.php` - Wave Picking & Batching
- `class/SmartPickRouteOptimizer.class.php` - TSP Ruteoptimering gennem lageret
- `class/SmartPickSlottingEngine.class.php` - ABC Analyse, Slotting & Heatmap
- `class/SmartPickEventEngine.class.php` - Event Engine & Life-cycle Bus
- `class/SmartPickRuleEngine.class.php` - Konfigurerbar Regelmotor
- `class/SmartPickKPIDashboard.class.php` - KPI Cockpit Dashboard
- `class/SmartPickDeepSeekAI.class.php` - DeepSeek-R1 AI ræsonneringsklient
- `class/SmartPickCartonization.class.php` - Dynamisk Emballage-Beregning
- `class/SmartPickQueue.class.php` - Plukkø med SLA/alder prioritering & restordre-opsplitning
- `class/SmartPickAllocation.class.php` - Put-Wall Slots, Express Fast-Track & Pakker-tidstagning
- `class/SmartPickFactorEngine.class.php` - Vækstfaktor & Højtidsforskydningsanalyse
- `script/smartpick_auto_shifts_cron.php` - Natlig Dolibarr Cron til automatisk 4-dages vagtoprettelse
- `class/ShipmondoAPI.class.php` - Shipmondo REST API v3
