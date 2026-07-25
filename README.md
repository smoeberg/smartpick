# SmartPick - Dolibarr WMS, DeepSeek-R1 AI, Cartonization & Shipmondo

SmartPick er et WMS (Warehouse Management System) modul til Dolibarr ERP/CRM med indbygget **DeepSeek-R1 Ræsonneringsmodel**, automatisk 4-dages AI-vagtmotor, emballageberegning (Cartonization), Put-Wall konsolidering og Shipmondo API v3.

---

## 🧠 DeepSeek-R1 Integration (`class/SmartPickDeepSeekAI.class.php`)

DeepSeek-R1 er valgt som den primære AI-ræsonneringsmotor til WMS-logikken pga. sine **overlegne evner inden for kæde-af-tankegang (Chain-of-Thought reasoning)**, rumlig lageroptimering (Slotting/Bin-Packing) og komplekse tidsserie-prognoser.

### **Fleksible Afviklingsmuligheder:**
1. **Cloud API (DeepSeek API / Groq / Together.ai):** Høj hastighed og fuld skalerbarhed.
2. **Lokal Afvikling (Ollama / vLLM / Local Server):** Kør DeepSeek-R1 lokalt i eget miljø uden eksterne API-kald for maksimal datasikkerhed.

Konfigureres enkelt under `admin/admin_setup.php`.

---

## 🛠 Modulstruktur
- `class/SmartPickDeepSeekAI.class.php` - DeepSeek-R1 AI ræsonneringsklient (Lokal vLLM/Ollama eller Cloud API)
- `class/SmartPickCartonization.class.php` - Automatisk beregning af optimal Dolibarr papkasse
- `class/SmartPickAllocation.class.php` - Put-Wall Slot Konsolidering, Pakker-ID & Pakketid
- `class/SmartPickFactorEngine.class.php` - Vækstfaktor (Shopskalering) & Højtidsforskydningsanalyse
- `class/SmartPickQueue.class.php` - Plukkø med SLA Ordrealder-Prioritering (Gamle ordrer først)
- `script/smartpick_auto_shifts_cron.php` - Natlig Dolibarr Cron til automatisk 4-dages vagtoprettelse
- `class/SmartPickForecastAI.class.php` - AI ordre- & vagtprognoser med faktor-motor
- `class/SmartPickShiftPlanner.class.php` - Automatisk vagtoprettelse & medarbejdertilmelding
- `class/SmartPickAI.class.php` - AI-baseret slotting
- `class/SmartPickStats.class.php` - Dolibarr standard medarbejderkobling & ergometric log
- `class/SmartPickReplenishment.class.php` - Genopfyldning via Dolibarr `MouvementStock`
- `class/SmartPickCycleCount.class.php` - Løbende lagertælling
- `class/ShipmondoAPI.class.php` - Shipmondo REST API v3
- `templates/smartpick_dashboard.tpl.php` - Mobil UI til pluk & pak
