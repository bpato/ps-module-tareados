<?php
/**
 * Copyright (C) 2020 Brais Pato
 *
 * NOTICE OF LICENSE
 *
 * This file is part of Simplerecaptcha <https://github.com/bpato/simplerecaptcha.git>.
 * 
 * Simplerecaptcha is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Simplerecaptcha is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Foobar. If not, see <https://www.gnu.org/licenses/>.
 *
 * @author    Brais Pato <patodevelop@gmail.com>
 * @copyright 2020 Brais Pato
 * @license   https://www.gnu.org/licenses/ GNU GPLv3
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class Tareados extends Module
{
    
    /** @var string Unique name */
    public $name = 'tareados';

    /** @var string Version */
    public $version = '1.0.0';

    /** @var string author of the module */
    public $author = 'Brais Pato';

    /** @var int need_instance */
    public $need_instance = 0;

    /** @var string Admin tab corresponding to the module */
    public $tab = 'migration_tools';

    /** @var array filled with known compliant PS versions */
    public $ps_versions_compliancy = [
        'min' => '1.7.3.3',
        'max' => '1.7.9.99'
    ];

    /** @var array Hooks used */
    public $hooks = [
        'displayHome',
        'displayFooterBefore'
    ];

    /** Name of ModuleAdminController used for configuration */
    const MODULE_ADMIN_CONTROLLER = 'AdminTareados';

    /**
     * Constructor of module
     */
    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->trans('Modulo Tarea 2', [], 'Modules.Tareados.Admin');
        $this->description = $this->trans('Crear un script o módulo que leyendo el siguiente CSV, importe a Prestashop todos los productos que aparecen.', [], 'Modules.Tareados.Admin');
        $this->confirmUninstall = $this->trans('¿Estás seguro de que quieres desinstalar el módulo?', array(), 'Modules.Tareados.Admin');
    }

    /**
     * @return bool
     */
    public function install()
    {
        return parent::install() 
            && $this->registerHook($this->hooks)
            && $this->installTab();
    }

    /**
     * @return bool
     */
    public function installTab()
    {
        $tab = new Tab();
        
        $tab->class_name = static::MODULE_ADMIN_CONTROLLER;
        $tab->name = array_fill_keys(
            Language::getIDs(false),
            $this->name
        );
        $tab->active = false;
        $tab->id_parent = (int) Tab::getIdFromClassName('AdminModulesManage');
        $tab->module = $this->name;

        return $tab->add();
    }

    /**
     * @return bool
     */
    public function uninstall()
    {
        return parent::uninstall()
            && $this->uninstallTabs()
            && $this->uninstallConfiguration();
    }

    /**
     * @return bool
     */
    public function uninstallTabs()
    {
        $id_tab = (int) Tab::getIdFromClassName(static::MODULE_ADMIN_CONTROLLER);

        if ($id_tab) {
            $tab = new Tab($id_tab);

            return (bool) $tab->delete();
        }

        return true;
    }

    /**
     * @return bool
     */
    public function uninstallConfiguration()
    {
        return true;
    }

    /**
     * @return null
     */
    public function getContent()
    {
        Tools::redirectAdmin(
            Context::getContext()->link->getAdminLink(self::MODULE_ADMIN_CONTROLLER)
        );
        return null;
    }
}