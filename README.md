# SmartPick - Dolibarr WMS, Restordre-Håndtering, Post-Pick Cartonization & DeepSeek-R1

SmartPick er et WMS (Warehouse Management System) modul til Dolibarr ERP/CRM med indbygget **Håndtering af Defekte Varer & Restordrer**, **Dynamisk Post-Pick Emballageberegning**, DeepSeek-R1 AI ræsonnering, Put-Wall konsolidering og Shipmondo API v3.

---

## ⚠️ Defekt Vare på Lagerhylde & Restordre-Opsplitning (`SmartPickQueue.class.php`)
- **Situation:** En plukker ankommer til lagerplaceringen, men den sidste vare på hylden er defekt, beskadiget eller mangler.
- **Plukker-Aktion:** Plukkeren trykker *"⚠️ Defekt / Mangler"* på sin håndterminal.
- **Automatisk Konsekvens:**
  1. Den defekte vare nedskrives og fjernes automatisk fra salgslageret i Dolibarr (`MouvementStock`).
  2. Ordren opsplittes automatisk: De færdigplukkede varer sendes videre til pakning, mens den manglende vare placeres på **Restordre (Backorder)**.
  3. Kundeservice/Salg i Dolibarr adviseres øjeblikkeligt om restordren.

---

## 📦 Dynamisk Post-Pick Emballageberegning (`SmartPickCartonization.class.php`)
- **Hvorfor Post-Pick?** For store varer eller ordrer med delvise pluk (f.eks. pga. defekt vare) beregnes den optimale papkasse **FØRST NÅR PLUKNINGEN ER GENNEMFØRT**.
- Systemet beregner den præcise volumen af de varer, der *reelt* blev lagt i plukkassen, så pakkeren ved pakkebordet altid får den helt rigtige kasseanbefaling.

---

## 🛠 Modulstruktur
- `class/SmartPickQueue.class.php` - Plukkø med Defekt/Mangler håndtering & Restordre-opsplitning
- `class/SmartPickCartonization.class.php` - Dynamisk Emballage-Beregning EFTER Pluk
- `class/SmartPickDeepSeekAI.class.php` - DeepSeek-R1 AI ræsonneringsklient (Lokal Ollama/vLLM eller Cloud API)
- `class/SmartPickAllocation.class.php` - Put-Wall Slot Konsolidering, Pakker-ID & Pakketid
- `class/SmartPickFactorEngine.class.php` - Vækstfaktor (Shopskalering) & Højtidsforskydningsanalyse
- `script/smartpick_auto_shifts_cron.php` - Natlig Dolibarr Cron til automatisk 4-dages vagtoprettelse
- `class/SmartPickForecastAI.class.php` - AI ordre- & vagtprognoser med faktor-motor
- `class/SmartPickShiftPlanner.class.php` - Automatisk vagtoprettelse & medarbejdertilmelding
- `class/SmartPickAI.class.php` - AI-baseret slotting
- `class/SmartPickStats.class.php` - Dolibarr standard medarbejderkobling & ergometric log
- `class/SmartPickReplenishment.class.php` - Genopfyldning via Dolibarr `MouvementStock`
- `class/SmartPickCycleCount.class.php` - Løbende lagertælling
- `class/ShipmondoAPI.class.php` - Shipmondo REST API v3
- `templates/smartpick_dashboard.tpl.php` - Mobil UI til pluk & pak
