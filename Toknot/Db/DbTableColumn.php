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

    /**
     * table field name
     *
     * @var string 
     */
    private $columnName = null;

    /**
     *
     * @var DbTableObject 
     */
    private $tableObject = null;

    /**
     * field alias name
     *
     * @var string
     */
    protected $alias = '';

    /**
     * the field data type in database, is varchar, char, text ....
     *
     * @var string
     */
    protected $type = '';

    /**
     * the field data length in database
     *
     * @var int
     */
    protected $length = 0;

    /**
     * the field whether is primary key
     *
     * @var bool
     */
    protected $isPK = false;

    /**
     * the field whether is auto increnment key
     *
     * @var bool
     */
    protected $autoIncrement = false;

    /**
     * select result of one row value of the field
     *
     * @var mixed
     */
    protected $value = '';

    /**
     * the field whether is unsigned
     *
     * @var bool 
     */
    protected $unsigned = false;

    /**
     * the field whether is not null
     *
     * @var bool
     */
    protected $notnull = false;

    /**
     * the field comment string
     *
     * @var string
     */
    protected $comment = '';

    /**
     * the field charset
     *
     * @var string
     */
    protected $charset = '';

    /**
     * the field default value,when set null of php variable type, the field default
     * is NULL, other is php scalar variable value
     *
     * @var mixed
     */
    protected $default = null;

    /**
     * the field is unique key,set is key name
     *
     * @var string
     */
    protected $unique = '';

    /**
     * the field is index key, set is key name
     *
     * @var string
     */
    protected $index = '';

    /**
     * the index key type
     *
     * @var string
     */
    protected $indexType = '';

    protected function __init($columnInfo, DbTableObject $table) {
        $this->tableObject = $table;
        $this->charset = $table->getCollate();
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
