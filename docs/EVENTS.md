# SmartPick V3 - Event Catalog

SmartPick bruges asynkront via `SmartPickEventEngine->dispatch($eventName, $payload)`. **Vigtig status:** dispatch-mekanismen findes, men der er endnu **ingen registrerede subscribers** — events sendes ud i tomrummet i dag. At koble faktiske listeners på er en forudsætning for at dette bliver reelt event-drevet og ikke bare et logget kald.

**Status-nøgle:** ✅ Dispatches i dag (fundet i kodekommentar/kald) · ⬜ Planlagt, ikke implementeret

Navngivningskonvention: `<Entitet><Fortid>`, PascalCase, ingen forkortelser.

---

## Inbound
- `GoodsReceived` ⬜
- `GoodsRejected` ⬜
- `QCPassed` ⬜
- `QCFailed` ⬜
- `PutawayTaskCreated` ⬜
- `PutawayCompleted` ⬜

## Storage / Slotting
- `LocationCapacityExceeded` ⬜
- `SlottingRecommendationGenerated` ⬜
- `ABCClassificationUpdated` ⬜
- `HeatmapRefreshed` ⬜

## Inventory
- `StockMoved` ⬜ *(sker allerede via Dolibarrs `MouvementStock`, men uden SmartPick-event omkring det)*
- `InventoryAdjusted` ⬜
- `CycleCountStarted` ⬜
- `CycleCountCompleted` ⬜
- `LowStockDetected` ⬜
- `ReplenishmentTaskCreated` ⬜

## Planning
- `OrderCreated` ✅ *(nævnt i `SmartPickEventEngine` kommentar)*
- `WaveCreated` ✅ *(nævnt i kommentar; `SmartPickWavePlanner` genererer wave_id, men dispatcher ikke eksplicit endnu)*
- `WaveReleased` ⬜
- `WaveCancelled` ⬜
- `DemandForecastUpdated` ⬜ *(relateret til `SmartPickFactorEngine`)*

## Picking
- `PickTaskAssigned` ⬜
- `PickingStarted` ✅ *(nævnt i kommentar)*
- `PickingFinished` ✅ *(nævnt i kommentar)*
- `PickItemScanned` ⬜ *(relateret til `api/scan.php`)*
- `PickItemDefectiveOrMissing` ✅ *(svarer til `SmartPickQueue->markItemDefectiveOrMissing()`, status `partial_backorder`)*
- `PickTaskPartialBackorder` ✅
- `RouteCalculated` ⬜ *(relateret til `SmartPickRouteOptimizer`)*

## Packing
- `PackingStarted` ✅ *(nævnt i kommentar; svarer til `startPackingOrder()`)*
- `Packed` ✅ *(nævnt i kommentar; svarer til `finishPackingOrder()`)*
- `PutWallSlotAssigned` ⬜
- `ExpressOrderDetected` ✅ *(svarer til `is_express_single_tote` i `SmartPickAllocation`)*
- `BoxRecommended` ✅ *(svarer til `SmartPickCartonization->calculateOptimalBoxForOrder()`)*

## Shipping
- `ShipmentCreated` ✅ *(nævnt i kommentar)*
- `ShipmentBooked` ⬜ *(svarer til succesfuldt Shipmondo-kald)*
- `ShipmentSent` ⬜
- `CarrierAssigned` ✅ *(svarer til `SmartPickRuleEngine->evaluateOrderRules()`)*
- `ShippingLabelGenerated` ⬜

## Returns
- `ReturnReceived` ⬜
- `ReturnInspected` ⬜
- `ReturnApproved` ⬜
- `ReturnRejected` ⬜
- `ReturnRestocked` ⬜
- `ReturnScrapped` ⬜

## Labor
- `ShiftCreated` ✅ *(svarer til `smartpick_auto_shifts_cron.php`)*
- `ShiftConfirmed` ✅ *(svarer til `llx_smartpick_user_shifts.status = 'confirmed'`)*
- `WorkerPerformanceLogged` ✅ *(svarer til insert i `llx_smartpick_user_logs`)*

## AI / Digital Twin
- `ForecastGenerated` ⬜
- `AIRecommendationIssued` ⬜
- `DigitalTwinStateUpdated` ⬜

## Migration Bridge (Shadow Mode)
- `LegacyPickTaskImported` ✅ *(kerneformål med `SmartPickMigrationBridge`)*
- `ShadowRouteVerified` ⬜
- `ShadowDiscrepancyDetected` ⬜

---

## Payload-konvention (forslag)

Alle events bør bære et minimum-payload:

```json
{
  "event": "PickingFinished",
  "aggregate_id": "12345",
  "aggregate_type": "PickTask",
  "timestamp": 1732550400,
  "fk_user": 7,
  "payload": { }
}
```

Dette matcher allerede delvist det `SmartPickEventEngine->dispatch()` returnerer i dag (`event`, `status`, `timestamp`), men mangler `aggregate_id`/`aggregate_type` for at kunne korreleres på tværs af domæner.

## Næste skridt for EventEngine

1. Tilføj en `llx_smartpick_events`-tabel så events faktisk persisteres, ikke kun returneres momentant
2. Indfør et simpelt subscriber-register (`EventEngine->subscribe($eventName, $callback)`)
3. Migrer de steder i koden der allerede *burde* dispatche (markeret ✅ ovenfor via kommentar) til faktisk at kalde `dispatch()` — i dag er de fleste kun nævnt i en docblock-kommentar, ikke reelt kaldt
