<?php
/**
 * SmartPick Auto-Shifts Cron Script
 * Køres automatisk af Dolibarr Cron hver nat
 * Forudser ordremængden 4 dage frem i tiden og opretter automatisk de nødvendige vagter.
 */

if (!defined('NOSESSION')) define('NOSESSION', '1');

require_once __DIR__ . '/../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/custom/smartpick/domain/labor/SmartPickShiftPlanner.class.php';

use SmartPick\Domain\Labor\SmartPickShiftPlanner;

$apiKey = $conf->global->SMARTPICK_MISTRAL_API_KEY;

if (empty($apiKey)) {
    print "FEJL: SMARTPICK_MISTRAL_API_KEY er ikke indstillet i Dolibarr administrationen.
";
    exit(1);
}

$planner = new SmartPickShiftPlanner($db);
$result = $planner->runAutoGenerateShiftsForDayPlus4($apiKey);

print json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "
";
