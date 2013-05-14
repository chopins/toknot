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

abstract class DbCRUD extends Object {
    protected $connectInstance = null;
    public function create($sql,$params = array()) {
        $pdo = $this->connectInstance->prepare($sql);
        $pdo->execute($params);
        return $pdo->lastInsertId();
    }
    
    public function read($sql, $params = array()) {
        $pdo = $this->connectInstance->prepare($sql);
        $pdo->execute($params);
        return $pdo->fetch(PDO::FETCH_ASSOC);
    }
    public function readAll($sql, $params = array()) {
        $pdo = $this->connectInstance->prepare($sql);
        $pdo->execute($params);
        return $pdo->fetchAll(PDO::FETCH_ASSOC);
    }


    public function update($sql, $params = array()) {
        $pdo = $this->connectInstance->prepare($sql);
        $pdo->execute($params);
        return $pdo->rowCount();
    }

    public function delete($sql, $params= array()) {
        $pdo = $this->connectInstance->prepare($sql);
        $pdo->execute($params);
        return $pdo->rowCount();
    }

    public function readLatest($start =0, $limit = null) {
        $sql = ActiveQuery::order(ActiveQuery::ORDER_DESC);
        $sql .= ActiveQuery::limit($start, $limit);
        $sql = ActiveQuery::select() . $sql;
        $this->readAll($sql);
    }

}
