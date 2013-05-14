<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Di;

use Toknot\Db\DbCRUD;
use \ReflectionClass;
use Toknot\Db\DbTableObject;
use Toknot\Db\DbTableJoinObject;
use \InvalidArgumentException;
use Toknot\Db\Connect;
use Toknot\Db\ActiveQuery;

class DatabaseObject extends DbCRUD {

    protected $dsn = null;
    protected $user = null;
    protected $password = null;
    private static $tableList = array();
    protected function __construct() {
    }
    public static function singleton() {
        return parent::__singleton();
    }
    public function setConnectInstance(Connect $connect) {
        $this->connectInstance = $connect->getConnectInstance();
        self::$tableList = $connect->showTableList();
    }
    public function showTableList() {
        $sql = ActiveQuery::showTableList();
        return $this->readALL($sql);
    }

    protected function setPropertie($propertie, $value) {
    }

    public function __get($propertie) {
        if (isset($this->$propertie)) {
            return $this->$propertie;
        } elseif(in_array($propertie, self::$tableList)) {
            return new DbTableObject($propertie, $this);
        }
    }

    public function getDSN() {
        return $this->dsn;
    }

    public function tableJOIN(DbTableObject $table1, DbTableObject $table2) {
        $argv = func_get_args();
        $argc = func_num_args();
        for ($i = 2; $i < $argc; $i++) {
            if (!$argc[$i] instanceof DbTableObject) {
                throw new InvalidArgumentException();
            }
        }
        $ref = new ReflectionClass('Toknot\Db\DbTableJoinObject');
        return $ref->newInstanceArgs($argv);
    }
    public function findAllBySQL($sql, $params = array()) {
        return $this->readAll($sql, $params);
    }
}