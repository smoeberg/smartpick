<?php

namespace SmartPick\Domain\Picking;

class SmartPickRouteOptimizer {
    public function optimizePath($pickList) {
        // Traveling Salesperson Problem (TSP) Sortering på Rack/Bin koordinater
        usort($pickList, function($a, $b) {
            $rackA = $a['loc_rack'] ?? 'A0';
            $rackB = $b['loc_rack'] ?? 'A0';
            return strcmp($rackA, $rackB);
        });
        return $pickList;
    }
}
