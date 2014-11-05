<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Db\Driver;

use \SQLiteDatabase;
use \SQLite3;
use Toknot\Db\ActiveQuery;
use Toknot\Db\Exception\DatabaseException;

class SQLite {

    /**
     *
     * @var  SQLiteDatabase
     * @access private
     */
    private $sqlLiteDatabase = null;
    private $errorMesage = '';
    private $sql = '';
    private $driverOptions = array();
    private $queryResult = null;
    private $driverVersion = 3;
    private $stmt = null;

    public function __construct($dsn, $driverOption = array('mode' => '0666')) {
        list($prefix, $file) = explode(':', $dsn, 2);
        $dir = dirname($file);
        if($prefix === 'sqlite2') {
            $this->driverVersion = 2;
        }
        if (is_readable($dir)) {
            if (class_exists('SQLite3') && $this->driverVersion === 3) {
                if (!isset($driverOption['flags'])) {
                    $driverOption['flags'] = (SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE);
                }
                if (!isset($driverOption['encryptionKey'])) {
                    $driverOption['encryptionKey'] = null;
                }
                $this->driverDatabase = new SQLite3($file, $driverOption['flags'], $driverOption['encryptionKey']);
                $this->driverVersion = 3;
            } else {
                $this->sqlLiteDatabase = new SQLiteDatabase($file, $driverOption['mode'], $this->errorMesage);
                $this->driverVersion = 2;
            }
        } else {
            throw new DatabaseException("Directory $dir is not readable", 0);
        }
    }

    public function getDriverVersion() {
        return $this->driverVersion;
    }

    public function beginTransaction() {
        $this->exec('START TRANSACTION');
    }

    public function commit() {
        $this->exec('COMMIT');
    }

    public function exec($sql) {
        if ($this->sqliteVersion === 3) {
            return $this->sqlLiteDatabase->exec($sql);
        }
        return $this->sqlLiteDatabase->queryExec($sql, $this->errorMesage);
    }

    public function getParamsType($p) {
        if (is_int($p)) {
            return SQLITE3_INTEGER;
        } else if (is_null($p)) {
            return SQLITE3_NULL;
        } else {
            return SQLITE3_TEXT;
        }
    }

    public function execute($params) {
        if ($this->driverVersion === 3) {
            $paramsCount = count($params);
            for ($i = 0; $i < $paramsCount; $i++) {
                $this->stmt->bindValue($i, $params[$i], $this->getParamsType($params[$i]));
            }
            if (($this->queryResult = $this->stmt->execute())) {
                return true;
            } else {
                return false;
            }
        }
        $sql = ActiveQuery::bindParams($params, $this->sql);
        $result_type = $this->driverOptions['resultType'];
        $this->queryResult = $this->sqlLiteDatabase->query($sql, $result_type, $this->errorMesage);
        if (!$this->query) {
            throw new DatabaseException($this->errorMesage, 0);
        }
    }

    public function fetch($resultType = 'BOTH', $decodeBinary = true) {
        if ($this->driverVersion === 3) {
            $fetchStyle = constant('SQLITE3_' . $resultType);
            return $this->queryResult->fetchArray($fetchStyle);
        }
        $fetchStyle = constant('SQLITE_' . $resultType);
        if ($resultType == ActiveQuery::FETCH_OBJ) {
            return $this->queryResult->fetchObject();
        }
        return $this->queryResult->fetch($fetchStyle, $decodeBinary);
    }

    public function fetchAll($resultType = 'BOTH', $decodeBinary = true) {
        if ($this->driverVersion === 3) {
            $allResult = array();
            while ($row = $this->fetch($resultType)) {
                $allResult[] = $row;
            }
            return $allResult;
        }
        $fetchStyle = constant('SQLITE_' . $resultType);
        return $this->queryResult->fetchAll($fetchStyle, $decodeBinary);
    }

    public function prepare($string, $driverOptions = array('resultType' => 'BOTH')) {
        if ($this->driverOptions === 3) {
            $this->stmt = $this->sqlLiteDatabase->prepare($string);
            return;
        }
        $this->sql = $string;
        $fetchStyle = constant('SQLITE_' . $driverOptions['resultType']);
        $this->driverOptions = $driverOptions['resultType'] = $fetchStyle;
        return $this;
    }

    public function query($sql) {
        $this->queryResult = $this->sqlLiteDatabase->query($sql);
        return $this;
    }

    public function rollBack() {
        return $this->exec('ROLLBACK');
    }

    public function lastInsertId() {
        if ($this->driverVersion === 3) {
            return $this->sqlLiteDatabase->lastInsertRowID();
        }
        return $this->sqlLiteDatabase->lastInsertId();
    }

    public function rowCount() {
        return $this->sqlLiteDatabase->changes();
    }
    public function errorInfo() {
        if($this->driverVersion === 3) {
            return $this->sqlLiteDatabase->lastErrorMsg();
        } 
        return sqlite_error_string($this->errorCode());
    }
    
    public function errorCode() {
        if($this->driverVersion === 3) {
            return $this->sqlLiteDatabase->lastErrorCode();
        }
        return $this->sqlLiteDatabase->lastError();
    }
}

