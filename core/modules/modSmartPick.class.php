<?php
// File: custom/smartpick/core/modules/modSmartPick.class.php

require_once DOL_DOCUMENT_ROOT . '/core/modules/DolibarrModules.class.php';

class modSmartPick extends DolibarrModules
{
    /**
     * Constructor
     */
    public function __construct($db)
    {
        global $langs, $conf;

        $this->db = $db;
        $this->numero = 104000; // Unique module ID
        $this->rights_class = 'smartpick';
        $this->family = 'interface';
        $this->module_position = 500;

        $this->name = preg_replace('/^mod/', '', get_class($this));
        $this->description = "SmartPick - Udvidet pluk- og logistikstyring";
        $this->editor_name = 'Signalement';
        $this->editor_url = 'https://signalement.dk';

        $this->version = '1.0.0';
        $this->const_name = 'MAIN_MODULE_SMARTPICK';
        $this->picto = 'smartpick@smartpick';

        $this->dirs = array("/smartpick/temp");

        $this->config_page_url = array("admin_shipmondo_poc.php@smartpick");

        $this->module_parts = array(
            'triggers' => 1,
            'hooks' => array('ordercard')
        );

        $this->rights = array();
        $r = 0;

        $this->rights[$r][0] = 104001;
        $this->rights[$r][1] = 'Læs adgang til SmartPick admin';
        $this->rights[$r][2] = 'r';
        $this->rights[$r][3] = 1;
        $this->rights[$r][4] = 'read';
        $r++;

        $this->rights[$r][0] = 104002;
        $this->rights[$r][1] = 'Skriv adgang til SmartPick admin';
        $this->rights[$r][2] = 'w';
        $this->rights[$r][3] = 1;
        $this->rights[$r][4] = 'write';
        $r++;

        $this->menus = array();
        $r = 0;

        $this->menus[$r] = array(
            'fk_menu' => 0,
            'type' => 'top',
            'titre' => 'SmartPick',
            'mainmenu' => 'smartpick',
            'leftmenu' => 'smartpick',
            'url' => '/smartpick/admin/admin_shipmondo_poc.php',
            'langs' => 'smartpick@smartpick',
            'position' => 100,
            'enabled' => '1',
            'perms' => '1',
            'target' => '',
            'user' => 2
        );
    }

    /**
     * Initialize module (called on activation)
     */
    public function init($options = '')
    {
        $result = parent::init($options);

        require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
        $extrafields = new ExtraFields($this->db);

        if (!isset($extrafields->attributes['product']['dropshipping_enabled'])) {
            $extrafields->addExtraField(
                'dropshipping_enabled',
                'Dropshipping aktiveret',
                'boolean',
                0,
                '',
                'product',
                0,
                '',
                1,
                '', '', '', 0 // <-- default value is 0 (unchecked)
            );
        }

        return $result;
    }

    /**
     * Custom remove override to avoid argument errors
     */
    public function remove()
    {
        return parent::_remove(array());
    }
}
