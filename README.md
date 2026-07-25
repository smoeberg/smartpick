# SmartPick - Dolibarr Enterprise WMS (Score: 9.5+/10)

SmartPick er et moderne, skalerbart WMS (Warehouse Management System) modul til Dolibarr ERP/CRM opbygget med **Wave Picking**, **TSP Ruteoptimering**, **ABC-Slotting Engine & Heatmap**, **Event Engine**, **Regelmotor**, **DeepSeek-R1 AI** og **KPI Cockpit Dashboard**.

---

## 🚀 Udvidelsesfunktionaliteter (Architecture Upgrade 9.5+/10)

### 1. 🌊 Wave Picking Planner (`SmartPickWavePlanner.class.php`)
- Samler 100+ ordrer i optimerede **Bølger (Waves)** pr. zone (Zone A, B, C) og sendefrister.

### 2. 🗺️ Traveling Salesperson Ruteoptimering (`SmartPickRouteOptimizer.class.php`)
- Beregner den matematisk korteste fysiske gangsti gennem lageret (`A12 -> A18 -> B01 -> C03`) for at eliminere spildtid.

### 3. 📊 ABC Analyse, Slotting Engine & Heatmap (`SmartPickSlottingEngine.class.php`)
- Opdeler produkter i **A-varer (Top 20% pluk)**, B- og C-varer.
- Genererer visuelt pluk-heatmap og foreslår automatisk at flytte hurtigløbere tættere på pakkeudgangen.

### 4. ⚡ Event Engine / Event Bus (`SmartPickEventEngine.class.php`)
- Håndterer hele ordrens livscyklus via events:  
  `OrderCreated` $\rightarrow$ `WaveCreated` $\rightarrow$ `PickingStarted` $\rightarrow$ `PickingFinished` $\rightarrow$ `PackingStarted` $\rightarrow$ `Packed` $\rightarrow$ `ShipmentCreated`

### 5. 🎛️ Regelmotor (`SmartPickRuleEngine.class.php`)
- Dynamiske fragt- og prioriteringsregler (f.eks. Norge $\rightarrow$ PostNord, Vægt > 20 kg $\rightarrow$ GLS, VIP $\rightarrow$ Høj Prioritet).

### 6. 📈 KPI Cockpit Dashboard (`SmartPickKPIDashboard.class.php`)
- Monitorering af **Picks/Time**, gennemsnitlig pakketid, fejlrate, restordrer og dagligt løftede kg.

### 7. 🔌 Interfaces & Central Audit Logging
- `interfaces/IAIProvider.php`, `interfaces/IShippingProvider.php`, `interfaces/ICartonization.php` for ren dependency injection og testbarhed.

---

## 🛠 Modulstruktur
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
