# SmartPick V3 - Central Warehouse Flows

Hvert flow beskrives i tre lag: **Target flow** (ønsket slutbillede), **Nuværende dækning** (hvad koden rent faktisk gør i dag), og **Gap** (hvad der mangler). Dette holder dokumentet ærligt i forhold til `docs/DOMAIN_MODEL.md`.

---

## Flow 1 — Receiving (Inbound)

**Target:**
```
Truck → Dock → Receiving → QC → Putaway → Storage
```

**Nuværende dækning:** Ingen. Varemodtagelse foregår i dag udelukkende i Dolibarrs eget indkøbsordre-/lagermodul. `SmartPickMigrationBridge` rører kun den udgående (pluk-)side, aldrig indgående.

**Gap:** Hele flowet mangler. Første kodede leverance i dette domæne bør være `ReceivingService` der lytter på Dolibarrs `LINEORDER_SUPPLIER_...`-events (eller triggers) og opretter en `PutawayTask`.

---

## Flow 2 — Storage / Slotting

**Target:**
```
Inventory → Slotting → ABC Analyse → Replenishment → Cycle Count
```

**Nuværende dækning:**
- ABC-analyse: `SmartPickSlottingEngine.class.php`
- Replenishment: `SmartPickReplenishment.class.php`
- Cycle Count: `SmartPickCycleCount.class.php`

Disse tre klasser eksisterer og fungerer isoleret, men er ikke kædet sammen som ét flow — der er ingen orkestrering der f.eks. automatisk trigger `ReplenishmentTask` når `SmartPickSlottingEngine` opdager lavt niveau i en hurtig-plukzone.

**Gap:** Orkestrering mellem de tre eksisterende klasser via events (`LowStockDetected` → trigger replenishment).

---

## Flow 3 — Order Fulfilment (kerneflowet)

**Target:**
```
Sales Order → Planner → Wave → Queue → Pick → Pack → Ship
```

**Nuværende dækning — dette er det mest modne flow i systemet:**

1. **Sales Order → Planner/Wave:** `SmartPickWavePlanner->createPickingWave($warehouse_id, $zone, $max_orders)` opretter en wave-reference
2. **Wave → Queue:** Rækker indsættes i `llx_smartpick_queue` (status `pending`)
3. **Queue → Pick:** `SmartPickQueue->markItemDefectiveOrMissing()` håndterer undtagelser under pluk; `SmartPickRouteOptimizer` beregner TSP-rute gennem lageret; scanning sker via `api/scan.php`
4. **Pick → Pack:** `SmartPickAllocation->startPackingOrder()` / `finishPackingOrder()` — inkl. automatisk ekspres-detektion (én-tote-ordrer) og kartonanbefaling via `SmartPickCartonization`
5. **Pack → Ship:** `ShipmondoAPI.class.php` booker fragt; carrier vælges af `SmartPickRuleEngine->evaluateOrderRules()`; resultat gemmes i `llx_smartpick_shipments`

**Gap:**
- Wave-trinnet er ikke persistent (se `DOMAIN_MODEL.md` punkt 4) — waves kan ikke slås op eller rapporteres på i dag
- Der er intet automatisk hop fra "Wave oprettet" til "Queue-rækker indsat" — det er sandsynligvis stadig to manuelle/separate kald
- Ingen `WaveReleased`-event der reelt frigiver en wave til gulvet

---

## Flow 4 — Returns

**Target:**
```
Customer → Inspection → Decision → Repair / Restock / Scrap
```

**Nuværende dækning:** Ingen kode, ingen tabeller. Dette er det mindst modne domæne.

**Gap:** Hele flowet skal designes og bygges fra bunden i Sprint 2. Foreslået minimumsflow til v1: `ReturnReceived` → manuel inspektionsformular → `ReturnDecision` (restock/scrap) → hvis restock: opret `PutawayTask` tilbage til lager.

---

## Flow 5 — Automation (fremtidigt)

**Target:**
```
Task → Robot → Conveyor → Put Wall → Operator
```

**Nuværende dækning:** Put-Wall-konceptet findes delvist (se Flow 3 / Packing), men robot/conveyor-integration findes slet ikke.

**Gap:** Lavest prioritet — ingen fysisk automation at integrere med endnu. Behold som Sprint 4-vision, byg ikke kode her før der er en konkret automation-partner.

---

## Flow 6 — Labor / Shift Planning

**Target (ikke i oprindelig vision, men eksisterer allerede i kode og bør dokumenteres):**
```
Demand forecast → Auto-generér vagter → Bemanding bekræftes → Performance-log
```

**Nuværende dækning:**
- `script/smartpick_auto_shifts_cron.php` — natlig cron der auto-opretter 4 dages vagter
- `SmartPickShiftPlanner.class.php` — vagtplanlægning
- `llx_smartpick_shifts` / `llx_smartpick_user_shifts` — persistente tabeller
- `llx_smartpick_user_logs` — performance-log pr. medarbejder (qty plukket, vægt løftet, varighed)

Dette flow er faktisk næsten færdigt og bør fremhæves som et forbillede for hvordan de andre domæner skal modnes.

---

## Prioritering af flow-arbejde (Sprint 2 forslag)

| Prioritet | Flow | Begrundelse |
|---|---|---|
| 1 | Order Fulfilment — gør Wave persistent | Kerneflowet, størst forretningsværdi, mangler kun ét led |
| 2 | Storage — orkestrér eksisterende klasser | Klasserne findes allerede, "kun" sammenkædning mangler |
| 3 | Returns | Findes slet ikke, men er forretningskritisk for et komplet WMS |
| 4 | Receiving | Findes slet ikke, men Dolibarr dækker det delvist i mellemtiden |
| 5 | Automation | Ingen fysisk integration at bygge mod endnu — udskyd |
