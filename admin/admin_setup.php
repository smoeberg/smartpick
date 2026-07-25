<?php
// SmartPick Administration - Konfiguration af DeepSeek-R1, Mistral AI, Vækstfaktor & Shipmondo

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';

$langs->load('admin');
$langs->load('smartpick@smartpick');

if (!$user->admin) accessforbidden();

$action = GETPOST('action', 'alpha');

if ($action == 'update') {
    $aiProvider = GETPOST('SMARTPICK_AI_PROVIDER', 'alphanohtml');
    $deepseekKey = GETPOST('SMARTPICK_DEEPSEEK_API_KEY', 'rest');
    $deepseekUrl = GETPOST('SMARTPICK_DEEPSEEK_API_URL', 'rest');
    $deepseekModel = GETPOST('SMARTPICK_DEEPSEEK_MODEL', 'alphanohtml');
    
    $apiUser = GETPOST('SMARTPICK_SHIPMONDO_API_USER', 'alphanohtml');
    $apiKey = GETPOST('SMARTPICK_SHIPMONDO_API_KEY', 'rest');
    $growthFactor = GETPOST('SMARTPICK_GROWTH_FACTOR', 'alphanohtml');

    dolibarr_set_const($db, 'SMARTPICK_AI_PROVIDER', $aiProvider, 'chaine', 0, '', $conf->entity);
    dolibarr_set_const($db, 'SMARTPICK_DEEPSEEK_API_KEY', $deepseekKey, 'chaine', 0, '', $conf->entity);
    dolibarr_set_const($db, 'SMARTPICK_DEEPSEEK_API_URL', $deepseekUrl, 'chaine', 0, '', $conf->entity);
    dolibarr_set_const($db, 'SMARTPICK_DEEPSEEK_MODEL', $deepseekModel, 'chaine', 0, '', $conf->entity);

    dolibarr_set_const($db, 'SMARTPICK_SHIPMONDO_API_USER', $apiUser, 'chaine', 0, '', $conf->entity);
    dolibarr_set_const($db, 'SMARTPICK_SHIPMONDO_API_KEY', $apiKey, 'chaine', 0, '', $conf->entity);
    dolibarr_set_const($db, 'SMARTPICK_GROWTH_FACTOR', $growthFactor, 'chaine', 0, '', $conf->entity);

    setEventMessages('Indstillinger gemt', null, 'mesgs');
}

llxHeader('', 'SmartPick Administration');

print load_fiche_titre('SmartPick - Modulkonfiguration (DeepSeek-R1 AI, Shipmondo & Vækst)');

print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="update">';

print '<table class="noborder centpercent">';

// AI Engine
print '<tr class="liste_titre"><td colspan="2">🧠 Primary AI Reasoning Engine (Slotting, Auto-Shifts & Forecast)</td></tr>';
print '<tr class="oddeven"><td>Vælg AI Motor</td>';
print '<td><select name="SMARTPICK_AI_PROVIDER">';
print '<option value="deepseek" ' . (($conf->global->SMARTPICK_AI_PROVIDER ?? 'deepseek') == 'deepseek' ? 'selected' : '') . '>DeepSeek-R1 (Anbefalet - Højeste Ræsonneringsevne)</option>';
print '<option value="mistral" ' . (($conf->global->SMARTPICK_AI_PROVIDER ?? '') == 'mistral' ? 'selected' : '') . '>Mistral AI</option>';
print '</select></td></tr>';

print '<tr class="oddeven"><td>DeepSeek API Nøgle (DeepSeek / Groq / Together AI)</td>';
print '<td><input type="password" name="SMARTPICK_DEEPSEEK_API_KEY" value="' . dol_escape_htmltag($conf->global->SMARTPICK_DEEPSEEK_API_KEY ?? '') . '" size="40"></td></tr>';

print '<tr class="oddeven"><td>DeepSeek Endpoint URL (Lokal vLLM/Ollama eller Cloud API)</td>';
print '<td><input type="text" name="SMARTPICK_DEEPSEEK_API_URL" value="' . dol_escape_htmltag($conf->global->SMARTPICK_DEEPSEEK_API_URL ?? 'https://api.deepseek.com/v1/chat/completions') . '" size="60"></td></tr>';

print '<tr class="oddeven"><td>DeepSeek Model Navn</td>';
print '<td><input type="text" name="SMARTPICK_DEEPSEEK_MODEL" value="' . dol_escape_htmltag($conf->global->SMARTPICK_DEEPSEEK_MODEL ?? 'deepseek-reasoner') . '" size="30"> (f.eks. deepseek-reasoner, deepseek-r1, eller groq/deepseek-r1)</td></tr>';

// Growth
print '<tr class="liste_titre"><td colspan="2">🚀 Virksomhedsvækst & Shop-Skalering</td></tr>';
print '<tr class="oddeven"><td>Manuel Vækstfaktor (f.eks. 1.0 = uændret, 4.0 = udvidet fra 2 til 8 shops)</td>';
print '<td><input type="text" name="SMARTPICK_GROWTH_FACTOR" value="' . dol_escape_htmltag($conf->global->SMARTPICK_GROWTH_FACTOR ?? '1.0') . '" size="10"></td></tr>';

// Shipmondo
print '<tr class="liste_titre"><td colspan="2">Shipmondo API Indstillinger</td></tr>';
print '<tr class="oddeven"><td>Shipmondo API Bruger (API User)</td>';
print '<td><input type="text" name="SMARTPICK_SHIPMONDO_API_USER" value="' . dol_escape_htmltag($conf->global->SMARTPICK_SHIPMONDO_API_USER ?? '') . '" size="40"></td></tr>';
print '<tr class="oddeven"><td>Shipmondo API Nøgle (API Key)</td>';
print '<td><input type="password" name="SMARTPICK_SHIPMONDO_API_KEY" value="' . dol_escape_htmltag($conf->global->SMARTPICK_SHIPMONDO_API_KEY ?? '') . '" size="40"></td></tr>';

print '</table>';

print '<div class="center" style="margin-top:20px;">';
print '<input type="submit" class="button button-save" value="Gem indstillinger">';
print '</div>';

print '</form>';

llxFooter();
