<?php
/**
 * SmartPickStats - Tæt kobling til Dolibarr Standard Medarbejdere (llx_user / User class)
 */
require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';

class SmartPickStats
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Hent liste af aktive Dolibarr standard medarbejdere med SmartPick/Lager tilladelser
     */
    public function getActiveDolibarrWorkers()
    {
        $sql = "SELECT u.rowid, u.login, u.firstname, u.lastname, u.email ";
        $sql .= "FROM " . MAIN_DB_PREFIX . "user u ";
        $sql .= "WHERE u.statut = 1 "; // Aktive brugere
        $sql .= "ORDER BY u.firstname ASC, u.lastname ASC";

        $resql = $this->db->query($sql);
        $workers = [];
        if ($resql) {
            while ($obj = $this->db->fetch_object($resql)) {
                $workers[] = [
                    'id' => $obj->rowid,
                    'login' => $obj->login,
                    'full_name' => trim($obj->firstname . ' ' . $obj->lastname),
                    'email' => $obj->email
                ];
            }
        }
        return $workers;
    }

    /**
     * Log plukhændelse for Dolibarr medarbejder
     */
    public function logPickEventForDolibarrUser($fk_user, $fk_product, $qty_picked, $duration_seconds)
    {
        // Valider at brugeren findes i Dolibarr llx_user
        $dol_user = new User($this->db);
        if ($dol_user->fetch($fk_user) <= 0) {
            return false;
        }

        // Hent produktvægt
        $sql = "SELECT weight, weight_units FROM " . MAIN_DB_PREFIX . "product WHERE rowid = " . intval($fk_product);
        $res = $this->db->query($sql);

        $weight_kg = 0.0;
        if ($res && $obj = $this->db->fetch_object($res)) {
            $raw_weight = floatval($obj->weight);
            $unit = intval($obj->weight_units);
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
     * Hent rapport over Dolibarr medarbejderens daglige pluk og samlede løftede kilo
     */
    public function getDolibarrUserReport($fk_user, $date = null)
    {
        if (empty($date)) $date = date('Y-m-d');

        $dol_user = new User($this->db);
        if ($dol_user->fetch($fk_user) <= 0) return null;

        $sql = "SELECT COUNT(rowid) as total_picks, SUM(qty_picked) as total_qty, SUM(weight_lifted_kg) as total_weight_kg, SUM(duration_sec) as total_sec ";
        $sql .= "FROM " . MAIN_DB_PREFIX . "smartpick_user_logs ";
        $sql .= "WHERE fk_user = " . intval($fk_user) . " ";
        $sql .= "AND DATE(date_creation) = '" . $this->db->escape($date) . "'";

        $res = $this->db->query($sql);
        if ($res && $obj = $this->db->fetch_object($res)) {
            return [
                'user_id' => $dol_user->id,
                'user_login' => $dol_user->login,
                'user_full_name' => $dol_user->getFullName($langs ?? null),
                'total_picks' => intval($obj->total_picks),
                'total_qty' => floatval($obj->total_qty),
                'total_weight_kg' => round(floatval($obj->total_weight_kg), 2),
                'total_time_minutes' => round(intval($obj->total_sec) / 60, 1)
            ];
        }

        return null;
    }
}
