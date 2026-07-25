<?php

namespace SmartPick\Infrastructure\AI;

/**
 * SmartPickForecastAI - Mistral AI forudsigelse med dynamisk Dolibarr Faktor-Motor (Helligdage, Kalender, Land, Backlog)
 */
require_once DOL_DOCUMENT_ROOT . '/custom/smartpick/infrastructure/ai/SmartPickMistralAI.class.php';
require_once DOL_DOCUMENT_ROOT . '/custom/smartpick/domain/planning/SmartPickFactorEngine.class.php';

use SmartPick\Domain\Planning\SmartPickFactorEngine;

class SmartPickForecastAI
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Kør AI-forudsigelse for måldato 4 dage forud ved brug af den dynamiske Dolibarr faktor-motor
     *
     * @param string $apiKey Mistral AI API Nøgle
     * @param string $target_date Måldato 4 dage frem
     * @param string $country_code Landekode for helligdage (f.eks. 'DK', 'SE', 'DE', 'NO')
     * @param int $avg_picks_per_picker_per_day Kapacitet pr. plukker
     */
    public function generateAutoShiftsForTargetDate($apiKey, $target_date, $country_code = 'DK', $avg_picks_per_picker_per_day = 100)
    {
        // 1. Ekstraher alle dynamiske faktorer fra Dolibarr kalender & helligdagskatalog
        $factorEngine = new SmartPickFactorEngine($this->db, $country_code);
        $factors = $factorEngine->extractAllFactorsForDate($target_date);

        // 2. Hvis selve måldatoen er en lukket national helligdag -> Skal der ikke oprettes plukkevagt
        if ($factors['is_public_holiday']) {
            return [
                'target_date' => $target_date,
                'is_holiday' => true,
                'holiday_name' => $factors['public_holiday_name'],
                'predicted_orders' => 0,
                'required_pickers' => 0,
                'explanation' => "Ingen plukkevagt oprettet da " . $target_date . " er en national helligdag (" . $factors['public_holiday_name'] . " i " . $country_code . ")."
            ];
        }

        // 3. Opbyg prompt til Mistral AI med samtlige udtrufne Dolibarr faktorer
        $system = "Du er en avanceret AI WMS planlægningsmotor. Analyser alle de indsendte Dolibarr faktorer (helligdage, kalenderbegivenheder, ugedag, lønningsdagseffekt, landekode og ubehandlet backlog) for at forudse det præcise ordreantal og oprette den optimale plukkevagt 4 dage ude.";

        $prompt = "Analyser følgende udttrufne Dolibarr faktorprofil for måldato $target_date:
";
        $prompt .= json_encode($factors, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $prompt .= "

Hvis dagen er dagen LIGE EFTER en helligdag (is_day_after_public_holiday = true) eller mandag efter weekenden, skal du øge det forventede ordreantal markant pga. ophobet efterspørgsel.";

        $mistral = new SmartPickMistralAI($apiKey);
        $ai_response = $mistral->queryMistral($prompt, $system);

        // Beregn grundræsonnering
        $base_orders = 150;
        if ($factors['is_payday_period']) $base_orders *= 1.35;
        if ($factors['is_day_after_public_holiday']) $base_orders *= 1.60; // 60% stigning efter helligdag
        $base_orders += ($factors['current_unshipped_backlog_in_dolibarr'] * 0.5);

        $predicted_orders = round($base_orders);
        $required_pickers = max(1, ceil($predicted_orders / $avg_picks_per_picker_per_day));

        return [
            'target_date' => $target_date,
            'country_code' => $country_code,
            'is_holiday' => false,
            'factors' => $factors,
            'predicted_orders' => $predicted_orders,
            'required_pickers' => $required_pickers,
            'ai_analysis' => $ai_response
        ];
    }
}
