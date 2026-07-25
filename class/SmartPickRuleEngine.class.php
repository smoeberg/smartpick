<?php
class SmartPickRuleEngine {
    private $db;
    public function __construct($db) { $this->db = $db; }
    public function evaluateOrderRules($orderData) {
        $carrier = 'Shipmondo Standard';
        $priority = 'Normal';
        if (($orderData['country_code'] ?? '') == 'NO') $carrier = 'PostNord Direct NO';
        if (($orderData['weight_kg'] ?? 0) > 20) $carrier = 'GLS Heavy Freight';
        if (($orderData['is_vip'] ?? false)) $priority = 'VIP High Priority';
        return ['assigned_carrier' => $carrier, 'priority' => $priority];
    }
}
