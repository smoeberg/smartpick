<?php
/**
 * SmartPickStats - Registrering af medarbejderbelastning, løftede kilo og arbejdsforhold
 */
class SmartPickStats
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Gem pluk-hændelse med beregnet løftevægt og tidsforbrug
     */
    public function logPickEvent($fk_user, $fk_product, $qty_picked, $duration_seconds)
    {
        // Hent produktets vægt fra Dolibarr product tabel
        $sql = "SELECT weight, weight_units FROM " . MAIN_DB_PREFIX . "product WHERE rowid = " . intval($fk_product);
        $res = $this->db->query($sql);

        $weight_kg = 0.0;
        if ($res && $obj = $this->db->fetch_object($res)) {
            // Konverter vægt til kg baseret på weight_units
            $raw_weight = floatval($obj->weight);
            $unit = intval($obj->weight_units); // 0 = kg, -3 = g, etc.
            $weight_kg = $raw_weight * pow(10, $unit);
        }

        $total_lifted_kg = $weight_kg * floatval($qty_picked);

        $insert_sql = "INSERT INTO " . MAIN_DB_PREFIX . "smartpick_user_logs ";
        $insert_sql .= "(fk_user, fk_product, qty_picked, weight_lifted_kg, duration_sec, date_creation) VALUES (";
        $insert_sql .= intval($fk_user) . ", ";
        $insert_sql .= intval($fk_product) . ", ";
        $insert_sql .= floatval($qty_picked) . ", ";
        $insert_sql .= floatval($total_lifted_kg) . ", ";
        $insert_sql .= intval($duration_seconds) . ", ";
        $insert_sql .= "'" . $this->db->idate(time()) . "'";
        $insert_sql .= ")";

        return $this->db->query($insert_sql);
    }

    /**
     * Hent daglig statistik for medarbejderen (samlet løftet kg, antal pluk, plukhastighed)
     */
    public function getUserDailyStats($fk_user, $date = null)
    {
        if (empty($date)) $date = date('Y-m-d');

        $sql = "SELECT COUNT(rowid) as total_picks, SUM(qty_picked) as total_qty, SUM(weight_lifted_kg) as total_weight_kg, SUM(duration_sec) as total_sec ";
        $sql .= "FROM " . MAIN_DB_PREFIX . "smartpick_user_logs ";
        $sql .= "WHERE fk_user = " . intval($fk_user) . " ";
        $sql .= "AND DATE(date_creation) = '" . $this->db->escape($date) . "'";

        $res = $this->db->query($sql);
        if ($res && $obj = $this->db->fetch_object($res)) {
            return [
                'total_picks' => intval($obj->total_picks),
                'total_qty' => floatval($obj->total_qty),
                'total_weight_kg' => round(floatval($obj->total_weight_kg), 2),
                'total_time_minutes' => round(intval($obj->total_sec) / 60, 1)
            ];
        }

        return ['total_picks' => 0, 'total_qty' => 0, 'total_weight_kg' => 0, 'total_time_minutes' => 0];
    }
}
