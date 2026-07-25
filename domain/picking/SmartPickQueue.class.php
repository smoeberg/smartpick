<?php

namespace SmartPick\Domain\Picking;

/**
 * SmartPickQueue - Plukkø med Defekt/Manglende Vare håndtering & Restordre-opsplitning
 */
class SmartPickQueue
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Håndter hvis en vare på lagerhylden er defekt, beskadiget eller mangler
     *
     * @param int $queue_id Pluklinje ID
     * @param int $fk_user Medarbejder ID
     * @param float $qty_defective Antal defekte/manglende varer
     * @param string $reason Årsag ('defective', 'missing', 'damaged')
     */
    public function markItemDefectiveOrMissing($queue_id, $fk_user, $qty_defective, $reason = 'defective')
    {
        // 1. Hent oplysninger om pluklinjen
        $sql = "SELECT q.*, p.ref as product_ref FROM " . MAIN_DB_PREFIX . "smartpick_queue q ";
        $sql .= "JOIN " . MAIN_DB_PREFIX . "product p ON p.rowid = q.fk_product ";
        $sql .= "WHERE q.rowid = " . intval($queue_id);

        $resql = $this->db->query($sql);
        if (!$resql || !($obj = $this->db->fetch_object($resql))) {
            return ['status' => 'error', 'message' => 'Pluklinje ikke fundet'];
        }

        $fk_commande = $obj->fk_commande;
        $fk_product = $obj->fk_product;
        $qty_to_pick = floatval($obj->qty_to_pick);
        $qty_already_picked = floatval($obj->qty_picked);

        // 2. Juster plukkøen: Sæt ordren på delvis pluk / restordre
        $qty_actually_picked = max(0, $qty_to_pick - $qty_defective);

        // Opdater nuværende linje som delvist plukket / mangler
        $this->db->query("UPDATE " . MAIN_DB_PREFIX . "smartpick_queue SET qty_picked = " . floatval($qty_actually_picked) . ", status = 'partial_backorder' WHERE rowid = " . intval($queue_id));

        // 3. Juster lagerbeholdningen i Dolibarr (Registrer defekt/lagerkorrektion)
        require_once DOL_DOCUMENT_ROOT . '/product/stock/class/mouvementstock.class.php';
        $move = new MouvementStock($this->db);
        $move->origin = 'smartpick_defective';
        $move->fk_origin = $queue_id;

        // Træk den defekte vare ud af salgslageret
        $inventory_note = "SmartPick: Vare skrottet/defekt fundet under pluk af bruger #$fk_user ($reason)";
        $move->_create($fk_user, $fk_product, $obj->fk_warehouse, -$qty_defective, 0, $inventory_note);

        // 4. Send besked/log til Dolibarr kundeservice om restordre på ordren
        $sql_log = "INSERT INTO " . MAIN_DB_PREFIX . "smartpick_user_logs ";
        $sql_log .= "(fk_user, fk_product, qty_picked, duration_sec, date_creation) VALUES (";
        $sql_log .= intval($fk_user) . ", " . intval($fk_product) . ", 0, 0, '" . $this->db->idate(time()) . "')";
        $this->db->query($sql_log);

        return [
            'status' => 'success',
            'order_id' => $fk_commande,
            'qty_picked' => $qty_actually_picked,
            'qty_backordered' => $qty_defective,
            'message' => "⚠️ Vare $obj->product_ref registreret som $reason ($qty_defective stk). Resten af ordren fortsætter til pakning!"
        ];
    }
}
