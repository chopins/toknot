<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Db;

use Toknot\Di\Object;
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
    private function getDbDriverFetchStyle() {
        if ($this->dbINSType == Connect::DB_INS_DRIVER) {
            return $this->fetchStyle;
        } elseif ($this->dbINSType == Connect::DB_INS_PDO) {
            return constant('PDO::FETCH_' . $this->fetchStyle);
        }
    }

    public function create($sql, $params = array()) {
        $pdo = $this->connectInstance->prepare($sql);
        if ($pdo) {
            $pdo->execute($params);
        } else {
            $sql = ActiveQuery::bindParams($params, $sql);
            $pdo = $this->connectInstance->query($sql);
            if(!$pdo) {
                throw new DatabaseException($this->connectInstance->errorInfo(),
                                            $this->connectInstance->errorCode());
            }
        }
        return $pdo->lastInsertId();
    }

    public function read($sql, $params = array()) {
        if (stripos($sql, 'LIMIT') === false) {
            $sql .= ' LIMIT 1';
        }
        $pdo = $this->connectInstance->prepare($sql);
        if ($pdo) {
            $pdo->execute($params);
        } else {
            $sql = ActiveQuery::bindParams($params, $sql);
            $pdo = $this->connectInstance->query($sql);
            if(!$pdo) {
                throw new DatabaseException($this->connectInstance->errorInfo(),
                                            $this->connectInstance->errorCode());
            }
        }
        return $pdo->fetch($this->getDbDriverFetchStyle());
    }

    public function readAll($sql, $params = array()) {
        $pdo = $this->connectInstance->prepare($sql);
        if ($pdo) {
            $pdo->execute($params);
        } else {
            $sql = ActiveQuery::bindParams($params, $sql);
            $pdo = $this->connectInstance->query($sql);
            if(!$pdo) {
                throw new DatabaseException($this->connectInstance->errorInfo(),
                                            $this->connectInstance->errorCode());
            }
        }
        return $pdo->fetchAll($this->getDbDriverFetchStyle());
    }

    public function update($sql, $params = array()) {
        $pdo = $this->connectInstance->prepare($sql);
        if ($pdo) {
            $pdo->execute($params);
        } else {
            $sql = ActiveQuery::bindParams($params, $sql);
            $pdo = $this->connectInstance->query($sql);
            if(!$pdo) {
                throw new DatabaseException($this->connectInstance->errorInfo(),
                                            $this->connectInstance->errorCode());
            }
        }
        return $pdo->rowCount();
    }

    public function delete($sql, $params = array()) {
        $pdo = $this->connectInstance->prepare($sql);
        if ($pdo) {
            $pdo->execute($params);
        } else {
            $sql = ActiveQuery::bindParams($params, $sql);
            $pdo = $this->connectInstance->query($sql);
            if(!$pdo) {
                throw new DatabaseException($this->connectInstance->errorInfo(),
                                            $this->connectInstance->errorCode());
            }
        }
        return $pdo->rowCount();
    }

}
