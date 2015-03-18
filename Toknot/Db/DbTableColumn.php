<?php

/**
* Toknot (http://toknot.com)
*
* @copyright Copyright (c) 2011 - 2015 Toknot.com
* @license http://toknot.com/LICENSE.txt New BSD License
* @link https://github.com/chopins/toknot
*/

namespace Toknot\Db;

use Toknot\Db\DbTableObject;
use Toknot\Boot\StringObject;
use Toknot\Exception\BaseException;

class DbTableColumn extends StringObject{
    private $columnName = null;
    private $tableObject = null;
    public $alias = null;
    public $type = null;
    public $length = 0;
    public $isPK = false;
    public $autoIncrement = false;
    public $value = '';
    protected function __init($columnName) {
        $this->columnName = $columnName;
        parent::__construct($columnName);
    }
    public function getPropertie($name) {
        if(isset($this->$name)) {
            return $this->$name;
        }
        throw new BaseException("bad call property of {$this->columnName}::{$name}");
    }

    public function __toString() {
        $this->value = (string) $this->value;
        return $this->value;
    }
}