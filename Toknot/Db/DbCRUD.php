<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Db;

use Toknot\Boot\Object;
use Toknot\Db\ActiveQuery;
use Toknot\Db\Connect;
use Toknot\Db\Exception\DatabaseException;

abstract class DbCRUD extends Object {

    protected $connectInstance = null;
    public $where = 1;
    public $order = null;
    public $orderBy = null;
    public $fetchStyle = ActiveQuery::FETCH_ASSOC;
    protected $dbDriverType = 0;
    protected $dbINSType = 0;
    protected $queryResult = null;
    private function getDbDriverFetchStyle() {
        if ($this->dbINSType == Connect::DB_INS_DRIVER) {
            return $this->fetchStyle;
        } elseif ($this->dbINSType == Connect::DB_INS_PDO) {
            return constant('PDO::FETCH_' . $this->fetchStyle);
        }
    }

    public function create($sql, $params = array()) {
        $isInsert = strpos($sql, 'INSERT');
        $pdo = $this->connectInstance->prepare($sql);
        if ($pdo) {
            $r = $pdo->execute($params);
            if(!$r) {
                throw new DatabaseException($pdo->errorInfo(),
                                            $pdo->errorCode(), $sql,$params);
            }
        } else {
            $sql = ActiveQuery::bindParams($params, $sql);
            $pdo = $this->connectInstance->query($sql);
            if(!$pdo) {
                throw new DatabaseException($this->connectInstance->errorInfo(),
                                            $this->connectInstance->errorCode(), 
                                            $sql,$params);
            }
        }
        if($isInsert) {
            return $pdo->lastInsertId();
        } else {
            return $pdo->rowCount();
        }
    }
    public function exec($sql) {
        $this->queryResult = $this->connectInstance->query($sql);
    }
    public function fetch() {
        return $this->queryResult->fetch($this->getDbDriverFetchStyle());
    }

    public function readOne($sql, $params = array()) {
        if (stripos($sql, 'LIMIT') === false) {
            $sql .= ' LIMIT 1';
        }
        $pdo = $this->connectInstance->prepare($sql);
        if ($pdo) {
            $r = $pdo->execute($params);
            if(!$r) {
                throw new DatabaseException($pdo->errorInfo(),
                                            $pdo->errorCode(), 
                                            $sql,$params);
            }
        } else {
            $sql = ActiveQuery::bindParams($params, $sql);
            $pdo = $this->connectInstance->query($sql);
            if(!$pdo) {
                throw new DatabaseException($this->connectInstance->errorInfo(),
                                            $this->connectInstance->errorCode(), 
                                            $sql,$params);
            }
        }
        return $pdo->fetch($this->getDbDriverFetchStyle());
    }

    public function readAll($sql, $params = array()) {
        $pdo = $this->connectInstance->prepare($sql);
        if ($pdo) {
            $r = $pdo->execute($params);
            if(!$r) {
                throw new DatabaseException($pdo->errorInfo(),
                                            $pdo->errorCode(),
                                            $sql,$params);
            }
        } else {
            $sql = ActiveQuery::bindParams($params, $sql);
            $pdo = $this->connectInstance->query($sql);
            if(!$pdo) {
                throw new DatabaseException($this->connectInstance->errorInfo(),
                                            $this->connectInstance->errorCode(),
                                            $sql,$params);
            }
        }
        return $pdo->fetchAll($this->getDbDriverFetchStyle());
    }

    public function update($sql, $params = array()) {
        $pdo = $this->connectInstance->prepare($sql);
        if ($pdo) {
            $r  = $pdo->execute($params);
            if(!$r) {
                throw new DatabaseException($pdo->errorInfo(),
                                           $pdo->errorCode(),
                                           $sql,$params);
            }
        } else {
            $sql = ActiveQuery::bindParams($params, $sql);
            $pdo = $this->connectInstance->query($sql);
            if(!$pdo) {
                throw new DatabaseException($this->connectInstance->errorInfo(),
                                            $this->connectInstance->errorCode(),
                                            $sql,$params);
            }
        }
        return $pdo->rowCount();
    }

    public function delete($sql, $params = array()) {
        $pdo = $this->connectInstance->prepare($sql);
        if ($pdo) {
            $r = $pdo->execute($params);
            if(!$r) {
                throw new DatabaseException($pdo->errorInfo(),
                                            $$pdo->errorCode(),
                                            $sql,$params);
            }
        } else {
            $sql = ActiveQuery::bindParams($params, $sql);
            $pdo = $this->connectInstance->query($sql);
            if(!$pdo) {
                throw new DatabaseException($this->connectInstance->errorInfo(),
                                            $this->connectInstance->errorCode(),
                                            $sql,$params);
            }
        }
        return $pdo->rowCount();
    }

}
