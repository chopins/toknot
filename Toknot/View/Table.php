<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\View;

use Toknot\Control\FMAI;

class Table {

    private $defaultTpl;
    private $nav;
    private $dataList;

    public function __construct($defaultTpl = true) {
        $this->defaultTpl = $defaultTpl;
        $this->nav = new ViewData;
        $this->dataList = new ViewData;
    }

    public function setNav($nav) {
        foreach ($nav as $item) {
            if (empty($item->type)) {
                $item->type = 'string';
            }
            if (empty($item->name)) {
                $item->setPropertie('name', '');
            }
            $this->nav->importPropertie($nav);
        }
        $this->nav = $nav;
    }

    public function setListData($dataList) {
        foreach ($dataList as $item) {
            foreach ($item as $it) {
                if (empty($it->type)) {
                    $it->setPropertie('type', 'string');
                }
                if (empty($it->value)) {
                    $it->setPropertie('value', '');
                }
                if(empty($it->selected)) {
                    $it->setPropertie('selected', '');
                }
            }
        }
        $this->dataList = $dataList;
    }

    public function renderer() {
        $FMAI = FMAI::getInstance();
        if ($this->defaultTpl) {
            $storeAppScanPath = Renderer::$scanPath;
            Renderer::$scanPath = __DIR__;
        }
        $FMAI->D->_TK_ST_tableNav = $this->nav;
        $FMAI->D->_TK_ST_tableDataList = $this->dataList;
        $FMAI->display('Widget/table');
        if ($this->defaultTpl) {
            Renderer::$scanPath = $storeAppScanPath;
        }
    }

}
