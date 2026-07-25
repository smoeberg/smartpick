<?php
/**
 * SmartPickAllocation - Handling for hvor og hvordan ordrer samles (Consolidation & Put-Wall / Tote Tracking)
 */
class SmartPickAllocation
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Tildel en specifik samlekasse / Put-wall hylde (Tote) til en ordre ved samlestationen
     *
     * @param int $fk_commande Ordre ID i Dolibarr
     * @param string $tote_id Samlekassens ID (f.eks. 'TOTE-42' eller 'HYLDE-B3')
     */
    public function assignOrderToTote($fk_commande, $tote_id)
    {
        $sql = "UPDATE " . MAIN_DB_PREFIX . "smartpick_queue SET ";
        $sql .= "tote_id = '" . $this->db->escape($tote_id) . "' ";
        $sql .= "WHERE fk_commande = " . intval($fk_commande);

        return $this->db->query($sql);
    }

    /**
     * Tjek realtid samlestatus for en ordre på pakkeskærmen
     */
    public function getConsolidationStatus($fk_commande)
    {
        $sql = "SELECT q.*, c.ref as order_ref ";
        $sql .= "FROM " . MAIN_DB_PREFIX . "smartpick_queue q ";
        $sql .= "JOIN " . MAIN_DB_PREFIX . "commande c ON c.rowid = q.fk_commande ";
        $sql .= "WHERE q.fk_commande = " . intval($fk_commande);

        $resql = $this->db->query($sql);
        $total_lines = 0;
        $picked_lines = 0;
        $tote_id = '';
        $zones_pending = [];

        if ($resql) {
            while ($obj = $this->db->fetch_object($resql)) {
                $total_lines++;
                if (!empty($obj->tote_id)) $tote_id = $obj->tote_id;

                if ($obj->status === 'picked') {
                    $picked_lines++;
                } else {
                    $zone = !empty($obj->loc_rack) ? $obj->loc_rack : 'Generel Zone';
                    if (!in_array($zone, $zones_pending)) {
                        $zones_pending[] = $zone;
                    }
                }
            }
        }

        $is_complete = ($total_lines > 0 && $total_lines === $picked_lines);

        return [
            'fk_commande' => $fk_commande,
            'tote_id' => $tote_id ? $tote_id : 'Ikke tildelt endnu',
            'total_lines' => $total_lines,
            'picked_lines' => $picked_lines,
            'is_complete' => $is_complete,
            'status_label' => $is_complete ? '🟢 KLAR TIL PAKNING & SHIPMONDO PRINT' : '🟡 DELVIST ANKOMMET - VENTER PÅ ZONE: ' . implode(', ', $zones_pending),
            'zones_pending' => $zones_pending
        ];
    }
}
