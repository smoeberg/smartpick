<?php
/**
 * SmartPickReplenishment - Genopfyldning af pluklager fra færdiglager/bufferlager
 * Anvender Dolibarr standard MouvementStock og product_warehouse tærskler
 */
class SmartPickReplenishment
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Tjek om pluklagerets beholdning er faldet under minimumsgrænse (seuil_stock)
     *
     * @param int $id_warehouse_pick ID for pluklageret
     * @param int $id_warehouse_buffer ID for overskuds/bufferlageret
     */
    public function checkReplenishmentNeeds($id_warehouse_pick, $id_warehouse_buffer)
    {
        // Hent produkter hvor beholdningen på pluklageret er under minimum
        $sql = "SELECT pw.fk_product, pw.stock as current_stock, pw.seuil_stock as min_stock, pw.desiredstock as target_stock, ";
        $sql .= "p.ref, p.label ";
        $sql .= "FROM " . MAIN_DB_PREFIX . "product_warehouse pw ";
        $sql .= "JOIN " . MAIN_DB_PREFIX . "product p ON p.rowid = pw.fk_product ";
        $sql .= "WHERE pw.fk_entrepot = " . intval($id_warehouse_pick) . " ";
        $sql .= "AND pw.stock <= pw.seuil_stock ";
        $sql .= "AND pw.seuil_stock > 0";

        $resql = $this->db->query($sql);
        $needs = [];

        if ($resql) {
            while ($obj = $this->db->fetch_object($resql)) {
                $qty_needed = ($obj->target_stock > 0) ? ($obj->target_stock - $obj->current_stock) : ($obj->min_stock * 2 - $obj->current_stock);

                // Tjek om der er nok beholdning på bufferlageret
                $buffer_stock = 0;
                $sql_buf = "SELECT stock FROM " . MAIN_DB_PREFIX . "product_warehouse WHERE fk_product = " . intval($obj->fk_product) . " AND fk_entrepot = " . intval($id_warehouse_buffer);
                $res_buf = $this->db->query($sql_buf);
                if ($res_buf && $bobj = $this->db->fetch_object($res_buf)) {
                    $buffer_stock = $bobj->stock;
                }

                $needs[] = [
                    'fk_product' => $obj->fk_product,
                    'ref' => $obj->ref,
                    'label' => $obj->label,
                    'current_pick_stock' => $obj->current_stock,
                    'buffer_stock' => $buffer_stock,
                    'qty_to_transfer' => min($qty_needed, $buffer_stock)
                ];
            }
        }

        return $needs;
    }

    /**
     * Udfør genopfyldning via Dolibarr standard MouvementStock (Flyt fra buffer til pluklager)
     */
    public function executeReplenishmentTransfer($fk_product, $qty, $id_warehouse_from, $id_warehouse_to, $user)
    {
        require_once DOL_DOCUMENT_ROOT . '/product/stock/class/mouvementstock.class.php';

        $move = new MouvementStock($this->db);
        $result = $move->_create(
            $user,
            $fk_product,
            $id_warehouse_from,
            -$qty,
            0,
            'SmartPick Genopfyldning fra overskudslager'
        );

        if ($result > 0) {
            $move_in = new MouvementStock($this->db);
            $move_in->_create(
                $user,
                $fk_product,
                $id_warehouse_to,
                $qty,
                0,
                'SmartPick Genopfyldning til pluklager'
            );
            return true;
        }

        return false;
    }
}
