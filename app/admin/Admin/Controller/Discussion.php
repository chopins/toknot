<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2017 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Admin\Controller;

use Admin\Controller\Lib\Common;

class Discussion extends Common {

    /**
     * @route
     */
    public function __construct() {
        parent::__construct();
        $this->index();
    }

    public function index() {
        $active = strtolower(get_called_class());
        $this->setTitle($active);
        $this->v('pageNav', $active);
        $this->v('leftMenuSelected', $active);
        $this->view('discussion.index');
    }

}
