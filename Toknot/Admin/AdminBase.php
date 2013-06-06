<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Admin;

use Toknot\Control\FMAI;
use Toknot\User\ClassUserControl;
use Toknot\Exception\FileIOException;

class AdminBase extends ClassUserControl {

    protected $FMAI = null;
    protected $AR = null;
    protected $view = null;
    protected $CFG = null;
    protected $dbConnect = null;

    public function __construct(FMAI &$FMAI) {
        $this->FMAI = $FMAI;
        $this->loadAdminConfig();

        $FMAI->registerAccessDeniedController('Toknot\Admin\Login');
        $FMAI->checkAccess($this);
        $this->initDatabase();
        $this->view = $FMAI->newTemplateView($this->CFG->View);
    }

    public function initDatabase() {
        $this->AR = $this->FMAI->getActiveRecord();
        $dbSectionName = $this->CFG->Admin->databaseOptionSectionName;
        if ($this->CFG->Admin->multiDatabase) {
            $i = 0;
            while (true) {
                $section = $this->CFG->Admin->databaseOptionSectionName.$i;
                if(!isset($this->CFG->$section)) {
                    break;
                }
                $this->AR->config($this->CFG->$section);
                $this->dbConnect[$i] = $this->AR->connect();
                $i++;
            }
        } else {
            $this->AR->config($this->CFG->$dbSectionName);
            $this->dbConnect = $this->AR->connect();
        }
    }

    public function loadAdminConfig() {
        if (!file_exists($FMAI->appRoot . '/Config/config.ini')) {
            throw new FileIOException('must create ' . $FMAI->appRoot . '/Config/config.ini');
        }
        $this->CFG = $this->FMAI->loadConfigure($FMAI->appRoot . '/Config/config.ini');
    }

}

?>
