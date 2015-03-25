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
use Toknot\Boot\ArrayObject;
use Toknot\Exception\BaseException;

class DbTableColumn extends ArrayObject {

    private $columnName = null;
    private $tableObject = null;
    protected $alias = null;
    protected $type = null;
    protected $length = 0;
    protected $isPK = false;
    protected $autoIncrement = false;
    protected $value = '';

    protected function __init($columnInfo, DbTableObject $table) {
        $this->tableObject = $table;
        parent::__construct($columnInfo);
    }

    public function getPropertie($name) {
        if (isset($this->$name)) {
            return $this->$name;
        }
        throw new BaseException("bad call property of {$this->columnName}::{$name}");
    }

    public function value($value = null) {
        if (empty($value)) {
            return $this->value;
        }
        $this->value = $value;
    }

    public function __toString() {
        $this->value = (string) $this->value;
        return $this->value;
    }

}
