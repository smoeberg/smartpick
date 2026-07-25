# SmartPick - Dolibarr WMS & Shipmondo Integration

SmartPick er et avanceret WMS (Warehouse Management System) modul til Dolibarr ERP/CRM med direkte integration til Shipmondo API v3 og AI-drevet lageroptimering.

---

## 🏗 WMS Workflow & Dolibarr Standardintegration

### 1. AI-baseret Lagerorganisering (Slotting & ABC-analyse) (`SmartPickAI.class.php`)
- **Frekvensanalyse:** Beregner løbende hvilke produkter der plukkes hyppigst på tværs af tidligere ordrer i Dolibarr.
- **Placering tæt på udgang:** A-varer (top 20% hurtigst sælgende) tildeles automatiske placeringsanbefalinger i Zone A (tættest på pakke-/udgangsområdet), B-varer i midtersektionen og C-varer på fjernlageret for at minimere gangtid.

### 2. Genopfyldning af Pluklager fra Færdiglager (`SmartPickReplenishment.class.php`)
- **Standard Dolibarr Tærskler:** Benytter `seuil_stock` (minimumsbeholdning) og `desiredstock` på Dolibarrs `product_warehouse` tabeller.
- **Automatisk Genopfyldningsordre:** Når beholdningen på pluklageret falder under minimumsgrænsen, genereres automatisk en genopfyldningsopgave fra bufferlageret/fjernlageret.
- **Dolibarr MouvementStock:** Overførslen bogføres direkte som en standard internt lagertransfer i Dolibarr (`MouvementStock`).

### 3. Medarbejder-Allokering, Zone-pluk & Samlestation (`SmartPickAllocation.class.php`)
- **Tildeling:** En ordre kan enten plukkes samlet af én medarbejder (Single Order Picking) eller opdeles efter zoner (`loc_rack`), så flere medarbejdere plukker hver deres del i parallel.
- **Samlestation (Consolidation Point):** Ved zone-pluk samles del-plukkene ved pakke-/samlestationen, hvor systemet verificerer at alle linjer er ankommet før udskrift af Shipmondo fragtlabel.

### 4. Registrering af Medarbejderdata & Arbejdsforhold (`SmartPickStats.class.php`)
- **Ergometri & Løftekilo:** For hvert udført pluk beregnes den samlede løftede vægt i kg baseret på produktets vægt i Dolibarr (`product->weight`).
- **Logning:** Gemmer løftet kg, plukhastighed (sekunder pr. linje) og antal gennemførte pluk pr. medarbejder i `llx_smartpick_user_logs` til brug for arbejdsmiljø- og kapacitetsrapportering.

### 5. Løbende Lagertælling under Pluk (Cycle Counting) (`SmartPickCycleCount.class.php`)
- **Dynamiske Stikprøver:** Når en medarbejder befinder sig ved en plukplacering, beder systemet med jævne mellemrum (f.eks. ved 1 ud af 10 pluk) medarbejderen bekræfte eller korrigere hyldens faktiske beholdning.
- **Automatisk Justering:** Hvis der opdages en afvigelse, opretter systemet automatisk en standard Dolibarr lagerjustering i `MouvementStock` med årsagsangivelse.

---

## 🛠 Modulstruktur
- `class/SmartPickAI.class.php` - AI & ABC Slotting logik
- `class/SmartPickReplenishment.class.php` - Genopfyldning fra overskudslager
- `class/SmartPickAllocation.class.php` - Allokering & Samlestation
- `class/SmartPickStats.class.php` - Løftede kilo & arbejdsforhold
- `class/SmartPickCycleCount.class.php` - Løbende lagertælling
- `class/ShipmondoAPI.class.php` - Shipmondo REST API v3
- `templates/smartpick_dashboard.tpl.php` - Mobil UI til pluk
- `sql/llx_smartpick_tables.sql` - Databasestruktur
