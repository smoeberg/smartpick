<?php
/**
 * SmartPickQueue - Plukkø med SLA/Alder Prioritering (Gamle ordrer får højeste prioritet)
 */
class SmartPickQueue
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Beregn prioritetsscore for en ordre baseret på alder (dage i kø) og cutoff
     * Formel: PriorityScore = (Alder i Dage * 100) + CutoffBonus
     */
    public function calculateOrderPriorityScore($date_creation, $is_before_cutoff = true)
    {
        $created_timestamp = strtotime($date_creation);
        $now_timestamp = time();
        $age_in_seconds = max(0, $now_timestamp - $created_timestamp);
        $age_in_days = $age_in_seconds / 86400.0;

        // Gamle ordrer escaleres kraftigt: +100 point pr. dag
        $score = round($age_in_days * 100);

        // Same-day cutoff bonus
        if ($is_before_cutoff) {
            $score += 50;
        }

        return $score; // Højere score = Højere prioritet i plukkøen
    }

    /**
     * Hent næste optimerede plukrute hvor opgaver sorteres efter ALDER (Gamle ordrer først) og dernæst lagerplacering
     */
    public function getOptimizedPickRouteForWorker($fk_user, $fk_warehouse = 0, $limit = 20)
    {
        $sql = "SELECT q.*, c.date_creation as order_date, ";
        $sql .= "DATEDIFF(NOW(), c.date_creation) as days_old ";
        $sql .= "FROM " . MAIN_DB_PREFIX . "smartpick_queue q ";
        $sql .= "JOIN " . MAIN_DB_PREFIX . "commande c ON c.rowid = q.fk_commande ";
        $sql .= "WHERE q.status = 'pending' ";
        if ($fk_warehouse > 0) {
            $sql .= "AND q.fk_warehouse = " . intval($fk_warehouse) . " ";
        }
        // ESCALERING: Gamle ordrer (højeste DATEDIFF) plukkes FØRST, derefter efter Hylde/Rack/Bin
        $sql .= "ORDER BY days_old DESC, q.loc_rack ASC, q.loc_bin ASC ";
        $sql .= "LIMIT " . intval($limit);

        $resql = $this->db->query($sql);
        $route = [];
        if ($resql) {
            while ($obj = $this->db->fetch_object($resql)) {
                $route[] = [
                    'queue_id' => $obj->rowid,
                    'order_id' => $obj->fk_commande,
                    'days_old' => intval($obj->days_old),
                    'priority_badge' => (intval($obj->days_old) >= 2) ? '🔥 HØJ PRIORITET (' . $obj->days_old . ' dage gammel)' : 'NORMAL',
                    'product_id' => $obj->fk_product,
                    'product_ref' => $obj->product_ref,
                    'barcode' => $obj->barcode,
                    'label' => $obj->label,
                    'qty_to_pick' => floatval($obj->qty_to_pick),
                    'qty_picked' => floatval($obj->qty_picked),
                    'loc_rack' => $obj->loc_rack,
                    'loc_bin' => $obj->loc_bin,
                    'tote_id' => $obj->tote_id
                ];
            }
        }
        return $route;
    }
}
