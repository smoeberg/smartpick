# SmartPick - Dolibarr WMS, AI Auto-Vagter, Mistral AI & Shipmondo Integration

SmartPick er et komplet WMS (Warehouse Management System) modul til Dolibarr ERP/CRM. Modulet anvender empiriske salgsmønstre og **Mistral AI** til automatisk at forudse ordremængder 4 dage frem i tiden og oprette de nødvendige plukkevagter helt uden manuel indgriben.

---

## 🔮 Automatisk AI-Vagtoprettelse 4 Dage Forud (`script/smartpick_auto_shifts_cron.php`)

I stedet for manuel vagtoprettelse kører SmartPick en **fuldautomatisk 4-dages rullende AI-motor**:

1. **Empirisk Dataudtræk fra Dolibarr (`SmartPickForecastAI.class.php`):**
   - **Ugedagseffekt:** Hvilke ugedage har flest ordrer (f.eks. stor mandagsstigning efter weekenden).
   - **Lønningsdagseffekt (Månedsstart vs. Månedsslut):** Analyserer empirien i Dolibarr. Eksempelvis vil en mandag den 1.-5. i måneden (efter lønudbetaling) have op til 35-50% HØJERE ordremængde end en mandag den 28. sidst på måneden.
   - **Sæsonmønstre:** Tager højde for historiske kampagner og månedstrends.

2. **Automatisk Vagtoprettelse (`SmartPickShiftPlanner.class.php`):**
   - Hver nat (via Cron) beregner Mistral AI det forventede ordreantal for **Dato + 4 Dage**.
   - Systemet beregner præcis hvor mange plukkere der kræves og **opretter automatisk vagterne** i `llx_smartpick_shifts`.
   - Plukkerne kan herefter 4 dage i forvejen se de åbne AI-oprettede vagter og vælge/tilmelde sig direkte på skærmen.

---

## 🛠 Modulstruktur
- `script/smartpick_auto_shifts_cron.php` - Natlig Dolibarr Cron til automatisk 4-dages vagtoprettelse
- `class/SmartPickForecastAI.class.php` - Empirisk analyse (lønningsdagseffekt, ugedagstrend) & Mistral AI
- `class/SmartPickShiftPlanner.class.php` - Automatisk vagtoprettelse & medarbejdertilmelding
- `class/SmartPickMistralAI.class.php` - Mistral AI REST API klient
- `class/SmartPickAI.class.php` - AI-baseret slotting med Mistral AI
- `class/SmartPickStats.class.php` - Dolibarr standard medarbejderkobling & ergometric log
- `class/SmartPickAllocation.class.php` - Plukkerkasser (Picker Totes) & Pakkebordsinstruktioner
- `class/SmartPickReplenishment.class.php` - Genopfyldning via Dolibarr `MouvementStock`
- `class/SmartPickCycleCount.class.php` - Løbende lagertælling
- `class/ShipmondoAPI.class.php` - Shipmondo REST API v3
- `templates/smartpick_dashboard.tpl.php` - Mobil UI til pluk & pak
