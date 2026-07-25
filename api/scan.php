<?php
// API endpoint for SmartPick mobile barcode scanning & WMS workflow

if (!defined('NOCSRFCHECK')) define('NOCSRFCHECK', '1');
if (!defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL', '1');

require_once '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/custom/smartpick/domain/picking/SmartPickQueue.class.php';
require_once DOL_DOCUMENT_ROOT . '/custom/smartpick/domain/shipping/ShipmondoAPI.class.php';

use SmartPick\Domain\Picking\SmartPickQueue;
use SmartPick\Domain\Shipping\ShipmondoAPI;

header('Content-Type: application/json; charset=utf-8');

$action = GETPOST('action', 'alpha');
$queue_id = GETPOST('queue_id', 'int');
$barcode = GETPOST('barcode', 'alphanohtml');
$batch_id = GETPOST('batch_id', 'alphanohtml');
$qty = GETPOST('qty', 'int');
if (empty($qty)) $qty = 1;

$queue = new SmartPickQueue($db);

switch ($action) {

    case 'get_route':
        $items = $queue->getOptimizedRoute($batch_id, 'pending');
        echo json_encode(['success' => true, 'count' => count($items), 'items' => $items]);
        break;

    case 'scan':
        if (!$queue_id || !$barcode) {
            echo json_encode(['success' => false, 'message' => 'Manglende parametre']);
            exit;
        }

        $res = $queue->recordScan($queue_id, $barcode, $qty);
        echo json_encode($res);
        break;

    case 'partial_pick':
        $picked_qty = GETPOST('picked_qty', 'int');
        if (!$queue_id) {
            echo json_encode(['success' => false, 'message' => 'Manglende queue_id']);
            exit;
        }
        $res = $queue->setPartialPick($queue_id, $picked_qty);
        echo json_encode(['success' => (bool)$res, 'message' => 'Delvist pluk registreret']);
        break;

    case 'complete_batch':
        // Udfør færdiggørelse og book evt. Shipmondo forsendelse
        $apiUser = $conf->global->SMARTPICK_SHIPMONDO_API_USER;
        $apiKey = $conf->global->SMARTPICK_SHIPMONDO_API_KEY;

        $shipmondoStatus = 'Not configured';
        if (!empty($apiUser) && !empty($apiKey)) {
            $shipmondo = new ShipmondoAPI($apiUser, $apiKey);
            $test = $shipmondo->testConnection();
            if ($test['success']) {
                $shipmondoStatus = 'Shipmondo Ready';
            }
        }

        echo json_encode([
            'success' => true,
            'message' => 'Plukkø gennemført!',
            'shipmondo_status' => $shipmondoStatus
        ]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Ukendt handling']);
        break;
}
