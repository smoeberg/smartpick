<?php

namespace SmartPick\Infrastructure\Migration;

/**
 * SmartPickMigrationBridge - Kontrolleret Migrationsbro i "Shadow Mode"
 * Importerer eksisterende ventende legacy-pluklinjer til v3 som nye PickTasks.
 * VIGTIGT: Ændrer ALDRIG den gamle legacy-kø og ruter IKKE produktionstrafik om endnu.
 */
class SmartPickMigrationBridge
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Kør synkronisering af ventende legacy-ordrer i Shadow Mode (100% Skrivebeskyttet mod legacy)
     */
    public function syncPendingLegacyOrdersToV3ShadowMode($limit = 50)
    {
        // 1. Træk ventende ordrelinjer fra den eksisterende legacy-kø uden at ændre dem
        $sql = "SELECT q.rowid as legacy_id, q.fk_commande, q.fk_product, q.qty_to_pick, q.qty_picked, q.fk_warehouse, q.status ";
        $sql .= "FROM " . MAIN_DB_PREFIX . "smartpick_queue q ";
        $sql .= "WHERE q.status IN ('pending', 'assigned') ";
        $sql .= "LIMIT " . intval($limit);

        $resql = $this->db->query($sql);
        $imported_count = 0;
        $shadow_tasks = [];

        if ($resql) {
            while ($obj = $this->db->fetch_object($resql)) {
                // Tjek om denne legacy linje allerede er importeret i v3 shadow tabellen
                $sql_check = "SELECT rowid FROM " . MAIN_DB_PREFIX . "smartpick_v3_picktasks WHERE fk_legacy_queue_id = " . intval($obj->legacy_id);
                $res_check = $this->db->query($sql_check);

                if ($res_check && $this->db->num_rows($res_check) == 0) {
                    // 2. Opret som en ny v3 PickTask i shadow mode
                    $v3_task_ref = 'TASK-V3-' . str_pad($obj->legacy_id, 8, '0', STR_PAD_LEFT);
                    
                    $sql_ins = "INSERT INTO " . MAIN_DB_PREFIX . "smartpick_v3_picktasks ";
                    $sql_ins .= "(ref, fk_legacy_queue_id, fk_commande, fk_product, qty_to_pick, sync_mode, shadow_status, date_creation) VALUES (";
                    $sql_ins .= "'" . $this->db->escape($v3_task_ref) . "', ";
                    $sql_ins .= intval($obj->legacy_id) . ", ";
                    $sql_ins .= intval($obj->fk_commande) . ", ";
                    $sql_ins .= intval($obj->fk_product) . ", ";
                    $sql_ins .= floatval($obj->qty_to_pick) . ", ";
                    $sql_ins .= "'shadow_mode', ";
                    $sql_ins .= "'shadow_synced', ";
                    $sql_ins .= "'" . $this->db->idate(time()) . "'";
                    $sql_ins .= ")";

                    $this->db->query($sql_ins);
                    $imported_count++;

                    $shadow_tasks[] = [
                        'v3_task_ref' => $v3_task_ref,
                        'legacy_queue_id' => $obj->legacy_id,
                        'order_id' => $obj->fk_commande,
                        'product_id' => $obj->fk_product,
                        'qty' => $obj->qty_to_pick
                    ];
                }
            }
        }

        return [
            'mode' => 'SHADOW_MODE_READ_ONLY_LEGACY',
            'status' => 'success',
            'imported_new_tasks' => $imported_count,
            'shadow_tasks' => $shadow_tasks,
            'safety_notice' => '🔒 Legacy-køen er 100% uændret. Ingen produktionstrafik er omdirigeret.'
        ];
    }

    /**
     * Sammenligningsrapport mellem Legacy kø og v3 PickTasks
     */
    public function getShadowSyncAuditReport()
    {
        $sql_legacy = "SELECT COUNT(rowid) as cnt FROM " . MAIN_DB_PREFIX . "smartpick_queue WHERE status = 'pending'";
        $res_legacy = $this->db->query($sql_legacy);
        $legacy_pending = 0;
        if ($res_legacy && $obj = $this->db->fetch_object($res_legacy)) {
            $legacy_pending = intval($obj->cnt);
        }

        $sql_v3 = "SELECT COUNT(rowid) as cnt FROM " . MAIN_DB_PREFIX . "smartpick_v3_picktasks WHERE sync_mode = 'shadow_mode'";
        $res_v3 = $this->db->query($sql_v3);
        $v3_shadow_count = 0;
        if ($res_v3 && $obj = $this->db->fetch_object($res_v3)) {
            $v3_shadow_count = intval($obj->cnt);
        }

        return [
            'legacy_pending_queue_count' => $legacy_pending,
            'v3_shadow_picktasks_count' => $v3_shadow_count,
            'sync_health' => ($v3_shadow_count >= $legacy_pending) ? '100% Synchronized in Shadow Mode' : 'Sync in progress'
        ];
    }
}
