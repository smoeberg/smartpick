<?php
/**
 * SmartPickCycleCount - Løbende lagertælling på pluklageret
 * Beder plukkeren om at tælle/bekræfte beholdning ved tilfældige stikprøver
 */
class SmartPickCycleCount
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Tjek om der skal udløses en stikprøvekontrol for en given location/vare
     * F.eks. 1 ud af 10 pluk udløser en hurtig bekræftelse af hyldebeholdningen
     */
    public function shouldTriggerCycleCount($fk_product, $id_warehouse)
    {
        // 10% chance for stikprøvekontrol under pluk
        return (rand(1, 10) === 1);
    }

    /**
     * Opret automatisk Dolibarr lagerjustering hvis medarbejderen korrigerer beholdningen
     */
    public function adjustStockFromCycleCount($user, $fk_product, $id_warehouse, $actual_counted_qty, $expected_qty)
    {
        require_once DOL_DOCUMENT_ROOT . '/product/stock/class/mouvementstock.class.php';

        $diff = floatval($actual_counted_qty) - floatval($expected_qty);
        if ($diff == 0) return true; // Ingen afvigelse

        $move = new MouvementStock($this->db);
        $res = $move->_create(
            $user,
            $fk_product,
            $id_warehouse,
            $diff,
            0,
            'SmartPick Løbende Lagertælling (Cycle Count Korrektion)'
        );

        return ($res > 0);
    }
}
