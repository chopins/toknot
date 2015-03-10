<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Db;

use Toknot\Db\DbCRUD;
use Toknot\Db\DbTableObject;
use Toknot\Db\DbTableJoinObject;
use \InvalidArgumentException;
use Toknot\Db\Connect;
use Toknot\Db\ActiveQuery;
use Toknot\Db\Exception\DatabaseException;
use Toknot\Boot\DataCacheControl;
use Toknot\Boot\StringObject;

final class DatabaseObject extends DbCRUD {

    protected $dsn = null;
    protected $username = null;
    protected $password = null;
    private $tableList = array();
    protected $driverOptions = array();
    protected $tablePrefix = '';

    /**
     *
     * @var array
     * @access protected
     */
    protected $tableValueList = array();
    protected $databaseStructInfoCache = '';
    protected $databaseCacheExpire = 100;
    public $databaseTableStructCache = '';
    public $databaseTableStructCacheExpire = 100;

    protected function __init() {
        
    }

    public function setDbINSType($type) {
        $this->dbINSType = $type;
    }

    public static function singleton() {
        return parent::__singleton();
    }

    public function setConfig($config) {
        if (isset($config->tablePrefix)) {
            $this->tablePrefix = $config->tablePrefix;
        }
        if (isset($config->databaseStructInfoCache)) {
            $this->databaseStructInfoCache = $config->databaseStructInfoCache;
        }
        if (isset($config->databaseStructInfoCacheExpire)) {
            $this->databaseCacheExpire = $config->databaseStructInfoCacheExpire;
        }

        if (isset($config->databaseTableStructCache)) {
            $this->databaseTableStructCache = $config->databaseTableStructCache;
        }
        if (isset($config->databaseTableStructCacheExpire)) {
            $this->databaseTableStructCacheExpire = $config->databaseTableStructCacheExpire;
        }
    }

    public function setDSN($dsn) {
        $this->dsn = $dsn;
    }

    public function setUsername($username) {
        $this->username = $username;
    }

    public function setPassword($password) {
        $this->password = $password;
    }

    public function setDriverOptions($driverOptions) {
        if ($driverOptions instanceof StringObject) {
            $this->driverOptions[] = $driverOptions->__toString();
            return;
        }
        foreach ($driverOptions as $key => $v) {
            if (!is_numeric($key)) {
                $key = constant($key);
            }
            $this->driverOptions[$key] = $v;
        }
    }

    public function setConnectInstance(Connect $connect) {
        $this->connectInstance = $connect->getConnectInstance();
        $this->tableList = $this->showTableList();
    }

    /**
     * get table list of the database
     * 
     * @return array table list
     */
    public function showTableList() {
        $cache = new DataCacheControl($this->databaseStructInfoCache);
        $cache->useExpire($this->databaseCacheExpire * 60);
        $cacheTable = $cache->get();
        if ($cacheTable === false) {
            $sql = ActiveQuery::showTableList();
            $result = $this->readALL($sql);
            $tableList = array();
            foreach ($result as $tableInfo) {
                $tableList[] = array_shift($tableInfo);
            }
            $cache->save($tableList);
            return $tableList;
        } else {
            return $cacheTable;
        }
    }

    protected function setPropertie($name, $value = null) {
        $this->tableValueList[$this->tablePrefix . $name] = $value;
        //throw new DatabaseException("undefined property $class::$name", 0);
    }

    public function getPropertie($propertie) {
        if (isset($this->$propertie)) {
            return $this->$propertie;
        } elseif (in_array($this->tablePrefix . $propertie, $this->tableList)) {
            if (!isset($this->interatorArray[$propertie])) {
                $this->interatorArray[$propertie] = new DbTableObject($this->tablePrefix . $propertie, $this);
            }
            return $this->interatorArray[$propertie];
        } elseif (isset($this->tableValueList[$propertie])) {
            return $this->tableValueList[$propertie];
        } else {
            $this->tableValueList[$propertie] = new DbTableObject($this->tablePrefix . $propertie, $this, true);
            return $this->tableValueList[$propertie];
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
        return $this->constructArgs($argc, $argv, 'Toknot\Db\DbTableJoinObject');
    }

    public function createTable() {
        if (ActiveQuery::getDbDriverType() != ActiveQuery::DRIVER_SQLITE) {
            throw new DatabaseException('ToKnot only provide create table on SQLite');
        }
        foreach ($this->tableValueList as $tableName => $table) {
            $sql = ActiveQuery::createTable($tableName);
            $sql .= ActiveQuery::setColumn($table);
            $this->create($sql);
        }
    }

}
