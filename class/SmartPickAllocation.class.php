<?php
/**
 * SmartPickAllocation - Zone-pluk, medarbejder-tildeling & samlestation
 */
class SmartPickAllocation
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Tildel pluklinjer eller en hel ordre til en specifik medarbejder
     */
    public function assignOrderToUser($fk_commande, $fk_user, $zone = null)
    {
        $sql = "UPDATE " . MAIN_DB_PREFIX . "smartpick_queue SET ";
        $sql .= "fk_user_assigned = " . intval($fk_user) . " ";
        $sql .= "WHERE fk_commande = " . intval($fk_commande) . " ";

        if (!empty($zone)) {
            $sql .= "AND loc_rack LIKE '" . $this->db->escape($zone) . "%' ";
        }

        return $this->db->query($sql);
    }

    /**
     * Tjek om alle zone-pluk til en ordre er ankommet til samlestationen (Konsolidering)
     */
    public function checkConsolidationStatus($fk_commande)
    {
        $sql = "SELECT COUNT(rowid) as total_lines, ";
        $sql .= "SUM(CASE WHEN status = 'picked' THEN 1 ELSE 0 END) as picked_lines ";
        $sql .= "FROM " . MAIN_DB_PREFIX . "smartpick_queue ";
        $sql .= "WHERE fk_commande = " . intval($fk_commande);

        $resql = $this->db->query($sql);
        if ($resql && $obj = $this->db->fetch_object($resql)) {
            $is_complete = ($obj->total_lines > 0 && $obj->total_lines == $obj->picked_lines);
            return [
                'fk_commande' => $fk_commande,
                'total_lines' => intval($obj->total_lines),
                'picked_lines' => intval($obj->picked_lines),
                'is_ready_for_packing' => $is_complete
            ];
        }

        return ['is_ready_for_packing' => false];
    }
}
