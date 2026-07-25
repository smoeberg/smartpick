<?php
/**
 * SmartPickForecastAI - AI-baseret forudsigelse af ordremængder & plukkerkapacitet via Mistral AI
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
     * Kør AI-forudsigelse af ordremængde og påkrævede plukkere for de næste 7 dage
     *
     * @param string $apiKey Mistral AI API nøgle
     * @param int $fixed_pickers Antal faste plukkere i virksomheden
     * @param int $avg_picks_per_picker_per_day Gennemsnitligt antal ordrer én plukker kan klare om dagen (f.eks. 100 ordrer)
     */
    public function predictRequiredPickersForWeek($apiKey, $fixed_pickers = 3, $avg_picks_per_picker_per_day = 100)
    {
        // 1. Hent historisk ordredata for de seneste 8 uger grupperet pr. ugedag fra Dolibarr
        $sql = "SELECT DAYNAME(c.date_creation) as day_name, DAYOFWEEK(c.date_creation) as day_num, COUNT(c.rowid) as order_count ";
        $sql .= "FROM " . MAIN_DB_PREFIX . "commande c ";
        $sql .= "WHERE c.date_creation >= DATE_SUB(NOW(), INTERVAL 60 DAY) ";
        $sql .= "GROUP BY day_name, day_num ";
        $sql .= "ORDER BY day_num ASC";

        $resql = $this->db->query($sql);
        $historical = [];
        if ($resql) {
            while ($obj = $this->db->fetch_object($resql)) {
                $historical[] = [
                    'day' => $obj->day_name,
                    'avg_order_volume' => round(intval($obj->order_count) / 8, 0)
                ];
            }
        }

        $total_capacity = $fixed_pickers * $avg_picks_per_picker_per_day;

        $prompt_data = [
            'fixed_pickers' => $fixed_pickers,
            'capacity_per_picker_per_day' => $avg_picks_per_picker_per_day,
            'total_fixed_daily_capacity' => $total_capacity,
            'historical_avg_orders_per_day' => $historical
        ];

        $system = "Du er en AI-specialist i lagerkapacitet og WMS-planlægning. Din opgave er at analysere den faste plukkerkapacitet og historiske ordredata, forudsige spidsbelastninger og udpege præcis hvilke dage der vil overskride kapaciteten og kræve ekstra plukkevagter/vikarer.";

        $prompt = "Analyser følgende kapacitetsdata for lageret:
";
        $prompt .= json_encode($prompt_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $prompt .= "

Giv en overskuelig analyse for hver ugedag. Angiv forventet ordreantal, om den faste stab på $fixed_pickers plukkere rækker, og præcis hvor mange ekstra plukkere der skal kaldes ind på flaskehalsdage.";

        $mistral = new SmartPickMistralAI($apiKey);
        return $mistral->queryMistral($prompt, $system);
    }
}
