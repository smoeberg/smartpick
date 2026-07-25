# SmartPick - Dolibarr WMS & Shipmondo Integration

SmartPick er et avanceret WMS (Warehouse Management System) modul til Dolibarr ERP/CRM med direkte integration til Shipmondo API v3.

## Kernefunktioner

### 1. Lager- & Plukoptimering (WMS)
* **Optimeret Plukrute:** Automatisk sortering af varer i plukkøen baseret på lagerplacering (Hylde/Bin/Gang) for at minimere gangtid på lageret.
* **Mobil Pluk-Dashboard:** Responsiv brugerflade designet til smartphones, tablets og håndterminaler (`/smartpick/templates/smartpick_dashboard.tpl.php`).
* **Stregkodescanning & Lydfeedback:** Lynhurtig scanning af varer (EAN / Varenummer) med audio-beskeder (succes/fejl lydsignaler).
* **Delvis Pluk & Restordre:** Indbygget støtte til registrering af delvise pluk, hvor varer ikke kan plukkes fuldt ud.

### 2. Integration med Shipmondo (v3 API)
* **Automatisk Bookning af Fragt:** Opretter automatisk forsendelse og booker fragt i Shipmondo, når plukruten gennemføres.
* **Udskrivning af Fragtlabels & Dokumenter:** Integration mod Shipmondos printklient for automatisk udskrivning af pakkelabels og følgesedler.
* **Synkronisering:** Automatisk overførsel af ordre-, produkt- og modtagerdata fra Dolibarr til Shipmondo.

### 3. Installation & Konfiguration
1. Klon eller placer modulet i Dolibarrs `custom/smartpick` mappe.
2. Aktiver modulet under **Dolibarr Administration -> Moduler -> SmartPick**.
3. Indtast din **Shipmondo API User** og **API Key** under modulindstillingerne (`admin/admin_setup.php`).
