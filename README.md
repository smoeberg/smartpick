# SmartPick - Dolibarr WMS, SLA Ordrealder-Prioritering, AI & Shipmondo

SmartPick er et WMS (Warehouse Management System) modul til Dolibarr ERP/CRM med indbygget **SLA Ordrealder-Prioritering (FIFO / Order Escalation)**, dynamisk faktor-motor, AI-forudsigelse 4 dage frem og Shipmondo API v3.

---

## 🔥 SLA Ordrealder-Prioritering (`SmartPickQueue.class.php`)

For at undgå at ældre ordrer eller restordrer bliver glemt på lageret, anvender SmartPick en streng **Ordrealder Escalering (FIFO)**:

1. **Aldersevaluering (Dwell Time):**
   - Systemet beregner løbende hvor mange dage og timer en ordre har ligget ubehandlet i Dolibarr (`DATEDIFF(NOW(), order_date)`).
2. **Eskaleret Prioritetsscore:**
   - **0 dage gammel:** Normal prioritet.
   - **1 dag gammel:** Eskaleret prioritet (+100 prioritetspoint).
   - **2+ dage gammel:** 🔥 **KRITISK HØJ PRIORITET / SLA ADVARSEL**.
3. **Sortering i Plukruten:**
   Plukkøen sorterer automatisk således, at **de ældste ordrer altid plukkes først**, hvorefter ruten optimeres efter hyldeplacering (Rack/Bin).

---

## 🛠 Modulstruktur
- `class/SmartPickQueue.class.php` - Plukkø med SLA Ordrealder-Prioritering (Gamle ordrer først)
- `class/SmartPickFactorEngine.class.php` - Dolibarr Helligdags-, Kalender- & Landekode Faktor-Motor
- `script/smartpick_auto_shifts_cron.php` - Natlig Dolibarr Cron til automatisk 4-dages vagtoprettelse
- `class/SmartPickForecastAI.class.php` - Mistral AI ordre- & vagtprognoser med faktor-motor
- `class/SmartPickShiftPlanner.class.php` - Automatisk vagtoprettelse & medarbejdertilmelding
- `class/SmartPickMistralAI.class.php` - Mistral AI REST API klient
- `class/SmartPickAI.class.php` - AI-baseret slotting med Mistral AI
- `class/SmartPickStats.class.php` - Dolibarr standard medarbejderkobling & ergometric log
- `class/SmartPickAllocation.class.php` - Plukkerkasser (Picker Totes) & Pakkebordsinstruktioner
- `class/SmartPickReplenishment.class.php` - Genopfyldning via Dolibarr `MouvementStock`
- `class/SmartPickCycleCount.class.php` - Løbende lagertælling
- `class/ShipmondoAPI.class.php` - Shipmondo REST API v3
- `templates/smartpick_dashboard.tpl.php` - Mobil UI til pluk & pak
