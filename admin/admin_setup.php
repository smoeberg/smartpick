<?php
// SmartPick Administration & Setup Configuration in Dolibarr

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT . '/custom/smartpick/class/ShipmondoAPI.class.php';

$langs->load('admin');
$langs->load('smartpick@smartpick');

if (!$user->admin) accessforbidden();

$action = GETPOST('action', 'alpha');

if ($action == 'update') {
    $apiUser = GETPOST('SMARTPICK_SHIPMONDO_API_USER', 'alphanohtml');
    $apiKey = GETPOST('SMARTPICK_SHIPMONDO_API_KEY', 'rest');
    $autoShipment = GETPOST('SMARTPICK_AUTO_CREATE_SHIPMENT', 'int');
    $sortRoute = GETPOST('SMARTPICK_SORT_ROUTE_BY', 'alphanohtml');

    dolibarr_set_const($db, 'SMARTPICK_SHIPMONDO_API_USER', $apiUser, 'chaine', 0, '', $conf->entity);
    dolibarr_set_const($db, 'SMARTPICK_SHIPMONDO_API_KEY', $apiKey, 'chaine', 0, '', $conf->entity);
    dolibarr_set_const($db, 'SMARTPICK_AUTO_CREATE_SHIPMENT', $autoShipment, 'chaine', 0, '', $conf->entity);
    dolibarr_set_const($db, 'SMARTPICK_SORT_ROUTE_BY', $sortRoute, 'chaine', 0, '', $conf->entity);

    setEventMessages('Indstillinger gemt', null, 'mesgs');
}

llxHeader('', 'SmartPick Administration');

print load_fiche_titre('SmartPick - Modulkonfiguration & Shipmondo Integration');

print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="update">';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><td colspan="2">Shipmondo API Indstillinger</td></tr>';

print '<tr class="oddeven"><td>Shipmondo API Bruger (API User)</td>';
print '<td><input type="text" name="SMARTPICK_SHIPMONDO_API_USER" value="' . dol_escape_htmltag($conf->global->SMARTPICK_SHIPMONDO_API_USER) . '" size="40"></td></tr>';

print '<tr class="oddeven"><td>Shipmondo API Nøgle (API Key)</td>';
print '<td><input type="password" name="SMARTPICK_SHIPMONDO_API_KEY" value="' . dol_escape_htmltag($conf->global->SMARTPICK_SHIPMONDO_API_KEY) . '" size="40"></td></tr>';

print '<tr class="liste_titre"><td colspan="2">WMS & Plukkonfiguration</td></tr>';

print '<tr class="oddeven"><td>Automatisk oprettelse af forsendelse ved afsluttet pluk</td>';
print '<td><select name="SMARTPICK_AUTO_CREATE_SHIPMENT">';
print '<option value="1"' . ($conf->global->SMARTPICK_AUTO_CREATE_SHIPMENT == 1 ? ' selected' : '') . '>Ja (Opret forsendelse & book fragt i Shipmondo)</option>';
print '<option value="0"' . ($conf->global->SMARTPICK_AUTO_CREATE_SHIPMENT == 0 ? ' selected' : '') . '>Nej (Manuel færdiggørelse)</option>';
print '</select></td></tr>';

print '<tr class="oddeven"><td>Sortering af plukrute på lageret</td>';
print '<td><select name="SMARTPICK_SORT_ROUTE_BY">';
print '<option value="rack_bin"' . ($conf->global->SMARTPICK_SORT_ROUTE_BY == 'rack_bin' ? ' selected' : '') . '>Placering (Hylde / Bin / Gang)</option>';
print '<option value="ref"' . ($conf->global->SMARTPICK_SORT_ROUTE_BY == 'ref' ? ' selected' : '') . '>Varenummer (Product Ref)</option>';
print '</select></td></tr>';

print '</table>';

print '<div class="center" style="margin-top:20px;">';
print '<input type="submit" class="button button-save" value="Gem indstillinger">';
print '</div>';

print '</form>';

// Tjek forbindelse hvis API er angivet
if (!empty($conf->global->SMARTPICK_SHIPMONDO_API_USER) && !empty($conf->global->SMARTPICK_SHIPMONDO_API_KEY)) {
    $api = new ShipmondoAPI($conf->global->SMARTPICK_SHIPMONDO_API_USER, $conf->global->SMARTPICK_SHIPMONDO_API_KEY);
    $res = $api->testConnection();

    print '<br><h3>Shipmondo Forbindelsesstatus</h3>';
    if ($res['success']) {
        print '<div class="info-box success" style="padding:10px; background:#d4edda; color:#155724;">✔ Forbindelse til Shipmondo v3 oprettet! Konto: ' . dol_escape_htmltag($res['data']['name'] ?? 'Aktiv') . '</div>';
    } else {
        print '<div class="info-box error" style="padding:10px; background:#f8d7da; color:#721c24;">✖ Kunne ikke forbinde til Shipmondo: ' . dol_escape_htmltag($res['error']) . '</div>';
    }
}

llxFooter();
