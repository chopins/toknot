<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Db;

use Toknot\Db\Exception\DatabaseException;
use \PDOException;
use Toknot\Di\Object;
use Toknot\Db\Driver\MySQL;
use Toknot\Db\Driver\SQLite;
use \PDO;
use Toknot\Di\StringObject;
use Toknot\Db\ActiveQuery;

class Connect extends Object {

    private $dsn = null;
    private $username = null;
    private $password = null;
    private $driverOptions = null;
    private static $supportDriver = array();
    private $connectInstance = null;
    private $dbINSType = null;

    const DB_INS_PDO = 1;
    const DB_INS_DRIVER = 2;

    /**
     * create Database connect and bind to DatabaseObject instance
     * 
     * @param \Toknot\Db\DatabaseObject $connectObject
     */
    public function __construct(DatabaseObject &$connectObject) {
        $this->dsn = $connectObject->dsn;
        $this->username = $connectObject->username;
        $this->password = $connectObject->password;
        if ($connectObject->driverOptions instanceof StringObject) {
            $this->driverOptions = array($connectObject->driverOptions);
        }

        $this->connectDatabase();
        $connectObject->setConnectInstance($this);
        $connectObject->setDbINSType($this->dbINSType);
    }

    public function getDbDriverType() {
        return $this->dbDriverType;
    }

    public function getConnectInstance() {
        return $this->connectInstance;
    }

    private function connectDatabase() {
        $databaseType = strtolower(strtok($this->dsn, ':'));
        if (class_exists('PDO')) {
            try {
                $this->connectInstance = new PDO($this->dsn, $this->username, $this->password, $this->driverOptions);
            } catch (PDOException $pdoe) {
                throw new DatabaseException($pdoe->getMessage(), $pdoe->getCode());
            }
            $this->dbINSType = self::DB_INS_PDO;
        } else {
            switch ($databaseType) {
                case 'mysql':
                    $this->connectInstance = $this->connectMySQL();
                    break;
                case 'sqlite':
                    $this->connectInstance = $this->connectSQLite();
                    break;
                default :
                    $this->scanDriver();
                    if (in_array($databaseType, self::$supportDriver)) {
                        $this->connectInstance = $this->importDriver();
                    } else {
                        throw new DatabaseException('Not Support Database', 0);
                    }
                    break;
            }
            $this->dbINSType = self::DB_INS_DRIVER;
        }
        ActiveQuery::setDbDriverType(constant('\Toknot\Db\ActiveQuery::DRIVER_' . strtoupper($databaseType)));
    }

    private function importDriver() {
        $classList = array_keys(self::$supportDriver);
        return new $classList($this->dsn, $this->username, $this->password, $this->driverOptions);
    }

    private function scanDriver() {
        $path = __DIR__ . '/Driver';
        $driverFile = scandir($path);
        foreach ($driverFile as $className) {
            if ($className == '.' || $className == '..') {
                continue;
            }
            self::$supportDriver[$className] = strtolower($className);
        }
    }

    private function connectMySQL() {
        return new MySQL($this->dsn, $this->username, $this->password, $this->driverOptions);
    }

    private function connectSQLite() {
        return new SQLite($this->dsn, $this->driverOptions);
    }

}