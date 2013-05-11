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

class DbTableColumn extends Object {
    private $columnName = null;
    private $currentValue = null;
    private $newValue = null;
    public function __construct($columnName, $value) {
        $this->columnName = $columnName;
        $this->currentValue = $value;
    }
    public function setNew($value) {
        $this->newValue = $value;
    }
    public function getCurrent() {
        return $this->currentValue;
    }
}