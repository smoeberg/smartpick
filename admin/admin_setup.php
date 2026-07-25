<?php
// SmartPick Administration - Konfiguration af Vækstfaktor, Shipmondo & Mistral AI

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';

$langs->load('admin');
$langs->load('smartpick@smartpick');

if (!$user->admin) accessforbidden();

$action = GETPOST('action', 'alpha');

if ($action == 'update') {
    $apiUser = GETPOST('SMARTPICK_SHIPMONDO_API_USER', 'alphanohtml');
    $apiKey = GETPOST('SMARTPICK_SHIPMONDO_API_KEY', 'rest');
    $mistralKey = GETPOST('SMARTPICK_MISTRAL_API_KEY', 'rest');
    $mistralModel = GETPOST('SMARTPICK_MISTRAL_MODEL', 'alphanohtml');
    $growthFactor = GETPOST('SMARTPICK_GROWTH_FACTOR', 'alphanohtml');

    dolibarr_set_const($db, 'SMARTPICK_SHIPMONDO_API_USER', $apiUser, 'chaine', 0, '', $conf->entity);
    dolibarr_set_const($db, 'SMARTPICK_SHIPMONDO_API_KEY', $apiKey, 'chaine', 0, '', $conf->entity);
    dolibarr_set_const($db, 'SMARTPICK_MISTRAL_API_KEY', $mistralKey, 'chaine', 0, '', $conf->entity);
    dolibarr_set_const($db, 'SMARTPICK_MISTRAL_MODEL', $mistralModel, 'chaine', 0, '', $conf->entity);
    dolibarr_set_const($db, 'SMARTPICK_GROWTH_FACTOR', $growthFactor, 'chaine', 0, '', $conf->entity);

    setEventMessages('Indstillinger gemt', null, 'mesgs');
}

llxHeader('', 'SmartPick Administration');

print load_fiche_titre('SmartPick - Modulkonfiguration (Shipmondo, AI & Vækstfaktor)');

print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="update">';

print '<table class="noborder centpercent">';

// Growth
print '<tr class="liste_titre"><td colspan="2">🚀 Virksomhedsvækst & Shop-Skalering (Growth Multiplier)</td></tr>';
print '<tr class="oddeven"><td>Manuel Vækst- / Shopskaleringsfaktor (f.eks. 1.0 = uændret, 4.0 = udvidet fra 2 til 8 shops)</td>';
print '<td><input type="text" name="SMARTPICK_GROWTH_FACTOR" value="' . dol_escape_htmltag($conf->global->SMARTPICK_GROWTH_FACTOR ?? '1.0') . '" size="10"></td></tr>';

// Shipmondo
print '<tr class="liste_titre"><td colspan="2">Shipmondo API Indstillinger</td></tr>';
print '<tr class="oddeven"><td>Shipmondo API Bruger (API User)</td>';
print '<td><input type="text" name="SMARTPICK_SHIPMONDO_API_USER" value="' . dol_escape_htmltag($conf->global->SMARTPICK_SHIPMONDO_API_USER) . '" size="40"></td></tr>';
print '<tr class="oddeven"><td>Shipmondo API Nøgle (API Key)</td>';
print '<td><input type="password" name="SMARTPICK_SHIPMONDO_API_KEY" value="' . dol_escape_htmltag($conf->global->SMARTPICK_SHIPMONDO_API_KEY) . '" size="40"></td></tr>';

// Mistral AI
print '<tr class="liste_titre"><td colspan="2">🤖 Mistral AI Integration (WMS Slotting & AI Auto-Vagter)</td></tr>';
print '<tr class="oddeven"><td>Mistral AI API Key</td>';
print '<td><input type="password" name="SMARTPICK_MISTRAL_API_KEY" value="' . dol_escape_htmltag($conf->global->SMARTPICK_MISTRAL_API_KEY) . '" size="40"></td></tr>';

print '</table>';

print '<div class="center" style="margin-top:20px;">';
print '<input type="submit" class="button button-save" value="Gem indstillinger">';
print '</div>';

print '</form>';

llxFooter();
