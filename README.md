# SmartPick - Dolibarr WMS, Mistral AI & Shipmondo Integration

SmartPick er et avanceret WMS (Warehouse Management System) modul til Dolibarr ERP/CRM. Modulet anvender **Mistral AI** til at skabe logikken for lageroptimering og er 100% integreret med Dolibarrs standard medarbejdere og Shipmondo API v3.

---

## 🤖 Mistral AI Integration (`SmartPickMistralAI.class.php`)
- **Mistral AI Logik:** Anvender Mistral AI (`mistral-small-latest` eller `mistral-large-latest`) til at analysere produkt- og salgsfrekvenser fra Dolibarr.
- **AI Slotting & Placeringsforslag:** Mistral AI beregner og foreslår optimale lagerplaceringer (Hylde/Rack/Bin) i Zone A (tættest på pakkeudgang) for de varer, der plukkes hyppigst.

---

## 👥 Dolibarr Standard Medarbejdere (`llx_user` / `User`)
- **Direkte Medarbejderkobling:** Plukopgaver, plukkasser og ergonomirapportering er 100% koblet til Dolibarrs standard medarbejdere (`User` klassen i Dolibarr).
- **Rapportering & Løftekilo:** For hver medarbejder beregnes samlet antal pluk, plukhastighed samt samlede løftede kilo pr. dag (`SmartPickStats.class.php`).

---

## 🛠 Modulstruktur
- `class/SmartPickMistralAI.class.php` - Mistral AI REST API klient & prompts
- `class/SmartPickAI.class.php` - AI-baseret slotting med Mistral AI
- `class/SmartPickStats.class.php` - Dolibarr standard medarbejderkobling & ergometric log
- `class/SmartPickAllocation.class.php` - Plukkerkasser (Picker Totes) & Pakkebordsinstruktioner
- `class/SmartPickReplenishment.class.php` - Genopfyldning via Dolibarr `MouvementStock`
- `class/SmartPickCycleCount.class.php` - Løbende lagertælling
- `class/ShipmondoAPI.class.php` - Shipmondo REST API v3
- `templates/smartpick_dashboard.tpl.php` - Mobil UI til pluk & pak
