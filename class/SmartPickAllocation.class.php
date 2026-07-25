<?php
/**
 * SmartPickAllocation - Kobling mellem Plukkerkasse (Picker Tote) og Pakkebord (Packing Station)
 */
class SmartPickAllocation
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Registrer at et specifikt produkt på en ordre er plukket ned i plukkerens unikke kasse
     *
     * @param int $queue_id ID på pluklinjen
     * @param string $picker_tote_id Plukkerens kasse ID (f.eks. 'KASSE-RØD-01' eller 'CART1-A')
     */
    public function recordPickerTote($queue_id, $picker_tote_id)
    {
        $sql = "UPDATE " . MAIN_DB_PREFIX . "smartpick_queue SET ";
        $sql .= "tote_id = '" . $this->db->escape($picker_tote_id) . "', ";
        $sql .= "status = 'picked' ";
        $sql .= "WHERE rowid = " . intval($queue_id);

        return $this->db->query($sql);
    }

    /**
     * Hent pakkebordsvisning for en ordre: Vis præcis hvilke kasser pakkeren skal tage de enkelte produkter fra
     *
     * @param int $fk_commande Ordre ID
     */
    public function getPackingInstructionsForOrder($fk_commande)
    {
        $sql = "SELECT q.*, p.label as product_name, p.ref as product_ref, p.barcode ";
        $sql .= "FROM " . MAIN_DB_PREFIX . "smartpick_queue q ";
        $sql .= "JOIN " . MAIN_DB_PREFIX . "product p ON p.rowid = q.fk_product ";
        $sql .= "WHERE q.fk_commande = " . intval($fk_commande);

        $resql = $this->db->query($sql);
        $items = [];
        $totes_required = [];

        if ($resql) {
            while ($obj = $this->db->fetch_object($resql)) {
                $tote = !empty($obj->tote_id) ? $obj->tote_id : 'Ukendt plukkerkasse';
                if (!in_array($tote, $totes_required)) {
                    $totes_required[] = $tote;
                }

                $items[] = [
                    'queue_id' => $obj->rowid,
                    'product_ref' => $obj->product_ref,
                    'product_name' => $obj->product_name,
                    'barcode' => $obj->barcode,
                    'qty_picked' => $obj->qty_picked,
                    'picker_tote' => $tote,
                    'instruction' => 'Tag ' . floatval($obj->qty_picked) . ' stki fra kasse: ' . $tote
                ];
            }
        }

        return [
            'fk_commande' => $fk_commande,
            'totes_to_collect_from' => $totes_required,
            'items' => $items
        ];
    }
}
