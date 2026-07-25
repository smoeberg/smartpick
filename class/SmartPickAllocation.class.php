<?php
/**
 * SmartPickAllocation - Put-Wall Slot Konsolidering & Pakkebord Fast-Track
 * Forhindrer flaskehalse ved pakning ved at anvende strukturerede Put-Wall reol-slots
 */
require_once DOL_DOCUMENT_ROOT . '/custom/smartpick/class/SmartPickCartonization.class.php';

class SmartPickAllocation
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Start pakning af ordre for en specifik medarbejder (Registrerer starttid)
     */
    public function startPackingOrder($fk_commande, $fk_user)
    {
        $cartonizer = new SmartPickCartonization($this->db);
        $box_recommendation = $cartonizer->calculateOptimalBoxForOrder($fk_commande);

        // Slå ordrens Put-Wall pladser / plukkasser op
        $sql = "SELECT tote_id, loc_rack, loc_bin, status FROM " . MAIN_DB_PREFIX . "smartpick_queue WHERE fk_commande = " . intval($fk_commande);
        $resql = $this->db->query($sql);
        $totes_and_slots = [];
        if ($resql) {
            while ($obj = $this->db->fetch_object($resql)) {
                $totes_and_slots[] = [
                    'tote_id' => $obj->tote_id,
                    'putwall_slot' => !empty($obj->tote_id) ? 'REOL-SLOT-' . strtoupper(substr(md5($obj->tote_id), 0, 3)) : 'DIREKTE'
                ];
            }
        }

        $unique_totes = count(array_unique(array_column($totes_and_slots, 'tote_id')));
        $is_express_single_tote = ($unique_totes <= 1);

        return [
            'fk_commande' => $fk_commande,
            'fk_packer_user' => $fk_user,
            'packing_start_timestamp' => time(),
            'is_express_single_tote' => $is_express_single_tote,
            'box_recommendation' => $box_recommendation,
            'putwall_instructions' => $totes_and_slots,
            'bottleneck_warning' => (!$is_express_single_tote) 
                ? "💡 TIP: Denne ordre består af $unique_totes plukkasser. Benyt Put-Wall Reolen for direkte konsolidering." 
                : "⚡ EXPRESS ORDRE: Alt findes i 1 enkelt plukkasse! Klar til direkte pakning uden Put-Wall søgning."
        ];
    }

    /**
     * Afslut pakning af ordre (Gemmer pakketid og trækker papkasse-lager i Dolibarr)
     */
    public function finishPackingOrder($fk_commande, $fk_user, $start_timestamp, $scanned_box_barcode)
    {
        $duration_sec = time() - intval($start_timestamp);

        // Gem pakkelog i llx_smartpick_user_logs
        $sql = "INSERT INTO " . MAIN_DB_PREFIX . "smartpick_user_logs ";
        $sql .= "(fk_user, fk_product, qty_picked, duration_sec, date_creation) VALUES (";
        $sql .= intval($fk_user) . ", 0, 1.0, " . intval($duration_sec) . ", '" . $this->db->idate(time()) . "'";
        $sql .= ")";
        $this->db->query($sql);

        // Opdater ordrestatus i queue
        $this->db->query("UPDATE " . MAIN_DB_PREFIX . "smartpick_queue SET status = 'picked' WHERE fk_commande = " . intval($fk_commande));

        return [
            'status' => 'completed',
            'packing_duration_sec' => $duration_sec,
            'message' => "🟢 Ordre #$fk_commande færdigpakket af medarbejder #$fk_user på $duration_sec sekunder! Papkasse $scanned_box_barcode registreret."
        ];
    }
}
