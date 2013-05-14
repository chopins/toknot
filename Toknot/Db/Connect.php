<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Db;

use Toknot\Db\Exception\DatabaseConnectException;
use \PDOException;
use Toknot\Di\Object;
use Toknot\Db\Dirver\MySQL;
use Toknot\Db\Dirver\SQLite;
use \PDO;

class Connect extends Object {

    private $dsn = null;
    private $username = null;
    private $password = null;
    private $driverOptions = null;
    private static $supportDriver = array();
    private $connectInstance = null;
    public function __construct(DatabaseObject &$connectObject) {
        $this->dsn = $connectObject->dsn;
        $this->username = $connectObject->username;
        $this->password = $connectObject->password;
        $this->driverOptions = $connectObject->dirverOptions;
        $this->connectDatabase();
        $connectObject->setConnectInstance($this);
    }
    public function getConnectInstance() {
        return $this->connectInstance;
    }

    private function connectDatabase() {
        if (class_exists('PDO')) {
            try {
                $this->connectInstance = new PDO($this->dsn, $this->username, $this->password, $this->driverOptions);
            } catch (PDOException $pdoe) {
                throw new DatabaseConnectException($pdoe->getMessage());
            }
        } else {
            $databaseType = strtolower(strtok($this->dsn, ':'));
            switch ($databaseType) {
                case 'mysql':
                    $this->connectInstance = $this->connectMySQL();
                case 'sqlite':
                    $this->connectInstance = $this->connectSQLite();
                default :
                    $this->scanDriver();
                    if (in_array($databaseType, self::$supportDriver)) {
                        $this->connectInstance = $this->importDriver();
                    } else {
                        throw new DatabaseConnectException('Not Support Database');
                    }
                    break;
            }
        }
    }
    private function importDriver() {
        $classList = array_keys(self::$supportDriver);
        return new $classList($this->dsn,$this->username,$this->password,$this->driverOptions);
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