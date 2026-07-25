<?php
class InterfaceSmartPickTrigger
{
    public function runTrigger($action, $object, $user, $langs, $conf)
    {
        global $db;

        if ($action == 'ORDER_CREATE' && !empty($object->lines)) {
            foreach ($object->lines as $line) {
                // Hent produktdetaljer
                if ($line->fk_product > 0) {
                    $sql = "SELECT ref, volume, rowid FROM ".MAIN_DB_PREFIX."product
                            WHERE rowid = ".(int) $line->fk_product;
                    $resql = $db->query($sql);
                    if ($resql && $db->num_rows($resql)) {
                        $obj = $db->fetch_object($resql);

                        // Indsæt i plukkø
                        $insert = "INSERT INTO ".MAIN_DB_PREFIX."smartpick_queue 
                            (fk_commande, fk_product, product_ref, qty, status, fk_warehouse)
                            VALUES (
                                ".(int) $object->id.",
                                ".(int) $line->fk_product.",
                                '".$db->escape($obj->ref)."',
                                ".(float) $line->qty.",
                                'pending',
                                ".((int) $object->fk_warehouse ?: 0)."
                            )";
                        $db->query($insert);
                    }
                }
            }
        }

        return 0;
    }
}
