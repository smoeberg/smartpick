# SmartPick - Dolibarr WMS, Vagtplanlægning, Mistral AI & Shipmondo Integration

SmartPick er et komplet WMS (Warehouse Management System) modul til Dolibarr ERP/CRM. Modulet indeholder AI-drevet kapacitetsforudsigelse via **Mistral AI**, vagtplanlægning, pluk-cutoff logik for same-day shipping og tæt integration med Dolibarrs standard medarbejdere.

---

## 📅 Vagtplanlægning & Medarbejdertilmelding (`SmartPickShiftPlanner.class.php`)
- **Vagtoprettelse:** Ledelsen opretter vagter med dato, arbejdstid (f.eks. kl. 07:00-15:00) og maks. antal plukkere.
- **Medarbejdertilmelding:** Den enkelte Dolibarr standard medarbejder kan vælge og tilmelde sig ledige vagter.
- **Same-Day Shipping Cutoff:** Dagen opdeles i tidsintervaller med et defineret **Pluk-Cutoff** (f.eks. kl. 13:30). Ordrer indkommet før cutoff prioriteres øverst i plukkøen, så de når afhentning af fragtmand samme dag.

---

## 🔮 Mistral AI Kapacitets- & Ordreforudsigelse (`SmartPickForecastAI.class.php`)
- **Fast Stab vs. Flaskehalse:** Virksomheden opgiver et fast antal grundplukkere og deres gennemsnitlige dageskapacitet (f.eks. 3 plukkere $\times$ 100 ordrer/dag = 300 ordrer/dag).
- **Historisk Analytik:** Mistral AI analyserer historiske salgsmønstre i Dolibarr over de seneste 60-90 dage opdelt på ugedage (f.eks. store spidser mandag morgen efter weekenden).
- **Bemandingsprognose:** Mistral AI forudsiger præcis hvilke dage den faste stab overbelastes, og angiver hvor mange ekstra vikarer/ekstra vagter der skal indkaldes i god tid.

---

## 🛠 Modulstruktur
- `class/SmartPickShiftPlanner.class.php` - Vagtplanlægning, tilmelding & cutoff logik
- `class/SmartPickForecastAI.class.php` - Mistral AI ordre- & kapacitetsprognose
- `class/SmartPickMistralAI.class.php` - Mistral AI REST API klient & prompts
- `class/SmartPickAI.class.php` - AI-baseret slotting med Mistral AI
- `class/SmartPickStats.class.php` - Dolibarr standard medarbejderkobling & ergometric log
- `class/SmartPickAllocation.class.php` - Plukkerkasser (Picker Totes) & Pakkebordsinstruktioner
- `class/SmartPickReplenishment.class.php` - Genopfyldning via Dolibarr `MouvementStock`
- `class/SmartPickCycleCount.class.php` - Løbende lagertælling
- `class/ShipmondoAPI.class.php` - Shipmondo REST API v3
- `templates/smartpick_dashboard.tpl.php` - Mobil UI til pluk & pak
