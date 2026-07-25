# SmartPick - Dolibarr WMS & Shipmondo Integration

SmartPick er et avanceret WMS (Warehouse Management System) modul til Dolibarr ERP/CRM med direkte integration til Shipmondo API v3 og AI-drevet lageroptimering.

---

## 📦 Plukkerkasser (Picker Totes) til Pakkebord Workflow

### Hvordan det fungerer fra Pluk til Pakning:

1. **Plukfasen (Plukker har en unik kasse):**
   - Hver plukker tildeles en fysisk identificerbar kasse eller vognplads (f.eks. `KASSE-RØD-01`, `KASSE-BLÅ-05`, `VOGN1-A`).
   - Når plukkeren scanner varen på hylden, scanner han derefter stregkoden på sin plukkerkasse.
   - Systemet kobler den specifikke ordrelinje til kassen `KASSE-RØD-01`.

2. **Pakkebordet (Pakkerens skærmvisning):**
   - Når alle kasser til en ordre ankommer til pakkebordet, scanner pakkeren ordrenummeret.
   - Pakkeskærmen viser en **strikte plukinstruks for pakkeren**:
     - 📦 **Vare 1 (Toppakning):** Tag **2 stki** fra **`KASSE-BLÅ-05`** (Plukker A).
     - 📦 **Vare 2 (Cabel/Tilbehør):** Tag **1 stki** fra **`KASSE-RØD-01`** (Plukker B).
   - Pakkeren scanner kassen, tager varen, lægger den i forsendelseskassen og bekræfter.
   - Når alt er hentet fra de respektive plukkerkasser og bekræftet, udskrives Shipmondo fragtlabelen automatisk.

---

## 🛠 Modulstruktur
- `class/SmartPickAllocation.class.php` - Plukkerkasser (Picker Totes) & Pakkebordsinstruktioner
- `class/SmartPickAI.class.php` - AI & ABC Slotting logik
- `class/SmartPickReplenishment.class.php` - Genopfyldning fra overskudslager
- `class/SmartPickStats.class.php` - Løftede kilo & arbejdsforhold
- `class/SmartPickCycleCount.class.php` - Løbende lagertælling
- `class/ShipmondoAPI.class.php` - Shipmondo REST API v3
- `templates/smartpick_dashboard.tpl.php` - Mobil UI til pluk & pak
