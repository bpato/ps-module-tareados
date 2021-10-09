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

require_once( __DIR__  . '/AdminImportCsvController.php');

class AdminTareadosController extends \ModuleAdminController
{
    /** @var Tareados $module */
    public $module;

    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'configuration';
        $this->className = 'Configuration';

        parent::__construct();

        $this->fields_options = [];
        $this->fields_options[0] = [
            'title' => $this->trans('Modulo Tarea dos', [], 'Modules.Tareados.Admin'),
            'icon' => 'icon-cogs',
            'fields' => [
                [
                    'title' => $this->trans('Csv Productos', [], 'Modules.Tareados.Admin'),
                    'type' => 'file',
                    'name' => 'importcsv',
                    'multiple' => false,
                ]
            ],
            'submit' => [
                'title' => $this->trans('Import', array(), 'Admin.Actions'),
                'name'  => 'submitImportFile'
            ]
        ];
    }

    public function initProcess()
    {
        parent::initProcess();
        if (\Tools::isSubmit('submitImportFile')) {
            $this->action = 'submit_import_file';
        }
    }

    public function processSubmitImportFile()
    {
        $importcsv = $_FILES['importcsv'];
        $uploader = new \Uploader(\Tools::getValue('importcsv'));
        $uploader->setSavePath(_PS_MODULE_DIR_ . $this->module->name . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR);
        $uploader->upload($importcsv);
        $importer = new AdminImportCsvController();
        if (!$imported = $importer->import($uploader->getFilePath($importcsv['name']))) {
            $this->errors[] = $this->trans('Error al importar', [], 'Modules.Tareados.Admin');
        } else {
            $this->displayInformation($this->trans('Importados %s producto/s', [$imported], 'Modules.Tareados.Admin'));
        }
    }
}