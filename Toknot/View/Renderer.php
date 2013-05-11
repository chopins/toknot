<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\View;

use Toknot\Di\Object;

class Renderer extends Object {
    private $varList = null;
    private $tplName = '';
    public function __construct() {
        ;
    }
    public static function singleton() {
        parent::__singleton();
    }
    public function importVars($vars) {
        $this->varList = $vars;
    }
    public function display($tplName) {
        $this->tplName = $tplName;
    }
}