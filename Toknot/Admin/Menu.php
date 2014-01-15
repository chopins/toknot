<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Admin;

use Toknot\Admin\AdminBase;
use Toknot\Config\ConfigLoader;
use Toknot\Di\FileObject;
use Toknot\Control\FMAI;

class Menu extends AdminBase {

    public $control = null;
    public $subNav = array();
    public static $FMAI;
    public function __construct(FMAI $FMAI) {
        self::$FMAI = $FMAI;
        $this->loadAdminConfig();
        $this->initDatabase();
    }

    public function getAllMenu() {
        $adminConfig = self::$CFG->Admin;
        if($adminConfig->adminUseIniNavigationConfig == false && 
                !empty($adminConfig->adminNavigationListTable)) {
            $table = $this->dbConnect->adminNavigationListTable;
            $allList = $table->readAll();
            $manageList = array();
            foreach ($allList as $manage) {
                $manage['sub'] = unserialize($manage['sub']);
                $manageList[$manage['key']] = $manage;
            }
        } else {
             $file = FileObject::getRealPath(self::$FMAI->appRoot,"Config/{$adminConfig->adminNavigationListIniFile}");
            $manageList = ConfigLoader::loadCfg($file);
        }
        foreach($manageList as &$manage) {
            if($manage['hassub'] && !empty($manage['sub'])) {
                foreach($manage['sub'] as $key=>$sub) {
                    $manage['sub'][$key] = $manageList[$sub];
                    unset($manageList[$sub]);
                }
            }
        }
        return $manageList;
    }
  

}

