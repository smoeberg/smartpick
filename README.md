# SmartPick - Dolibarr WMS, Vækstfaktor, Højtidsforskydning & AI

SmartPick er et WMS (Warehouse Management System) modul til Dolibarr ERP/CRM med indbygget **Vækstfaktor (Shopskalering)**, **År-til-År Højtidsforskydning**, dynamisk faktor-motor, AI-forudsigelse 4 dage frem og Shipmondo API v3.

---

## 🚀 Vækstfaktor & Shop-Skalering (`SMARTPICK_GROWTH_FACTOR`)
- **Automatisk YoY Vækstberegning:** Måler den reelle ordrevækst i Dolibarr over de seneste 30 dage sammenlignet med samme periode sidste år.
- **Shop-Ekspansionsfaktor:** Hvis virksomheden f.eks. udvider fra 2 til 8 connectede e-handelsshops, kan vækstfaktoren indstilles til `4.0` i administrationen.
- **Formel:**
  $$\text{Prognose} = \text{Empirisk Grundlinje} \times \text{Højtidsforskydning} \times \text{Vækstfaktor}$$

---

## 📅 År-til-År Højtidsforskydning (`getHolidayCalendarShiftDynamics`)
- **Ugedagsdynamik for Helligdage:** Tager højde for at hvis Juleaften flytter sig fra en Søndag til en Onsdag, ændres ordrefordelingen markant i dagene op til og efter julen.
- **Historisk Sammenligning:** Mistral AI sammenligner den aktuelle kalenderplacering med de seneste 3 års historiske ordrekurver i Dolibarr.

---

## 🛠 Modulstruktur
- `class/SmartPickFactorEngine.class.php` - Vækstfaktor (Shopskalering) & Højtidsforskydningsanalyse
- `class/SmartPickQueue.class.php` - Plukkø med SLA Ordrealder-Prioritering (Gamle ordrer først)
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
