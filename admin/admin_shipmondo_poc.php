<?php
// File: /admin/custom/smartpick/admin/admin_shipmondo_poc.php

define('NOCSRFCHECK', 1);

// Sikker include af main.inc.php (ligger i /admin)
$main_path = realpath(__DIR__ . '/../../../main.inc.php');
if (! $main_path || ! file_exists($main_path)) {
    echo '<strong style="color:red">❌ Include of main.inc.php mislykkedes</strong><br>';
    echo 'Forsøgte path: ' . realpath(__DIR__ . '/../../../main.inc.php');
    exit;
}
require_once $main_path;

require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/custom/smartpick/class/ShipmondoPOC.class.php';

// Sprog og sideopsætning
$langs->load("admin");
$title = "Shipmondo POC Test";

// Adgangskontrol
if (! $user->admin) {
    accessforbidden();
}

// Hent konfiguration
$apiuser = dolibarr_get_const($db, 'SHIPMONDO_API_USER');
$apikey = dolibarr_get_const($db, 'SHIPMONDO_API_KEY');

// Gem indstillinger
if (!empty($_POST['save'])) {
    $res1 = dolibarr_set_const($db, 'SHIPMONDO_API_USER', GETPOST('apiuser', 'alpha'), 'chaine', 0, '', $conf->entity);
    $res2 = dolibarr_set_const($db, 'SHIPMONDO_API_KEY', GETPOST('apikey', 'alpha'), 'chaine', 0, '', $conf->entity);
    if ($res1 && $res2) {
        setEventMessages("✅ API-indstillinger gemt", null, 'mesgs');
        $apiuser = GETPOST('apiuser', 'alpha');
        $apikey = GETPOST('apikey', 'alpha');
    } else {
        setEventMessages("❌ Kunne ikke gemme indstillinger", null, 'errors');
    }
}

// Vis side
llxHeader('', $title);
print load_fiche_titre($title, '', 'setup');

// Test forbindelse
if (!empty($_POST['test'])) {
    $poc = new ShipmondoPOC($apiuser, $apikey);
    $result = $poc->testConnection();

    print '<div class="fichecenter">';

    if (isset($result['name'])) {
        // Direkte svar fra Shipmondo API (v3/account)
        print '<div class="ok">';
        print '✅ Forbindelse succesfuld!<br>';
        print 'Kontonavn: ' . dol_escape_htmltag($result['name']) . '<br>';
        print 'Email: ' . dol_escape_htmltag($result['email']) . '<br>';
        print 'Adresse: ' . dol_escape_htmltag($result['address_1']) . ', ' . dol_escape_htmltag($result['zip_code']) . ' ' . dol_escape_htmltag($result['city']);
        print '</div>';
    } elseif (isset($result['error'])) {
        print '<div class="error">❌ Fejl ved forbindelse:<br><pre>';
        print dol_escape_htmltag(print_r($result, true));
        print '</pre></div>';
    } else {
        print '<div class="error">❌ Ukendt svar fra API:<br><pre>';
        print dol_escape_htmltag(print_r($result, true));
        print '</pre></div>';
    }

    print '</div><br>';
}

// Formular
print '<form method="POST">';
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre"><td colspan="2">Shipmondo API Settings</td></tr>';
print '<tr><td class="fieldrequired">API User</td><td><input type="text" name="apiuser" class="flat" size="50" value="' . dol_escape_htmltag($apiuser) . '"></td></tr>';
print '<tr><td class="fieldrequired">API Key</td><td><input type="text" name="apikey" class="flat" size="50" value="' . dol_escape_htmltag($apikey) . '"></td></tr>';
print '</table>';
print '<br><div class="center">';
print '<input type="submit" class="button" name="save" value="Gem"> ';
print '<input type="submit" class="button" name="test" value="Test Shipmondo API">';
print '</div>';
print '</form>';

// Luk side
llxFooter();
$db->close();
