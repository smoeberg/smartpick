<?php
/**
 * SmartPickShiftPlanner - Vagtplanlægning, pluk-cutoff og arbejdstidsstyring
 */
require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';

class SmartPickShiftPlanner
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Opret en vagt i systemet
     *
     * @param string $shift_date Dato (YYYY-MM-DD)
     * @param string $start_time Starttid (HH:MM)
     * @param string $end_time Sluttid (HH:MM)
     * @param int $max_pickers Maksimalt antal plukkere på denne vagt
     * @param string $cutoff_time Cutoff tidspunkt for same-day shipping (f.eks. '13:30')
     */
    public function createShift($shift_date, $start_time, $end_time, $max_pickers = 5, $cutoff_time = '13:30')
    {
        $sql = "INSERT INTO " . MAIN_DB_PREFIX . "smartpick_shifts ";
        $sql .= "(shift_date, start_time, end_time, max_pickers, cutoff_time, date_creation) VALUES (";
        $sql .= "'" . $this->db->escape($shift_date) . "', ";
        $sql .= "'" . $this->db->escape($start_time) . "', ";
        $sql .= "'" . $this->db->escape($end_time) . "', ";
        $sql .= intval($max_pickers) . ", ";
        $sql .= "'" . $this->db->escape($cutoff_time) . "', ";
        $sql .= "'" . $this->db->idate(time()) . "'";
        $sql .= ")";

        return $this->db->query($sql);
    }

    /**
     * En Dolibarr medarbejder vælger/tilmelder sig en vagt
     */
    public function assignWorkerToShift($fk_shift, $fk_user)
    {
        $dol_user = new User($this->db);
        if ($dol_user->fetch($fk_user) <= 0) return false;

        $sql = "INSERT INTO " . MAIN_DB_PREFIX . "smartpick_user_shifts (fk_shift, fk_user, status) VALUES (";
        $sql .= intval($fk_shift) . ", ";
        $sql .= intval($fk_user) . ", ";
        $sql .= "'confirmed'";
        $sql .= ")";

        return $this->db->query($sql);
    }

    /**
     * Hent dagens arbejds- og pluk-cutoff status
     */
    public function getDailyCutoffStatus($date = null)
    {
        if (empty($date)) $date = date('Y-m-d');

        $sql = "SELECT * FROM " . MAIN_DB_PREFIX . "smartpick_shifts WHERE shift_date = '" . $this->db->escape($date) . "'";
        $res = $this->db->query($sql);

        if ($res && $obj = $this->db->fetch_object($res)) {
            $now = date('H:i');
            $cutoff = $obj->cutoff_time;
            $is_before_cutoff = ($now <= $cutoff);

            return [
                'shift_date' => $obj->shift_date,
                'start_time' => $obj->start_time,
                'end_time' => $obj->end_time,
                'cutoff_time' => $cutoff,
                'is_before_cutoff' => $is_before_cutoff,
                'status_message' => $is_before_cutoff 
                    ? "🟢 Pluk i gang for Same-Day Afhentning (Cutoff kl. $cutoff)" 
                    : "🔴 Cutoff passeret. Nye ordrer skubbes til næste dags afhentning."
            ];
        }

        return [
            'cutoff_time' => '13:30',
            'is_before_cutoff' => (date('H:i') <= '13:30'),
            'status_message' => 'Standard cutoff 13:30 gælder.'
        ];
    }
}
