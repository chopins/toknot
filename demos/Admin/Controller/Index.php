<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @link       https://github.com/chopins/toknot
 */

use Toknot\Admin\AdminBase;
class Index extends AdminBase {
    public function __construct($FMAI) {
        $this->FMAI = $FMAI;
        $this->CFG = $this->FMAI->loadConfigure($FMAI->appRoot . '/Config/config.ini');
        $this->adminConfig = $this->CFG->Admin;
        $this->AR = $this->FMAI->getActiveRecord();
        $this->AR->config($this->CFG->Database);
        
        $this->FMAI->enableHTMLCache();
        $this->view = $this->FMAI->newTemplateView($this->CFG->View);

        $FMAI->checkAccess($this);
        parent::__construct($FMAI);
    }
    public function GET() {
        
    }
    public function POST() {
        
    }
}

?>
