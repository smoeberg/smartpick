<?php
/**
 * SmartPickAI - AI & Algoritme-baseret lagerorganisering (Slotting / ABC-analyse)
 */
class SmartPickAI
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Beregn ABC-frekvens for alle produkter baseret på salgshistorik
     * A-varer: Top 20% hyppigst plukkede varer (skal placeres nærmest pakke-udgang)
     * B-varer: Næste 30%
     * C-varer: Resterende 50%
     */
    public function calculateABCAnalysis($days = 90)
    {
        $date_limit = date('Y-m-d H:i:s', strtotime("-$days days"));

        $sql = "SELECT cd.fk_product, COUNT(cd.rowid) as order_count, SUM(cd.qty) as total_qty ";
        $sql .= "FROM " . MAIN_DB_PREFIX . "commandedet cd ";
        $sql .= "JOIN " . MAIN_DB_PREFIX . "commande c ON c.rowid = cd.fk_commande ";
        $sql .= "WHERE c.date_creation >= '" . $this->db->escape($date_limit) . "' ";
        $sql .= "AND cd.fk_product > 0 ";
        $sql .= "GROUP BY cd.fk_product ";
        $sql .= "ORDER BY order_count DESC, total_qty DESC";

        $resql = $this->db->query($sql);
        $products = [];
        if ($resql) {
            while ($obj = $this->db->fetch_object($resql)) {
                $products[] = $obj;
            }
        }

        $total = count($products);
        if ($total == 0) return [];

        $a_count = ceil($total * 0.20);
        $b_count = ceil($total * 0.30);

        $recommendations = [];
        foreach ($products as $idx => $prod) {
            $class = 'C';
            $suggested_zone = 'Zone C (Fjernhylde)';

            if ($idx < $a_count) {
                $class = 'A';
                $suggested_zone = 'Zone A (Tættest på pakkeudgang / Lave hylder)';
            } elseif ($idx < ($a_count + $b_count)) {
                $class = 'B';
                $suggested_zone = 'Zone B (Midterste gang)';
            }

            $recommendations[] = [
                'fk_product' => $prod->fk_product,
                'order_count' => $prod->order_count,
                'total_qty' => $prod->total_qty,
                'abc_class' => $class,
                'suggested_zone' => $suggested_zone
            ];
        }

        return $recommendations;
    }
}
