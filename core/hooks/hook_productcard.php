<?php
// File: custom/smartpick/core/hooks/hook_productcard.php

class HookProductcard
{
    /**
     * Dolibarr hook til visning af dropshipping-feltet
     */
    public function formObjectOptions($parameters, &$object, &$action, $hookmanager)
    {
        global $langs, $db;

        if ($object->element != 'product') return 0;

        require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
        $extrafields = new ExtraFields($db);
        $extrafields->fetch_name_optionals_label('product');

        print '<tr class="fieldtr">';
        print '<td class="fieldname">' . $langs->trans('Dropshipping aktiv?') . '</td>';
        print '<td colspan="3">';

        $value = isset($object->array_options['options_dropshipping_enabled']) ? $object->array_options['options_dropshipping_enabled'] : 0;
        print '<input type="checkbox" name="options_dropshipping_enabled" value="1"' . ($value ? ' checked' : '') . ' />';

        print '</td>';
        print '</tr>';

        return 1;
    }
}
