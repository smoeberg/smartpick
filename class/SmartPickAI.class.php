<?php
/**
 * SmartPickAI - AI-baseret lagerorganisering med Mistral AI
 */
require_once DOL_DOCUMENT_ROOT . '/custom/smartpick/class/SmartPickMistralAI.class.php';

class SmartPickAI
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Kør AI-slotting analyse via Mistral AI
     */
    public function runMistralSlottingAnalysis($apiKey, $days = 90)
    {
        $date_limit = date('Y-m-d H:i:s', strtotime("-$days days"));

        $sql = "SELECT cd.fk_product, p.ref, p.label, p.weight, COUNT(cd.rowid) as order_count, SUM(cd.qty) as total_qty ";
        $sql .= "FROM " . MAIN_DB_PREFIX . "commandedet cd ";
        $sql .= "JOIN " . MAIN_DB_PREFIX . "commande c ON c.rowid = cd.fk_commande ";
        $sql .= "JOIN " . MAIN_DB_PREFIX . "product p ON p.rowid = cd.fk_product ";
        $sql .= "WHERE c.date_creation >= '" . $this->db->escape($date_limit) . "' ";
        $sql .= "GROUP BY cd.fk_product, p.ref, p.label, p.weight ";
        $sql .= "ORDER BY order_count DESC ";
        $sql .= "LIMIT 50";

        $resql = $this->db->query($sql);
        $products = [];
        if ($resql) {
            while ($obj = $this->db->fetch_object($resql)) {
                $products[] = [
                    'product_id' => $obj->fk_product,
                    'ref' => $obj->ref,
                    'label' => $obj->label,
                    'weight_kg' => floatval($obj->weight),
                    'order_count' => intval($obj->order_count),
                    'total_qty_sold' => floatval($obj->total_qty)
                ];
            }
        }

        if (empty($products)) {
            return ['success' => false, 'error' => 'Ingen salgsdata fundet inden for de seneste ' . $days . ' dage.'];
        }

        $mistral = new SmartPickMistralAI($apiKey);
        return $mistral->generateSlottingOptimization($products);
    }
}
