<?php
/**
 * SmartPickFactorEngine - Dynamisk Faktor-Motor med Vækstfaktor (Shops/YoY) og År-til-År Højtidsforskydning
 */
class SmartPickFactorEngine
{
    private $db;
    private $country_code;

    public function __construct($db, $country_code = 'DK')
    {
        $this->db = $db;
        $this->country_code = !empty($country_code) ? strtoupper($country_code) : 'DK';
    }

    /**
     * Beregn virksomhedens År-til-År (YoY) vækstfaktor
     */
    public function calculateYoYGrowthFactor()
    {
        // 1. Ordreantal seneste 30 dage
        $sql_recent = "SELECT COUNT(rowid) as recent_cnt FROM " . MAIN_DB_PREFIX . "commande WHERE date_creation >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        $res_recent = $this->db->query($sql_recent);
        $recent_cnt = 0;
        if ($res_recent && $obj = $this->db->fetch_object($res_recent)) {
            $recent_cnt = intval($obj->recent_cnt);
        }

        // 2. Ordreantal samme 30 dages periode sidste år
        $sql_last_year = "SELECT COUNT(rowid) as ly_cnt FROM " . MAIN_DB_PREFIX . "commande WHERE date_creation BETWEEN DATE_SUB(NOW(), INTERVAL 395 DAY) AND DATE_SUB(NOW(), INTERVAL 365 DAY)";
        $res_ly = $this->db->query($sql_last_year);
        $ly_cnt = 0;
        if ($res_ly && $obj = $this->db->fetch_object($res_ly)) {
            $ly_cnt = intval($obj->ly_cnt);
        }

        // Beregn automatisk vækstfaktor hvis historisk data findes, ellers standard 1.0
        $yoy_growth_rate = 1.0;
        if ($ly_cnt > 10 && $recent_cnt > 0) {
            $yoy_growth_rate = round($recent_cnt / $ly_cnt, 2);
        }

        // Hent eventuel manuel shop-ekspansionsfaktor fra Dolibarr indstillinger (f.eks. fra 2 shops til 8 shops)
        global $conf;
        $configured_shop_growth = !empty($conf->global->SMARTPICK_GROWTH_FACTOR) ? floatval($conf->global->SMARTPICK_GROWTH_FACTOR) : 1.0;

        $final_growth_factor = max($yoy_growth_rate, $configured_shop_growth);

        return [
            'recent_30d_orders' => $recent_cnt,
            'last_year_30d_orders' => $ly_cnt,
            'calculated_yoy_growth_multiplier' => $yoy_growth_rate,
            'configured_growth_multiplier' => $configured_shop_growth,
            'final_applied_growth_factor' => $final_growth_factor
        ];
    }

    /**
     * Analyse af højtidsforskydning år-til-år (f.eks. Jul på en Søndag vs. Jul på en Onsdag)
     */
    public function getHolidayCalendarShiftDynamics($target_date)
    {
        $year = date('Y', strtotime($target_date));
        $month_day = date('m-d', strtotime($target_date));
        $target_day_of_week = date('l', strtotime($target_date));

        // Tjek historiske udtræk for samme dato over de seneste 3 år i Dolibarr
        $sql = "SELECT YEAR(date_creation) as yr, DAYNAME(date_creation) as day_name, COUNT(rowid) as order_count ";
        $sql .= "FROM " . MAIN_DB_PREFIX . "commande ";
        $sql .= "WHERE DATE_FORMAT(date_creation, '%m-%d') = '" . $this->db->escape($month_day) . "' ";
        $sql .= "GROUP BY yr, day_name ORDER BY yr DESC";

        $res = $this->db->query($sql);
        $history = [];
        if ($res) {
            while ($obj = $this->db->fetch_object($res)) {
                $history[] = [
                    'year' => $obj->yr,
                    'day_of_week' => $obj->day_name,
                    'order_count' => intval($obj->order_count)
                ];
            }
        }

        return [
            'target_year' => $year,
            'target_day_of_week' => $target_day_of_week,
            'historical_same_date_records' => $history
        ];
    }

    /**
     * Udtræk alle kontekstuelle faktorer for en måldato 4 dage ude (Inkl. Vækst & Højtidsforskydning)
     */
    public function extractAllFactorsForDate($target_date)
    {
        $day_of_week = date('l', strtotime($target_date));
        $day_of_month = date('j', strtotime($target_date));

        $growth_data = $this->calculateYoYGrowthFactor();
        $holiday_shift = $this->getHolidayCalendarShiftDynamics($target_date);

        return [
            'target_date' => $target_date,
            'country_code' => $this->country_code,
            'day_of_week' => $day_of_week,
            'day_of_month' => $day_of_month,
            'is_payday_period' => ($day_of_month >= 1 && $day_of_month <= 5),
            'is_end_of_month' => ($day_of_month >= 25),
            'business_growth_engine' => $growth_data,
            'holiday_calendar_shift_dynamics' => $holiday_shift
        ];
    }
}
