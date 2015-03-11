<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2015 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Db\Driver;

use Toknot\Db\ActiveQuery;
use Toknot\Db\Exception\DatabaseException;

class MySQL {

    private static $link = null;
    private $sql = null;
    private $queryResult = null;
    private $numRow = 0;
    private $useMySQLi = false;
    private $mysqliStmt = null;
    private $error = '';
    private $errno = 0;

    public function __construct($dsn, $username, $password, $driverOption = array(0)) {
        $dbinfo = ActiveQuery::transformDsn($dsn);

        if (isset($dbinfo->host)) {
            $host = $dbinfo->host;
        } else {
            $dbinfo->host = null;
        }
        if (!isset($dbinfo->port)) {
            $dbinfo->port = 3306;
        }
        if (isset($dbinfo->unix_socket)) {
            $host = $dbinfo->unix_socket;
            $dbinfo->port = null;
        } else {
            $dbinfo->unix_socket = null;
        }
        if (class_exists('mysqli')) {
            self::$link = new \mysqli($dbinfo->host, $username, $password, $dbinfo->dbname, $dbinfo->port, $dbinfo->unix_soket);
            if (self::$link->connect_error) {
                throw new DatabaseException(self::$link->connect_error, self::$link->connect_errno);
            }
            $this->error = self::$link->error;
            $this->errno = self::$link->errno;
            $this->useMySQLi = true;
            return;
        }
        if (isset($dbinfo->port)) {
            $host .= ":{$dbinfo->port}";
        }
        if ($driverOption[0] == 1) {
            self::$link = mysql_pconnect($host, $username, $password);
        } else {
            self::$link = mysql_connect($host, $username, $password);
        }
        if (!self::$link) {
            throw new DatabaseException(mysql_error(), mysql_errno());
        }
        $select = mysql_select_db($dbinfo->dbname, self::$link);
        if (!$select) {
            throw new DatabaseException(mysql_error(self::$link), mysql_errno(self::$link));
        }
    }

    public function beginTransaction() {
        $this->exec('START TRANSACTION');
    }

    public function query($sql) {
        if($this->useMySQLi) {
            $this->queryResult = self::$link->query($sql);
            $this->error = self::$link->error;
            $this->errno = self::$link->errno;
            return $this;
        }
        $this->queryResult = mysql_query($sql, self::$link);
        return $this;
    }

    public function autocommit($mode) {
        if ($this->useMySQLi) {
            return $this->autocommit($mode);
        }
        $mode = (int) $mode;
        return $this->exec("SET AUTOCOMMIT = $mode");
    }

    public function commit() {
        if ($this->useMySQLi) {
            return self::$link->commit();
        } else {
            return mysql_query('COMMIT', self::$link);
        }
    }

    public function rollBack() {
        if ($this->useMySQLi) {
            return self::$link->rollback();
        } else {
            return mysql_query('ROLLBACK', self::$link);
        }
    }

    public function inTransaction() {
        $this->exec("SELECT @@autocommit");
        $row = $this->fetch(ActiveQuery::FETCH_NUM);
        return $row[0];
    }

    public function prepare($sql) {
        if ($this->useMySQLi) {
            $this->mysqliStmt = self::$link->prepare($sql);
            $this->error = self::$link->error;
            $this->errno = self::$link->errno;
        }
        $this->sql = $sql;
        return $this;
    }

    public function exec($sql) {
        if ($this->useMySQLi) {
            $this->queryResult = self::$link->query($sql);
            $this->error = self::$link->error;
            $this->errno = self::$link->errno;
        } else {
            $this->queryResult = mysql_query($sql, self::$link);
        }
    }

    public function execute($params) {
        if ($this->useMySQLi) {
            $c = count($params);
            $type = str_repeat('s', $c);
            $this->mysqliStmt->bind_param($type,$params);
            $es = $this->mysqliStmt->execute();
            if (!$es) {
                throw new DatabaseException(self::$link->error, self::$link->errno);
            }
            $this->queryResult = $this->mysqliStmt->get_result();
            $this->sql = null;
            $this->numRow = $this->queryResult->num_rows;
            $this->error = $this->mysqliStmt->error;
            $this->errno = $this->mysqliStmt->errno;
            return;
        }
        $sql = ActiveQuery::bindParams($params, $this->sql);
        $this->queryResult = mysql_query($sql, self::$link);
        if (!$this->queryResult) {
            throw new DatabaseException(mysql_error(self::$link), mysql_errno(self::$link));
        }
        $this->numRow = mysql_num_rows($this->queryResult);
    }

    public function fetch($fetchStyle = null) {
        $fetchStyle = constant("MYSQLI_{$fetchStyle}");
        if ($this->useMySQLi) {
            return $this->queryResult->fetch_array($fetchStyle);
        }
        if ($fetchStyle == ActiveQuery::FETCH_OBJ) {
            if ($this->useMySQLi) {
                return $this->queryResult->fetch_object();
            }
            return mysql_fetch_object($this->queryResult);
        }
        return mysql_fetch_array($this->queryResult, $fetchStyle);
    }

    public function fetchAll($fetchStyle = null) {
        if ($this->useMySQLi && $fetchStyle != ActiveQuery::FETCH_OBJ) {
            $fetchStyle = constant("MYSQLI_{$fetchStyle}");
            return $this->queryResult->fetch_all($fetchStyle);
        }
        $return = array();
        while ($row = $this->fetch($fetchStyle)) {
            $return[] = $row;
        }
        if ($this->numRow > 100) {
            $this->free();
        }
        return $return;
    }

    public function free() {
        if ($this->useMySQLi) {
            if (!is_null($this->mysqliStmt)) {
                return $this->mysqliStmt->free_result();
            }
            return $this->queryResult->free();
        }
        return ysql_free_result($this->queryResult);
    }

    public function rowCount() {
        if ($this->useMySQLi) {
            if (!is_null($this->mysqliStmt)) {
                return $this->mysqliStmt->affected_rows;
            }
            return self::$link->affected_rows;
        }
        return mysql_affected_rows(self::$link);
    }

    public function lastInsertId() {
        if ($this->useMySQLi) {
            if (!is_null($this->mysqliStmt)) {
                return $this->mysqliStmt->insert_id;
            }
            return self::$link->insert_id;
        }
        return mysql_insert_id(self::$link);
    }

    public function errorInfo() {
        if($this->useMySQLi) {
            return $this->error;
        } 
        return mysql_error(self::$link);
    }
    
    public function errorCode() {
        if($this->useMySQLi) {
            return $this->errno;
        }
        return mysql_errno(self::$link);
    }
}

