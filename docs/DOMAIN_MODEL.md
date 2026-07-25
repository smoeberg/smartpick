# SmartPick V3 - Domænemodel (Bounded Contexts)

Dette dokument definerer SmartPicks kernesprog (Ubiquitous Language). Hver bounded context ejer sine egne entiteter, aggregates og value objects. Ingen context må læse eller skrive direkte i en anden context's tabeller — al kommunikation sker via events eller applikationsservices.

**Status-nøgle:** ✅ Implementeret i nuværende skema/kode · 🔶 Delvist implementeret (findes som felt, ikke som selvstændig entitet) · ⬜ Ikke implementeret endnu (målarkitektur)

---

## 1. Inbound (Modtagelse)

**Ansvar:** Modtagelse af varer fra leverandør til lager, kvalitetskontrol og placering.

| Entitet | Type | Status | Noter |
|---|---|---|---|
| `Dock` | Entity | ⬜ | Fysisk modtagedock. Ikke i nuværende skema. |
| `ReceivingTask` | Aggregate Root | ⬜ | Modtagekontrol pr. leverance |
| `QCInspection` | Entity | ⬜ | Kvalitetskontrol-resultat |
| `PutawayTask` | Aggregate Root | ⬜ | Instruktion om placering efter modtagelse |
| `GoodsReceiptLine` | Value Object | ⬜ | Varelinje modtaget mod PO |

**Nuværende kode:** `SmartPickMigrationBridge.class.php` importerer legacy-plukkø, men der findes endnu ingen dedikeret Inbound-modtageflow — Dolibarrs eget PO-modul (`commande_fournisseur`) dækker i dag varemodtagelsen uden SmartPick-involvering.

**Services (mål):** `ReceivingService`, `QCService`, `PutawayService`

---

## 2. Storage (Lagerlokationer & Slotting)

**Ansvar:** Fysisk lagerstruktur, slotting-strategi og heatmap-analyse.

| Entitet | Type | Status | Noter |
|---|---|---|---|
| `Warehouse` | Aggregate Root | 🔶 | Findes som `fk_warehouse` (Dolibarr's eget warehouse-modul), ingen SmartPick-udvidelse |
| `Zone` | Entity | ⬜ | Ikke som tabel — kun implicit i `SmartPickWavePlanner` (`zone` som string-parameter) |
| `Location` (Rack/Bin) | Value Object | ✅ | `loc_rack`, `loc_bin` (varchar) på `llx_smartpick_queue` |
| `ABCClass` | Value Object | ⬜ | Beregnes af `SmartPickSlottingEngine`, men gemmes ikke persistent endnu |
| `Heatmap` | Read Model | ⬜ | Mål: aggregeret visning fra `SmartPickSlottingEngine` |

**Nuværende kode:** `SmartPickSlottingEngine.class.php` (ABC-analyse & heatmap-logik)

**Services (mål):** `SlottingService`, `LocationService`

**Gap:** `Zone`, `Rack`, `Bin` er i dag frie tekststrenge, ikke normaliserede entiteter med kapacitet/regler. Dette er den vigtigste strukturelle mangel før Storage-domænet kan blive selvstændigt.

---

## 3. Inventory (Lagerbeholdning & Cyklustælling)

**Ansvar:** Præcision af lagersaldi, genopfyldning, cyklustælling.

| Entitet | Type | Status | Noter |
|---|---|---|---|
| `StockSnapshot` | Entity | 🔶 | Via Dolibarrs `MouvementStock` (bruges direkte af `SmartPickQueue`) |
| `CycleCountTask` | Aggregate Root | 🔶 | `SmartPickCycleCount.class.php` |
| `ReplenishmentTask` | Aggregate Root | 🔶 | `SmartPickReplenishment.class.php` |
| `StockAdjustment` | Value Object | ✅ | Registreres via `MouvementStock->_create()` med `origin = 'smartpick_defective'` |

**Nuværende kode:** `SmartPickCycleCount.class.php`, `SmartPickReplenishment.class.php`

**Services (mål):** `InventoryService`, `CycleCountService`, `ReplenishmentService`

---

## 4. Planning (Wave- & Ordreplanlægning)

**Ansvar:** Omdanne salgsordrer til udførbare plukbølger.

| Entitet | Type | Status | Noter |
|---|---|---|---|
| `Wave` | Aggregate Root | 🔶 | `SmartPickWavePlanner->createPickingWave()` — genererer `wave_id` (`WAVE-YYYYMMDD-HHMMSS`), men persisteres ikke i eget skema endnu, kun returneret som array |
| `PickTask` | Aggregate Root | ✅ | `llx_smartpick_queue` — kernetabellen |
| `SeasonalFactor` | Value Object | ✅ | `SmartPickFactorEngine.class.php` (vækstfaktor / højtidsforskydning) |

**Nuværende kode:** `SmartPickWavePlanner.class.php`, `SmartPickFactorEngine.class.php`

**Services (mål):** `WavePlanningService`, `DemandForecastService`

**Gap:** `Wave` mangler en persistent tabel (`llx_smartpick_wave`) der kobler et sæt `PickTask`-rækker til én bølge — i dag er `batch_id` på queue-tabellen det nærmeste.

---

## 5. Picking

**Ansvar:** Udførelse af selve plukningen, rutestyring, undtagelseshåndtering.

| Entitet | Type | Status | Noter |
|---|---|---|---|
| `PickTask` | Aggregate Root | ✅ | `llx_smartpick_queue` (delt med Planning) |
| `PickRoute` | Value Object | 🔶 | Beregnes runtime af `SmartPickRouteOptimizer` (TSP), ikke persisteret |
| `PickException` | Value Object | ✅ | `markItemDefectiveOrMissing()` i `SmartPickQueue.class.php` — status `partial_backorder` |
| `Tote` (samlekasse) | Entity | ✅ | `tote_id` på queue-tabellen — fungerer som SmartPicks LPN-ækvivalent |

**Nuværende kode:** `SmartPickQueue.class.php`, `SmartPickRouteOptimizer.class.php`

**Services (mål):** `PickingService`, `RouteOptimizationService`, `ExceptionHandlingService`

---

## 6. Packing

**Ansvar:** Konsolidering, kartonvalg, pakning, ekspres-detektion.

| Entitet | Type | Status | Noter |
|---|---|---|---|
| `PackingSession` | Aggregate Root | ✅ | `startPackingOrder()` / `finishPackingOrder()` i `SmartPickAllocation.class.php` |
| `PutWallSlot` | Value Object | 🔶 | Udledt runtime som `REOL-SLOT-<hash>` — ikke en fysisk allokeret/reserveret slot endnu |
| `BoxRecommendation` | Value Object | ✅ | `SmartPickCartonization->calculateOptimalBoxForOrder()` |

**Nuværende kode:** `SmartPickAllocation.class.php`, `SmartPickCartonization.class.php`, `interfaces/ICartonization.php`

**Services (mål):** `PackingService`, `CartonizationService`, `PutWallService`

**Gap:** Put-Wall slots er i dag et deterministisk hash af `tote_id`, ikke en reel begrænset ressource-pulje med kapacitet og reservation/frigivelse. Skal normaliseres hvis put-wall fysisk kapacitet skal styres korrekt.

---

## 7. Shipping

**Ansvar:** Fragtbooking, labels, tracking.

| Entitet | Type | Status | Noter |
|---|---|---|---|
| `Shipment` | Aggregate Root | ✅ | `llx_smartpick_shipments` |
| `Carrier` | Value Object | ✅ | `carrier_code` — tildeles af `SmartPickRuleEngine->evaluateOrderRules()` |
| `ShippingLabel` | Value Object | ✅ | `label_url`, `tracking_url` |

**Nuværende kode:** `ShipmondoAPI.class.php`, `ShipmondoPOC.class.php`, `interfaces/IShippingProvider.php`

**Services (mål):** `ShippingService` (facade over `ShipmondoAPI`, forbereder ERP-agnostisk carrier-abstraktion)

---

## 8. Returns

**Ansvar:** Modtagelse, inspektion og beslutning om returvarer.

| Entitet | Type | Status | Noter |
|---|---|---|---|
| `Return` | Aggregate Root | ⬜ | Ingen kode eller tabel findes endnu |
| `ReturnDecision` | Value Object | ⬜ | Repair / Restock / Scrap |

**Nuværende kode:** Ingen. Dette er det mest umodne domæne i hele systemet.

**Services (mål):** `ReturnsService`, `ReturnInspectionService`

---

## 9. Labor (Bemanding)

**Ansvar:** Vagtplanlægning, arbejdstidsregistrering, ydelsesmåling.

| Entitet | Type | Status | Noter |
|---|---|---|---|
| `Shift` | Aggregate Root | ✅ | `llx_smartpick_shifts` |
| `UserShift` | Entity | ✅ | `llx_smartpick_user_shifts` |
| `WorkerPerformanceLog` | Entity | ✅ | `llx_smartpick_user_logs` (qty, vægt løftet, varighed) |

**Nuværende kode:** `SmartPickShiftPlanner.class.php`, `script/smartpick_auto_shifts_cron.php` (natlig 4-dages auto-oprettelse)

**Services (mål):** `ShiftPlanningService`, `LaborAnalyticsService`

---

## 10. AI / Digital Twin

**Ansvar:** Forecasting, ræsonnering, beslutningsstøtte.

| Komponent | Status | Noter |
|---|---|---|
| `SmartPickAI.class.php` | ✅ | Generisk AI-facade |
| `SmartPickDeepSeekAI.class.php` | ✅ | DeepSeek-R1-klient |
| `SmartPickMistralAI.class.php` | ✅ | Alternativ AI-provider |
| `SmartPickForecastAI.class.php` | ✅ | Demand forecast |
| `IAIProvider.php` | ✅ | Provider-interface — gør AI-backend udskiftelig |
| `Warehouse Digital Twin` | ⬜ | Konceptuelt beskrevet i `DIGITAL_TWIN.md`, ingen implementering |

**Gap:** Der findes allerede en pæn provider-abstraktion (`IAIProvider`), hvilket er godt — men ingen af AI-klasserne er koblet til en samlet tilstandsmodel (Digital Twin). De opererer isoleret pr. forespørgsel.

---

## 11. Analytics

| Komponent | Status | Noter |
|---|---|---|
| `SmartPickKPIDashboard.class.php` | ✅ | KPI Cockpit |
| `SmartPickStats.class.php` | ✅ | Rå statistik |
| `templates/smartpick_dashboard.tpl.php` | ✅ | Visning |

---

## 12. Rules & Events (Cross-Cutting)

| Komponent | Status | Noter |
|---|---|---|
| `SmartPickRuleEngine.class.php` | 🔶 | Fungerer, men reglerne er **hårdkodede if-sætninger** (se `RULE_ENGINE.md` for målarkitektur) |
| `SmartPickEventEngine.class.php` | 🔶 | `dispatch($eventName, $payload)` findes, men er en stub — ingen faktiske listeners/subscribers, ingen persisteret event-log |
| `SmartPickMigrationBridge.class.php` | ✅ | Shadow-mode legacy-import (skrivebeskyttet) |

---

## Value Objects på tværs af domæner

- `Money` — bruges implicit via Dolibarr, ikke eget VO endnu
- `Quantity` — `qty_to_pick`, `qty_picked` (double) — bør konsolideres til ét VO med enhed
- `TimeWindow` — shift start/slut, cutoff-tid
- `Barcode` — bruges i `api/scan.php` og queue-tabellen

## Prioriteret gap-liste (til Sprint 2)

1. **Zone/Location som rigtige entiteter** — i dag kun strings. Blokerer reel slotting-optimering.
2. **Wave som persistent aggregate** — i dag kun runtime-array.
3. **Put-Wall som ressourcepulje** — i dag hash-baseret, ingen kapacitetsstyring.
4. **Returns-domænet findes slet ikke** — skal bygges fra bunden.
5. **EventEngine mangler faktiske subscribers** — dispatch sker, men intet lytter.
6. **RuleEngine mangler konfigurerbarhed** — reglerne er hårdkodet PHP, ikke data-drevne (se `RULE_ENGINE.md`).
