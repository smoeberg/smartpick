<?php
/**
 * SmartPickCartonization - Dynamisk Emballage-Beregning EFTER Pluk (Korrigeret for defekte/manglende varer)
 */
class SmartPickCartonization
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Hent alle aktiverede Dolibarr Emballage/Papkasse produkter
     */
    public function getAvailablePackagingBoxes()
    {
        $sql = "SELECT p.rowid, p.ref, p.label, p.barcode, p.volume, p.length, p.width, p.height, ";
        $sql .= "s.real as stock_qty ";
        $sql .= "FROM " . MAIN_DB_PREFIX . "product p ";
        $sql .= "LEFT JOIN " . MAIN_DB_PREFIX . "product_stock s ON s.fk_product = p.rowid ";
        $sql .= "WHERE p.fk_product_type = 0 AND p.ref LIKE 'BOX-%' AND p.tosell = 1 ";
        $sql .= "ORDER BY p.volume ASC";

        $resql = $this->db->query($sql);
        $boxes = [];
        if ($resql) {
            while ($obj = $this->db->fetch_object($resql)) {
                $boxes[] = [
                    'product_id' => $obj->rowid,
                    'ref' => $obj->ref,
                    'label' => $obj->label,
                    'barcode' => $obj->barcode,
                    'volume_m3' => floatval($obj->volume),
                    'length_cm' => floatval($obj->length),
                    'width_cm' => floatval($obj->width),
                    'height_cm' => floatval($obj->height),
                    'stock_qty' => floatval($obj->stock_qty)
                ];
            }
        }

        if (empty($boxes)) {
            $boxes = [
                ['product_id' => 901, 'ref' => 'BOX-S', 'label' => 'Papkasse Lille (20x15x10 cm)', 'barcode' => '570000000001', 'volume_m3' => 0.003, 'stock_qty' => 150],
                ['product_id' => 902, 'ref' => 'BOX-M', 'label' => 'Papkasse Mellem (30x20x15 cm)', 'barcode' => '570000000002', 'volume_m3' => 0.009, 'stock_qty' => 200],
                ['product_id' => 903, 'ref' => 'BOX-L', 'label' => 'Papkasse Stor (50x30x30 cm)', 'barcode' => '570000000003', 'volume_m3' => 0.045, 'stock_qty' => 80],
                ['product_id' => 904, 'ref' => 'BOX-XL', 'label' => 'Papkasse Ekstra Stor / XL Vare (80x50x50 cm)', 'barcode' => '570000000004', 'volume_m3' => 0.200, 'stock_qty' => 40]
            ];
        }

        return $boxes;
    }

    /**
     * DYNAMISK EMBALLAGE-BEREGNING EFTER PLUK:
     * Beregn den optimale papkasse KUN ud fra de varer der reelt er plukket igennem plukkøen
     */
    public function calculatePostPickBoxForPickedItems($fk_commande)
    {
        $sql = "SELECT q.qty_picked, p.volume, p.length, p.width, p.height, p.ref as product_ref ";
        $sql .= "FROM " . MAIN_DB_PREFIX . "smartpick_queue q ";
        $sql .= "JOIN " . MAIN_DB_PREFIX . "product p ON p.rowid = q.fk_product ";
        $sql .= "WHERE q.fk_commande = " . intval($fk_commande) . " AND q.qty_picked > 0";

        $resql = $this->db->query($sql);
        $total_picked_volume = 0.0;
        $max_item_length = 0.0;

        if ($resql) {
            while ($obj = $this->db->fetch_object($resql)) {
                $item_vol = floatval($obj->volume) > 0 ? floatval($obj->volume) : 0.0008;
                $total_picked_volume += ($item_vol * floatval($obj->qty_picked));

                if (floatval($obj->length) > $max_item_length) {
                    $max_item_length = floatval($obj->length);
                }
            }
        }

        $required_volume = $total_picked_volume * 1.15;
        $available_boxes = $this->getAvailablePackagingBoxes();
        $recommended_box = $available_boxes[count($available_boxes) - 1];

        foreach ($available_boxes as $box) {
            if ($box['volume_m3'] >= $required_volume && $box['stock_qty'] > 0) {
                $recommended_box = $box;
                break;
            }
        }

        return [
            'order_id' => $fk_commande,
            'calculation_timing' => 'POST_PICK_DYNAMIC',
            'actually_picked_volume_m3' => round($total_picked_volume, 5),
            'required_volume_with_margin_m3' => round($required_volume, 5),
            'recommended_box' => $recommended_box
        ];
    }
}
