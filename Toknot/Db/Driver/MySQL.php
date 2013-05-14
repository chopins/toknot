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
    public function __construct($dsn, $username, $password, $driverOption = array(0)) {
        $dbinfo = ActiveQuery::transformDsn($dsn);
        if($driverOption[0] == 1) {
            self::$link = mysql_pconnect($dbinfo->host, $username, $password);
        } else {
            self::$link = mysql_connect($dbinfo->host,$username,$password);
        }
        if(!self::$link) {
            throw new DatabaseException(mysql_error(),  mysql_errno());
        }
        $select = mysql_select_db($dbinfo->dbname, self::$link);
        if(!$select) {
            throw new DatabaseException(mysql_error(self::$link), mysql_errno(self::$link));
        }
    }
    public function prepare($sql) {
        $this->sql = $sql;
        return $this;
    }
    public function execute($params) {
        foreach ($params as &$v) {
            $v = addslashes($v);
            $v = "'$v'";
        }
        $sql = preg_replace('/[^\'^".]?[.^\'^"]/', $params, $this->sql);
        $this->query = mysql_query($sql, self::$link);
        if(!$this->query) {
            throw new DatabaseException(mysql_error(self::$link), mysql_errno(self::$link));
        }
        $this->sql = null;
        $this->numRow = mysql_num_rows($this->query);
    }
    public function fetch() {
        return mysql_fetch_array($this->query);
    }
    public function fetchAll() {
        $return = array();
        while ($row = mysql_fetch_array($this->query)) {
            $return[] = $row;
        }
        if($this->numRow > 100) {
            mysql_free_result($this->query);
        }
        return $return;
    }
    public function rowCount() {
        return mysql_affected_rows(self::$link);
    }
    public function lastInsertId() {
        return mysql_insert_id(self::$link);
    }
}

?>
