<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Db\Driver;

use Toknot\Db\ActiveQuery;
use Toknot\Db\Exception\DatabaseException;

class MySQL {

    private static $link = null;
    private $sql = null;
    private $query = null;
    private $numRow = 0;
    private $useMySQLi = false;
    private $mysqliStmt = null;

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
            if ($this->link->connect_error) {
                throw new DatabaseException($this->link->connect_error, $this->link->connect_errno);
            }
            $this->useMySQLi = true;
            return;
        }
        if (isset($dbinfo->port)) {
            $host .= ":{$this->port}";
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
            return mysql_query('COMMIT');
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
        $result = self::$link->query("SELECT @@autocommit");
        $row = $result->fetch_row();
        return $row[0];
    }

    public function prepare($sql) {
        if ($this->useMySQLi) {
            $this->mysqliStmt = self::$link->prepare($sql);
        }
        $this->sql = $sql;
        return $this;
    }

    public function exec($sql) {
        if ($this->useMySQLi) {
            return self::$link->query($sql);
        } else {
            return mysql_query($sql, self::$link);
        }
    }

    public function execute($params) {
        if ($this->useMySQLi) {
            $c = count($params);
            $type = sprintf("%'s{$c}s", 's');
            array_unshift($params, $type);
            call_user_func_array(array($this->useMySQLi,'bind_param'), $params);
            $es = $this->mysqliStmt->execute();
            if (!$es) {
                throw new DatabaseException(self::$link->error, self::$link->errno);
            }
            $this->query = $this->mysqliStmt->get_result();
            $this->sql = null;
            $this->numRow = $this->query->num_rows;
            return;
        }
        foreach ($params as &$v) {
            $v = addslashes($v);
            $v = "'$v'";
        }
        $sql = str_replace('?', $params, $this->sql);
        $this->query = mysql_query($sql, self::$link);
        if (!$this->query) {
            throw new DatabaseException(mysql_error(self::$link), mysql_errno(self::$link));
        }
        $this->numRow = mysql_num_rows($this->query);
    }

    public function fetch($fetchStyle = null) {
        $const = get_defined_constants();
        $fetchStyle = $const["MYSQLI_{$fetchStyle}"];
        if ($this->useMySQLi) {
            return $this->query->fetch_array($fetchStyle);
        }
        if ($fetchStyle == ActiveQuery::FETCH_OBJ) {
            if ($this->useMySQLi) {
                return $this->query->fetch_object();
            }
            return mysql_fetch_object($this->query);
        }
        return mysql_fetch_array($this->query, $fetchStyle);
    }

    public function fetchAll($fetchStyle = null) {
        if ($this->useMySQLi && $fetchStyle != ActiveQuery::FETCH_OBJ) {
            $const = get_defined_constants();
            $fetchStyle = $const["MYSQLI_{$fetchStyle}"];
            return $this->query->fetch_all($fetchStyle);
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
            return $this->query->free();
        }
        return ysql_free_result($this->query);
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

}

?>
