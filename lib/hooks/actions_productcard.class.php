<?php
// File: custom/smartpick/lib/hooks/actions_productcard.class.php

class ActionsSmartPick
{
    /**
     * Vis ekstra felt på produktkortet (vises under "Komm.")
     */
    public function formObjectOptions($parameters, &$object, &$action, $hookmanager)
    {
        global $langs;

        if (empty($object->element) || $object->element !== 'product') {
            return 0;
        }

        $langs->load('smartpick@smartpick');

        $checked = !empty($object->array_options['options_dropshipping_enabled']) ? 'checked' : '';
        $label = $langs->trans("EnableDropshipping");

        print '<tr class="tagtr">';
        print '<td class="fieldrequired">' . $label . '</td>';
        print '<td>';
        print '<input type="checkbox" name="options_dropshipping_enabled" value="1" ' . $checked . '>';
        print '</td>';
        print '</tr>';

        return 1;
    }

    /**
     * Gem feltet når produkt opdateres
     */
    public function doActions($parameters, &$object, &$action, $hookmanager)
    {
        if ($object->element !== 'product') {
            return 0;
        }

        if ($action === 'update') {
            $object->array_options['options_dropshipping_enabled'] = GETPOST('options_dropshipping_enabled', 'int') ? 1 : 0;
        }

        return 1;
    }
}
