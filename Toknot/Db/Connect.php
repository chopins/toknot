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
use PDOException;
use Toknot\Di\Object;

class Connect extends Object{

    private $dsn = null;
    private $username = null;
    private $password = null;
    private $driverOptions = null;

    public function __construct(DatabaseObject &$connectObject) {
        $this->dsn = $connectObject->dsn;
        $this->username = $connectObject->username;
        $this->password = $connectObject->password;
        $this->driverOptions = $connectObject->dirverOptions;
        $connectObject->connectInstance = $this->connectDatabase();
    }

    private function connectDatabase() {
        if (class_exists('PDO')) {
            try {
                return new \PDO($this->dsn, $this->username, $this->password, $this->driverOptions);
            } catch (PDOException $pdoe) {
                throw new DatabaseConnectException($pdoe->getMessage());
            }
        } else {
            $databaseType = strtolower(strtok($this->dsn, ':'));
            switch ($databaseType) {
                case 'mysql':
                    return $this->connectMySQL();
                case 'sqlite':
                    return $this->connectSQLite();
                default :
                    throw new DatabaseConnectException('Not Support Database');
            }
        }
    }
    private function connectMySQL() {
        
    }
    private function connectSQLite() {
        
    }
 }