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
    public $where = 1;
    public $order = null;
    public $orderBy = null;
    public $fetchStyle = ActiveQuery::FETCH_ASSOC;
    public function create($sql,$params = array()) {
        $pdo = $this->connectInstance->prepare($sql);
        $pdo->execute($params);
        return $pdo->lastInsertId();
    }
    
    public function read($sql, $params = array()) {
        if(stripos($sql, 'LIMIT') === false) {
            $sql .= ' LIMIT 1';
        }
        $pdo = $this->connectInstance->prepare($sql);
        $pdo->execute($params);
        return $pdo->fetch($this->fetchStyle);
    }
    public function readAll($sql, $params = array()) {
        $pdo = $this->connectInstance->prepare($sql);
        $pdo->execute($params);
        return $pdo->fetchAll($this->fetchStyle);
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
}
