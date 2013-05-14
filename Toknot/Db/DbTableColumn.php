<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Db;

use Toknot\Db\DbCRUD;
use Toknot\Db\DbTableObject;

class DbTableColumn extends DbCRUD {
    private $columnName = null;
    private $tableObject = null;
    public function __construct($columnName, DbTableObject &$tableObject) {
        $this->columnName = $columnName;
        $this->tableObject = $tableObject;
        $this->connectInstance = $tableObject->connectInstance;
    }
  
    public function getCurrent() {
        return $this->currentValue;
    }
}