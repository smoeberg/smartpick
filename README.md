# SmartPick - Dolibarr WMS, Emballage-Beregning (Cartonization) & Put-Wall Konsolidering

SmartPick er et WMS (Warehouse Management System) modul til Dolibarr ERP/CRM med indbygget **Automatisk Emballage-Beregning (Cartonization)**, **Put-Wall Slot Konsolidering** for at forhindre pakkeflaskehalse, tidsregistrering pr. pakker og Shipmondo API v3 integration.

---

## 📦 Emballage-Valg & Papkasselager i Dolibarr (`SmartPickCartonization.class.php`)
- **Volumen- & Dimensionstest:** Systemet summerer den samlede ordrevolumen ($V = \sum v_i$) og vælger automatisk den mindst mulige Dolibarr papkasse (`BOX-S`, `BOX-M`, `BOX-L`), der kan rumme varerne + 15% fyldmateriale.
- **Dolibarr Lagerintegration:** Papkasser er oprettet som standardprodukter i Dolibarr. Når pakkeren scanner kassen på pakkebordet, trækkes kassebeholdningen automatisk i Dolibarr.

---

## ⚡ Forebyggelse af Pakkeflaskehalse (Put-Wall Slot System) (`SmartPickAllocation.class.php`)

### **Udfordring:**
At lade en pakker lede igennem 50 tilfældige plukkasser efter 6 varer skaber en enorm flaskehals ved pakkebordet.

### **SmartPick Løsning:**
1. **Put-Wall Reol-Slots (`SLOT-A1` til `SLOT-A20`):**
   Når plukkere afleverer deres plukkasser ved pakkebordet, scanner de kassen ind på en nummereret plads i Put-Wall reolen.
2. **Præcis Pakker-Vejledning:**
   Pakkerens skærm viser **ikke** "Søg i 50 kasser", men i stedet helt kontante instruktioner:  
   👉 *Tag 2x fra REOL-SLOT-A4 (Blå Kasse)*  
   👉 *Tag 1x fra REOL-SLOT-B12 (Rød Kasse)*
3. **Express Single-Tote Fast-Track:**
   Ordrer der befinder sig i **1 enkelt plukkasse** ledes direkte udenom Put-Wall reolen til Express-pakning!
4. **Pakker & Pakketidsregistrering:**
   Registrerer præcis hvilken medarbejder (`fk_packer_user`) der pakker ordren, samt pakketiden i sekunder fra start til færdigudskrift.

---

## 🛠 Modulstruktur
- `class/SmartPickCartonization.class.php` - Automatisk beregning af optimal Dolibarr papkasse
- `class/SmartPickAllocation.class.php` - Put-Wall Slot Konsolidering, Pakker-ID & Pakketid
- `class/SmartPickFactorEngine.class.php` - Vækstfaktor (Shopskalering) & Højtidsforskydningsanalyse
- `class/SmartPickQueue.class.php` - Plukkø med SLA Ordrealder-Prioritering (Gamle ordrer først)
- `script/smartpick_auto_shifts_cron.php` - Natlig Dolibarr Cron til automatisk 4-dages vagtoprettelse
- `class/SmartPickForecastAI.class.php` - Mistral AI ordre- & vagtprognoser med faktor-motor
- `class/SmartPickShiftPlanner.class.php` - Automatisk vagtoprettelse & medarbejdertilmelding
- `class/SmartPickMistralAI.class.php` - Mistral AI REST API klient
- `class/SmartPickAI.class.php` - AI-baseret slotting med Mistral AI
- `class/SmartPickStats.class.php` - Dolibarr standard medarbejderkobling & ergometric log
- `class/SmartPickReplenishment.class.php` - Genopfyldning via Dolibarr `MouvementStock`
- `class/SmartPickCycleCount.class.php` - Løbende lagertælling
- `class/ShipmondoAPI.class.php` - Shipmondo REST API v3
- `templates/smartpick_dashboard.tpl.php` - Mobil UI til pluk & pak
