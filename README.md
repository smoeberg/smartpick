# SmartPick - Dolibarr WMS, Dynamisk Faktor-Motor & Mistral AI Integration

SmartPick er et WMS (Warehouse Management System) modul til Dolibarr ERP/CRM. Modulet anvender en **Dynamisk Faktor-Motor**, som trækker landekoder, nationale helligdage (`llx_c_holiday`) og Dolibarrs kalendervent (`llx_actioncomm`) for at forudse ordremængder 4 dage frem i tiden.

---

## 🏛 Dynamisk Dolibarr Faktor-Motor (`SmartPickFactorEngine.class.php`)

For at sikre at ingen kritiske faktorer overses, ekstraherer faktor-motoren følgende kontekstuelle data for måldatoen ($D+4$):

1. **Landeafhængige Nationale Helligdage (`llx_c_holiday`):**
   - Slår automatisk op på lagerets landekode (f.eks. `DK`, `SE`, `DE`, `NO`).
   - Tjekker om måldatoen er en lukket helligdag (f.eks. Påske, Kristi Himmelfart, Jul).
   - Tjekker om dagen LIGE FØR var en helligdag $\rightarrow$ Udløser **ophobningseffekt (Surge Factor)**, da e-handelsordrer har samlet sig op over lukkedagen.

2. **Dolibarr Kalender-Events & Kampagner (`llx_actioncomm`):**
   - Henter planlagte salgskampagner, Black Friday-events og virksomhedslukninger direkte fra Dolibarrs kalender.

3. **Realtids Backlog i Dolibarr (`llx_commande`):**
   - Inddrager ubehandlede/validerede ordrer i Dolibarr, som endnu ikke er plukket.

4. **Tids- & Lønningsfaktorer:**
   - Lønningsdagseffekt (1.–5. i måneden vs. 25.–31. i måneden).
   - Ugedagsmønstre.

---

## 🛠 Modulstruktur
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
