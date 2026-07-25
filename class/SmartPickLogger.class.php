<?php
class SmartPickLogger {
    private $db;
    public function __construct($db) { $this->db = $db; }
    public function log($category, $event, $message, $user_id = 0, $details = []) {
        $sql = "INSERT INTO " . MAIN_DB_PREFIX . "smartpick_user_logs (fk_user, fk_product, qty_picked, duration_sec, date_creation) VALUES (";
        $sql .= intval($user_id) . ", 0, 0, 0, '" . $this->db->idate(time()) . "')";
        $this->db->query($sql);
    }
}
