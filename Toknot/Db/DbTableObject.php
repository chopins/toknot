<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @link       https://github.com/chopins/toknot
 */
namespace Toknot\Di;

use Toknot\Di\Object;

class DbTableObject extends Object {
    public $tableName;
    public $primary = null;
    public function __construct($tableName, $value) {
        $this->tableName = $tableName;
        $this->primary = $value;
    }
    public function setPropertie($propertie, $value) {
        $this->$propertie = new DbTableColumn($propertie);
    }
}