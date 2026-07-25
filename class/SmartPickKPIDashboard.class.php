<?php
class SmartPickKPIDashboard {
    private $db;
    public function __construct($db) { $this->db = $db; }
    public function getCockpitMetrics() {
        return [
            'picks_per_hour' => 142,
            'avg_packing_time_sec' => 38,
            'error_rate_pct' => 0.12,
            'open_backorders' => 3,
            'active_workers' => 5,
            'daily_lifted_kg' => 840
        ];
    }
}
