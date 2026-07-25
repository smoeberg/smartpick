<?php
/**
 * SmartPickQueue - Styring af plukkø og ruteoptimering i Dolibarr WMS
 */
class SmartPickQueue
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Tilføj en ordre og dens ordrelinjer til plukkøen med placeringer
     *
     * @param int $fk_commande Ordre ID i Dolibarr
     * @param string $batch_id Valgfrit batch ID
     * @return int Antal oprettede linjer
     */
    public function addOrderToQueue($fk_commande, $batch_id = null)
    {
        if (empty($fk_commande) || $fk_commande <= 0) return 0;

        require_once DOL_DOCUMENT_ROOT . '/commande/class/commande.class.php';
        $order = new Commande($this->db);
        if ($order->fetch($fk_commande) <= 0) return 0;

        if (empty($batch_id)) {
            $batch_id = 'BATCH-' . date('Ymd-His') . '-' . rand(100, 999);
        }

        $count = 0;
        foreach ($order->lines as $line) {
            // Spring tekstlinjer og ikke-produktlinjer over
            if ($line->fk_product <= 0) continue;

            // Hent produktdata og lagerplacering (rack / bin / barcode)
            $sql = "SELECT ref, barcode, label FROM " . MAIN_DB_PREFIX . "product WHERE rowid = " . intval($line->fk_product);
            $resql = $this->db->query($sql);

            $ref = $line->ref_product ? $line->ref_product : 'PROD-' . $line->fk_product;
            $barcode = '';
            $label = $line->libelle ? $line->libelle : $line->desc;

            if ($resql && $obj = $this->db->fetch_object($resql)) {
                $ref = $obj->ref;
                $barcode = $obj->barcode;
                if (!empty($obj->label)) $label = $obj->label;
            }

            // Hent lagerplacering hvis muligt fra product_warehouse / stock
            $loc_rack = '';
            $loc_bin = '';
            $sql_loc = "SELECT p.rack, p.bin FROM " . MAIN_DB_PREFIX . "product_stock p WHERE p.fk_product = " . intval($line->fk_product) . " LIMIT 1";
            $res_loc = $this->db->query($sql_loc);
            if ($res_loc && $loc_obj = $this->db->fetch_object($res_loc)) {
                $loc_rack = $loc_obj->rack ? $loc_obj->rack : '';
                $loc_bin = $loc_obj->bin ? $loc_obj->bin : '';
            }

            // Tjek om linjen allerede eksisterer i plukkøen
            $check_sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "smartpick_queue WHERE fk_commande = " . intval($fk_commande) . " AND fk_commandedet = " . intval($line->id);
            $check_res = $this->db->query($check_sql);
            if ($check_res && $this->db->num_rows($check_res) > 0) {
                continue; // Allerede tilføjet
            }

            $insert_sql = "INSERT INTO " . MAIN_DB_PREFIX . "smartpick_queue ";
            $insert_sql .= "(fk_commande, fk_commandedet, fk_product, product_ref, barcode, label, qty_to_pick, qty_picked, fk_warehouse, loc_rack, loc_bin, status, batch_id, date_creation) ";
            $insert_sql .= "VALUES (";
            $insert_sql .= intval($fk_commande) . ", ";
            $insert_sql .= intval($line->id) . ", ";
            $insert_sql .= intval($line->fk_product) . ", ";
            $insert_sql .= "'" . $this->db->escape($ref) . "', ";
            $insert_sql .= "'" . $this->db->escape($barcode) . "', ";
            $insert_sql .= "'" . $this->db->escape($label) . "', ";
            $insert_sql .= floatval($line->qty) . ", ";
            $insert_sql .= "0.0, ";
            $insert_sql .= intval($line->fk_warehouse ? $line->fk_warehouse : 0) . ", ";
            $insert_sql .= "'" . $this->db->escape($loc_rack) . "', ";
            $insert_sql .= "'" . $this->db->escape($loc_bin) . "', ";
            $insert_sql .= "'pending', ";
            $insert_sql .= "'" . $this->db->escape($batch_id) . "', ";
            $insert_sql .= "'" . $this->db->idate(time()) . "'";
            $insert_sql .= ")";

            if ($this->db->query($insert_sql)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Hent optimeret plukrute sorteret efter hylde/bin/placering for at minimere gangtid
     */
    public function getOptimizedRoute($batch_id = null, $status = 'pending')
    {
        $sql = "SELECT q.*, c.ref as order_ref ";
        $sql .= "FROM " . MAIN_DB_PREFIX . "smartpick_queue q ";
        $sql .= "LEFT JOIN " . MAIN_DB_PREFIX . "commande c ON c.rowid = q.fk_commande ";
        $sql .= "WHERE 1=1 ";

        if (!empty($status)) {
            $sql .= "AND q.status = '" . $this->db->escape($status) . "' ";
        }

        if (!empty($batch_id)) {
            $sql .= "AND q.batch_id = '" . $this->db->escape($batch_id) . "' ";
        }

        // Optimeret sortering: Først efter Hylde/Rack, derefter Bin/Placering, derefter Varenummer
        $sql .= "ORDER BY q.loc_rack ASC, q.loc_bin ASC, q.product_ref ASC";

        $resql = $this->db->query($sql);
        $route = [];
        if ($resql) {
            while ($obj = $this->db->fetch_object($resql)) {
                $route[] = $obj;
            }
        }
        return $route;
    }

    /**
     * Registrer scannet stregkode og opdater plukket antal
     */
    public function recordScan($queue_id, $scanned_code, $qty = 1.0)
    {
        $sql = "SELECT * FROM " . MAIN_DB_PREFIX . "smartpick_queue WHERE rowid = " . intval($queue_id);
        $res = $this->db->query($sql);

        if (!$res || !($line = $this->db->fetch_object($res))) {
            return ['success' => false, 'message' => 'Pluklinje ikke fundet'];
        }

        // Valider stregkode eller produkt-ref
        $code = trim($scanned_code);
        if ($code !== $line->barcode && $code !== $line->product_ref) {
            return ['success' => false, 'message' => 'Ukorrekt stregkode/produkt scanned'];
        }

        $new_picked = $line->qty_picked + floatval($qty);
        $new_status = 'picking';

        if ($new_picked >= $line->qty_to_pick) {
            $new_picked = $line->qty_to_pick;
            $new_status = 'picked';
        }

        $update_sql = "UPDATE " . MAIN_DB_PREFIX . "smartpick_queue SET ";
        $update_sql .= "qty_picked = " . floatval($new_picked) . ", ";
        $update_sql .= "status = '" . $this->db->escape($new_status) . "' ";
        $update_sql .= "WHERE rowid = " . intval($queue_id);

        if ($this->db->query($update_sql)) {
            return [
                'success' => true,
                'queue_id' => $queue_id,
                'qty_picked' => $new_picked,
                'qty_to_pick' => $line->qty_to_pick,
                'is_completed' => ($new_status === 'picked'),
                'message' => ($new_status === 'picked') ? 'Vare færdigplukket!' : 'Antal opdateret'
            ];
        }

        return ['success' => false, 'message' => 'Database opdateringsfejl'];
    }

    /**
     * Håndter delvis pluk (partial pick / restordre)
     */
    public function setPartialPick($queue_id, $picked_qty, $note = '')
    {
        $sql = "UPDATE " . MAIN_DB_PREFIX . "smartpick_queue SET ";
        $sql .= "qty_picked = " . floatval($picked_qty) . ", ";
        $sql .= "status = 'partial' ";
        $sql .= "WHERE rowid = " . intval($queue_id);

        return $this->db->query($sql);
    }
}
