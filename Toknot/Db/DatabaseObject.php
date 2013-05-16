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
use \ReflectionClass;
use Toknot\Db\DbTableObject;
use Toknot\Db\DbTableJoinObject;
use \InvalidArgumentException;
use Toknot\Db\Connect;
use Toknot\Db\ActiveQuery;
use Toknot\Db\Exception\DatabaseException;

final class DatabaseObject extends DbCRUD {

    protected $dsn = null;
    protected $username = null;
    protected $password = null;
    private static $tableList = array();
    protected $driverOptions = null;
    protected $tablePrefix = '';
    protected function __construct() {
        
    }
    public function setDbDriverType($type) {
        $this->dbDriverType = $type;
    }

    public static function singleton() {
        return parent::__singleton();
    }

    public function setDSN($dsn) {
        $this->dsn = $dsn;
    }

    public function setTablePrefix($prefix) {
        $this->tablePrefix = $prefix;
    }

    public function setUsername($username) {
        $this->username = $username;
    }

    public function setPassword($password) {
        $this->password = $password;
    }

    public function setDriverOptions($driverOptions) {
        $this->driverOptions = $driverOptions;
    }

    public function setConnectInstance(Connect $connect) {
        $this->connectInstance = $connect->getConnectInstance();
        self::$tableList = $this->showTableList();
    }

    /**
     * get table list of the database
     * 
     * @return array table list
     */
    public function showTableList() {
        $sql = ActiveQuery::showTableList();
        $result = $this->readALL($sql);
        $tableList = array();
        foreach ($result as $tableInfo) {
            $tableList[] = array_shift($tableInfo);
        }
        return $tableList;
    }

    protected function setPropertie($name, $value) {
        $class = __CLASS__;
        throw new DatabaseException("undefined property $class::$name", 0);
    }

    public function __get($propertie) {
        if (isset($this->$propertie)) {
            return $this->$propertie;
        } elseif (in_array($this->tablePrefix . $propertie, self::$tableList)) {
            if (!isset($this->interatorArray[$propertie])) {
                $this->interatorArray[$propertie] = new DbTableObject($propertie, $this);
            }
            return $this->interatorArray[$propertie];
        } else {
            throw new DatabaseException("Table {$this->tablePrefix}{$propertie} not exists");
        }
    }

    public function getDSN() {
        return $this->dsn;
    }

    /**
     * left join some tables for prepared query
     * 
     * @param \Toknot\Db\DbTableObject $table1
     * @param \Toknot\Db\DbTableObject $table2
     * @param \Toknot\Db\DbTableObject $_ 
     * @return DbTableJoinObject    return an instance of DbTableJoinObject
     * @throws InvalidArgumentException
     */
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

}