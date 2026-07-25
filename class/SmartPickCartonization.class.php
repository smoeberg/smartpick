<?php
/**
 * SmartPickCartonization - Automatisk beregning af optimal Dolibarr papkasse/emballage
 */
class SmartPickCartonization
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Hent alle aktiverede Dolibarr Emballage/Papkasse produkter (Produkter af typen 'packaging' eller i emballage-kategori)
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

        // Fallback standard papkasser hvis ikke oprettet i Dolibarr endnu
        if (empty($boxes)) {
            $boxes = [
                ['product_id' => 901, 'ref' => 'BOX-S', 'label' => 'Papkasse Lille (20x15x10 cm)', 'barcode' => '570000000001', 'volume_m3' => 0.003, 'stock_qty' => 150],
                ['product_id' => 902, 'ref' => 'BOX-M', 'label' => 'Papkasse Mellem (30x20x15 cm)', 'barcode' => '570000000002', 'volume_m3' => 0.009, 'stock_qty' => 200],
                ['product_id' => 903, 'ref' => 'BOX-L', 'label' => 'Papkasse Stor (50x30x30 cm)', 'barcode' => '570000000003', 'volume_m3' => 0.045, 'stock_qty' => 80]
            ];
        }

        return $boxes;
    }

    /**
     * Beregn den optimale papkasse til en ordre ud fra samlet ordrevolumen
     *
     * @param int $fk_commande Ordre ID
     */
    public function calculateOptimalBoxForOrder($fk_commande)
    {
        // 1. Beregn samlet volumen for ordren
        $sql = "SELECT d.qty, p.volume, p.length, p.width, p.height ";
        $sql .= "FROM " . MAIN_DB_PREFIX . "commandedet d ";
        $sql .= "JOIN " . MAIN_DB_PREFIX . "product p ON p.rowid = d.fk_product ";
        $sql .= "WHERE d.fk_commande = " . intval($fk_commande);

        $resql = $this->db->query($sql);
        $total_order_volume = 0.0;
        if ($resql) {
            while ($obj = $this->db->fetch_object($resql)) {
                $item_vol = floatval($obj->volume) > 0 ? floatval($obj->volume) : 0.0005; // Standard 0.5 liter hvis ikke angivet
                $total_order_volume += ($item_vol * floatval($obj->qty));
            }
        }

        // Tilsæt 15% luft/fyldmateriale margin
        $required_volume = $total_order_volume * 1.15;

        // 2. Find den mindste papkasse i Dolibarr der kan rumme $required_volume
        $available_boxes = $this->getAvailablePackagingBoxes();
        $recommended_box = $available_boxes[count($available_boxes) - 1]; // Standard den største

        foreach ($available_boxes as $box) {
            if ($box['volume_m3'] >= $required_volume && $box['stock_qty'] > 0) {
                $recommended_box = $box;
                break;
            }
        }

        return [
            'order_id' => $fk_commande,
            'total_order_volume_m3' => round($total_order_volume, 5),
            'required_volume_with_buffer_m3' => round($required_volume, 5),
            'recommended_box' => $recommended_box
        ];
    }
}
