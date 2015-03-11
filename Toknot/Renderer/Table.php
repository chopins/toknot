<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2015 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Renderer;

use Toknot\Boot\FMAI;

class Table {

    private $defaultTpl;
    private $nav;
    private $dataList;

    /**
     * 
     * @param boolean $defaultTpl whether use Toknot given table template
     */
    public function __construct($defaultTpl = true) {
        $this->defaultTpl = $defaultTpl;
        $this->nav = new ViewData;
        $this->dataList = new ViewData;
    }

    /**
     * set Table title list
     * data like:
     * <code>
     * array(
     *      array('type'=>'checkbox','name'=>''), // one item set
     *      array('type=>'string','name'=>'test')
     *      //....
     * )
     * </code>
     * @access public
     * @param ArrayObject $nav
     */
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

    /**
     * set table data
     * data like:
     * <code>
     * array(
     *      array( //one table line
     *          array('type'=>'checkbox', 'selected'=>1), //one item
     *          array('value'=>'test1')
     *      ),
     *      array(
     *          array('type'=>'checkbox'),
     *          array('value'=>'test2')
     *      )
     *      
     *  )
     * </code>
     * @param ArrayObject $dataList
     */
    public function setListData($dataList) {
        foreach ($dataList as $item) {
            foreach ($item as $it) {
                if (empty($it->type)) {
                    $it->setPropertie('type', 'string');
                }
                if (empty($it->value)) {
                    $it->setPropertie('value', '');
                }
                if (empty($it->selected)) {
                    $it->setPropertie('selected', '');
                }
            }
        }
        $this->dataList = $dataList;
    }

    /**
     * render the table and print it
     * 
     * @access public
     */
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
