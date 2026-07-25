<?php
/**
 * SmartPickFactorEngine - Dynamisk Faktor-Motor med Dolibarr Helligdags- og Eventkalender Integration
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
     * Tjek om en dato er en national helligdag i Dolibarrs helligdagstabel (llx_c_holiday)
     */
    public function isPublicHoliday($date)
    {
        $month = date('m', strtotime($date));
        $day = date('d', strtotime($date));

        // Slå op i Dolibarrs standard c_holiday / holiday tabeller for det specifikke land
        $sql = "SELECT rowid, label FROM " . MAIN_DB_PREFIX . "c_holiday ";
        $sql .= "WHERE code_country = '" . $this->db->escape($this->country_code) . "' ";
        $sql .= "AND month = " . intval($month) . " AND day = " . intval($day) . " ";
        $sql .= "AND active = 1";

        $resql = $this->db->query($sql);
        if ($resql && $obj = $this->db->fetch_object($resql)) {
            return [
                'is_holiday' => true,
                'holiday_name' => $obj->label,
                'country' => $this->country_code
            ];
        }

        // Fastkoded fallback for faste helligdage hvis tabellen ikke er udfyldt
        $fixed_holidays = [
            '01-01' => 'Nytårsdag',
            '12-25' => 'Juledag',
            '12-26' => 'Anden Juledag'
        ];
        $key = date('m-d', strtotime($date));
        if (isset($fixed_holidays[$key])) {
            return [
                'is_holiday' => true,
                'holiday_name' => $fixed_holidays[$key],
                'country' => $this->country_code
            ];
        }

        return ['is_holiday' => false, 'holiday_name' => null, 'country' => $this->country_code];
    }

    /**
     * Hent planlagte kampagner eller begivenheder fra Dolibarrs Kalender (llx_actioncomm)
     */
    public function getDolibarrCalendarEvents($date)
    {
        $sql = "SELECT a.id, a.label, a.note ";
        $sql .= "FROM " . MAIN_DB_PREFIX . "actioncomm a ";
        $sql .= "WHERE DATE(a.datep) = '" . $this->db->escape($date) . "'";

        $resql = $this->db->query($sql);
        $events = [];
        if ($resql) {
            while ($obj = $this->db->fetch_object($resql)) {
                $events[] = [
                    'label' => $obj->label,
                    'note' => $obj->note
                ];
            }
        }
        return $events;
    }

    /**
     * Udtræk alle kontekstuelle faktorer for en måldato 4 dage ude
     */
    public function extractAllFactorsForDate($target_date)
    {
        $day_of_week = date('l', strtotime($target_date));
        $day_of_month = date('j', strtotime($target_date));
        $month = date('n', strtotime($target_date));

        // 1. Tjek helligdag på selve dagen og dagene op til
        $holiday_info = $this->isPublicHoliday($target_date);
        
        // Tjek om dagen forinden var en helligdag (medfører ophobning af ubehandlede ordrer)
        $yesterday = date('Y-m-d', strtotime($target_date . ' -1 day'));
        $yesterday_holiday = $this->isPublicHoliday($yesterday);

        // 2. Tjek Dolibarr Kalendervent
        $calendar_events = $this->getDolibarrCalendarEvents($target_date);

        // 3. Nuværende ubehandlede ordrer i Dolibarr (Backlog)
        $backlog_sql = "SELECT COUNT(rowid) as backlog FROM " . MAIN_DB_PREFIX . "commande WHERE fk_statut = 1";
        $res_backlog = $this->db->query($backlog_sql);
        $current_backlog = 0;
        if ($res_backlog && $obj = $this->db->fetch_object($res_backlog)) {
            $current_backlog = intval($obj->backlog);
        }

        // 4. Saml alle faktorer i en struktureret profil
        return [
            'target_date' => $target_date,
            'country_code' => $this->country_code,
            'day_of_week' => $day_of_week,
            'day_of_month' => $day_of_month,
            'month' => $month,
            'is_payday_period' => ($day_of_month >= 1 && $day_of_month <= 5),
            'is_end_of_month' => ($day_of_month >= 25),
            'is_public_holiday' => $holiday_info['is_holiday'],
            'public_holiday_name' => $holiday_info['holiday_name'],
            'is_day_after_public_holiday' => $yesterday_holiday['is_holiday'],
            'yesterday_holiday_name' => $yesterday_holiday['holiday_name'],
            'dolibarr_calendar_events' => $calendar_events,
            'current_unshipped_backlog_in_dolibarr' => $current_backlog
        ];
    }
}
