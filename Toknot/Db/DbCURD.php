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
    public function create($sql) {
        $this->connectInstance->query($sql);
    }

    public function read($sql) {
        return $this->connectInstance->query($sql);
    }
    public function readAll($sql) {
    }

    public function update();

    public function delete();

    public function readLatest($start =0, $limit = null) {
        $sql = ActiveQuery::order(ActiveQuery::ORDER_DESC);
        $sql .= ActiveQuery::limit($start, $limit);
    }

}
