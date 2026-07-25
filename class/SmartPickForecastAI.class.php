<?php
/**
 * SmartPickForecastAI - Automatisk AI-prognose & automatisk vagtoprettelse 4 dage forud
 * Baseret på emperi (ugedag, lønningsdag/start-af-måned effekt, månedssæson) via Mistral AI
 */
require_once DOL_DOCUMENT_ROOT . '/custom/smartpick/class/SmartPickMistralAI.class.php';

class SmartPickForecastAI
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Hent empiriske ordremønstre fra Dolibarr til analyse
     * Opdelt på ugedag, dag i måneden (lønningsdag/start af måned) og måned
     */
    public function getEmpiricalOrderData()
    {
        // 1. Ordremønstre opdelt på ugedage (1-7)
        $sql_weekday = "SELECT DAYNAME(c.date_creation) as day_name, DAYOFWEEK(c.date_creation) as day_num, COUNT(c.rowid) as order_count ";
        $sql_weekday .= "FROM " . MAIN_DB_PREFIX . "commande c ";
        $sql_weekday .= "WHERE c.date_creation >= DATE_SUB(NOW(), INTERVAL 180 DAY) ";
        $sql_weekday .= "GROUP BY day_name, day_num ORDER BY day_num ASC";

        $res_weekday = $this->db->query($sql_weekday);
        $weekdays = [];
        if ($res_weekday) {
            while ($obj = $this->db->fetch_object($res_weekday)) {
                $weekdays[$obj->day_name] = round(intval($obj->order_count) / 25, 0); // Snit pr. ugedag over 25 uger
            }
        }

        // 2. Månedsstart vs. Månedsslut effekt (Lønningsdagseffekt: Dag 1-5 vs Dag 25-31)
        $sql_month_period = "SELECT ";
        $sql_month_period .= "AVG(CASE WHEN DAY(date_creation) BETWEEN 1 AND 5 THEN 1 ELSE 0 END) as start_surge, ";
        $sql_month_period .= "AVG(CASE WHEN DAY(date_creation) BETWEEN 25 AND 31 THEN 1 ELSE 0 END) as end_drop ";
        $sql_month_period .= "FROM " . MAIN_DB_PREFIX . "commande WHERE date_creation >= DATE_SUB(NOW(), INTERVAL 180 DAY)";

        $res_month = $this->db->query($sql_month_period);
        $start_factor = 1.35; // Standard 35% stigning ved månedsstart/lønningsdag
        $end_factor = 0.80;   // Standard 20% fald ved månedsslut

        if ($res_month && $obj = $this->db->fetch_object($res_month)) {
            // Empirisk beregnede faktorer
            if ($obj->start_surge > 0) $start_factor = 1.0 + floatval($obj->start_surge);
        }

        return [
            'weekday_averages' => $weekdays,
            'payday_start_of_month_factor' => $start_factor,
            'end_of_month_factor' => $end_factor
        ];
    }

    /**
     * Kør AI-forudsigelse for en specifik dato 4 dage ude i fremtiden og generer automatisk vagtbehov
     *
     * @param string $apiKey Mistral AI API Key
     * @param string $target_date Datoen 4 dage forud (YYYY-MM-DD)
     * @param int $avg_picks_per_picker_per_day Hvor mange ordrer én plukker i snit kan plukke på en 8-timers vagt (f.eks. 100 ordrer)
     */
    public function generateAutoShiftsForTargetDate($apiKey, $target_date, $avg_picks_per_picker_per_day = 100)
    {
        $empirical = $this->getEmpiricalOrderData();
        
        $day_of_week = date('l', strtotime($target_date));
        $day_of_month = date('j', strtotime($target_date));

        // Empirisk grundprædiktion
        $base_avg = isset($empirical['weekday_averages'][$day_of_week]) ? $empirical['weekday_averages'][$day_of_week] : 150;
        
        // Juster ud fra om det er start af måneden (lønningsdag 1.-5.) eller slutningen af måneden (25.-31.)
        $multiplier = 1.0;
        if ($day_of_month >= 1 && $day_of_month <= 5) {
            $multiplier = $empirical['payday_start_of_month_factor']; // Lønningsdagseffekt
        } elseif ($day_of_month >= 25) {
            $multiplier = $empirical['end_of_month_factor']; // Slut-af-måned fald
        }

        $predicted_orders = round($base_avg * $multiplier);
        $required_pickers = max(1, ceil($predicted_orders / $avg_picks_per_picker_per_day));

        $prompt_data = [
            'target_date' => $target_date,
            'day_of_week' => $day_of_week,
            'day_of_month' => $day_of_month,
            'is_payday_start_period' => ($day_of_month >= 1 && $day_of_month <= 5),
            'is_end_of_month_period' => ($day_of_month >= 25),
            'empirical_base_weekday_avg' => $base_avg,
            'payday_multiplier' => $multiplier,
            'calculated_predicted_orders' => $predicted_orders,
            'picker_daily_capacity' => $avg_picks_per_picker_per_day,
            'recommended_required_pickers' => $required_pickers
        ];

        $system = "Du er en AI WMS planlægningsmotor. Returner et JSON svar med 'predicted_orders', 'required_pickers', 'shift_start', 'shift_end', 'cutoff_time' og 'explanation' baseret på den empiriske analyse 4 dage forud.";

        $prompt = "Analyser denne 4-dages fremtidsprognose og bekræft vagtkonfigurationen i JSON:
" . json_encode($prompt_data, JSON_PRETTY_PRINT);

        $mistral = new SmartPickMistralAI($apiKey);
        $ai_response = $mistral->queryMistral($prompt, $system);

        return [
            'target_date' => $target_date,
            'day_of_week' => $day_of_week,
            'day_of_month' => $day_of_month,
            'predicted_orders' => $predicted_orders,
            'required_pickers' => $required_pickers,
            'ai_analysis' => $ai_response
        ];
    }
}
