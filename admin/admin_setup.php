<?php
// SmartPick Administration - Shipmondo & Mistral AI Konfiguration i Dolibarr

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT . '/custom/smartpick/class/ShipmondoAPI.class.php';
require_once DOL_DOCUMENT_ROOT . '/custom/smartpick/class/SmartPickMistralAI.class.php';

$langs->load('admin');
$langs->load('smartpick@smartpick');

if (!$user->admin) accessforbidden();

$action = GETPOST('action', 'alpha');

if ($action == 'update') {
    $apiUser = GETPOST('SMARTPICK_SHIPMONDO_API_USER', 'alphanohtml');
    $apiKey = GETPOST('SMARTPICK_SHIPMONDO_API_KEY', 'rest');
    $mistralKey = GETPOST('SMARTPICK_MISTRAL_API_KEY', 'rest');
    $mistralModel = GETPOST('SMARTPICK_MISTRAL_MODEL', 'alphanohtml');
    $autoShipment = GETPOST('SMARTPICK_AUTO_CREATE_SHIPMENT', 'int');
    $sortRoute = GETPOST('SMARTPICK_SORT_ROUTE_BY', 'alphanohtml');

    dolibarr_set_const($db, 'SMARTPICK_SHIPMONDO_API_USER', $apiUser, 'chaine', 0, '', $conf->entity);
    dolibarr_set_const($db, 'SMARTPICK_SHIPMONDO_API_KEY', $apiKey, 'chaine', 0, '', $conf->entity);
    dolibarr_set_const($db, 'SMARTPICK_MISTRAL_API_KEY', $mistralKey, 'chaine', 0, '', $conf->entity);
    dolibarr_set_const($db, 'SMARTPICK_MISTRAL_MODEL', $mistralModel, 'chaine', 0, '', $conf->entity);
    dolibarr_set_const($db, 'SMARTPICK_AUTO_CREATE_SHIPMENT', $autoShipment, 'chaine', 0, '', $conf->entity);
    dolibarr_set_const($db, 'SMARTPICK_SORT_ROUTE_BY', $sortRoute, 'chaine', 0, '', $conf->entity);

    setEventMessages('Indstillinger gemt', null, 'mesgs');
}

llxHeader('', 'SmartPick Administration');

print load_fiche_titre('SmartPick - Modulkonfiguration (Shipmondo & Mistral AI)');

print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="update">';

print '<table class="noborder centpercent">';

// Shipmondo
print '<tr class="liste_titre"><td colspan="2">Shipmondo API Indstillinger</td></tr>';
print '<tr class="oddeven"><td>Shipmondo API Bruger (API User)</td>';
print '<td><input type="text" name="SMARTPICK_SHIPMONDO_API_USER" value="' . dol_escape_htmltag($conf->global->SMARTPICK_SHIPMONDO_API_USER) . '" size="40"></td></tr>';
print '<tr class="oddeven"><td>Shipmondo API Nøgle (API Key)</td>';
print '<td><input type="password" name="SMARTPICK_SHIPMONDO_API_KEY" value="' . dol_escape_htmltag($conf->global->SMARTPICK_SHIPMONDO_API_KEY) . '" size="40"></td></tr>';

// Mistral AI
print '<tr class="liste_titre"><td colspan="2">🤖 Mistral AI Integration (WMS Slotting & Lageroptimering)</td></tr>';
print '<tr class="oddeven"><td>Mistral AI API Key</td>';
print '<td><input type="password" name="SMARTPICK_MISTRAL_API_KEY" value="' . dol_escape_htmltag($conf->global->SMARTPICK_MISTRAL_API_KEY) . '" size="40"></td></tr>';
print '<tr class="oddeven"><td>Mistral AI Model</td>';
print '<td><select name="SMARTPICK_MISTRAL_MODEL">';
$curr_model = !empty($conf->global->SMARTPICK_MISTRAL_MODEL) ? $conf->global->SMARTPICK_MISTRAL_MODEL : 'mistral-small-latest';
print '<option value="mistral-small-latest"' . ($curr_model == 'mistral-small-latest' ? ' selected' : '') . '>mistral-small-latest (Hurtig & Effektiv)</option>';
print '<option value="mistral-large-latest"' . ($curr_model == 'mistral-large-latest' ? ' selected' : '') . '>mistral-large-latest (Avanceret Logistik-analyse)</option>';
print '<option value="open-mistral-7b"' . ($curr_model == 'open-mistral-7b' ? ' selected' : '') . '>open-mistral-7b</option>';
print '</select></td></tr>';

// WMS
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

llxFooter();
