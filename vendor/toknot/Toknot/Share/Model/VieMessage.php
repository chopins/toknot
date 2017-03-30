<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2017 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Share\Model;

use Toknot\Share\DB\DBA;
use Toknot\Share\DB\QueryHelper;
use Toknot\Exception\VieMessageException;
use Toknot\Boot\Object;
use Toknot\Boot\Kernel;
use Toknot\Boot\GlobalFilter;

/**
 * VieMessage
 *
 * @author chopin
 */
class VieMessage extends Object {

    protected $tableName = '';
    protected $lockFeild = 'lockFlag';
    protected $tableInstance = null;
    protected $unprocessed = 0;
    protected $processed = 1;
    protected $limit = 5;
    protected $lastId = 0;
    protected $pk = '';
    protected $lockPrefix = 0;
    protected $kernel = null;

    /**
     * 
     * @param string $table     message save table name of database
     */
    public function __construct($table) {
        $this->tableName = $table;
        $this->tableInstance = DBA::table($this->tableName);
        $this->pk = $this->tableInstance->primaryKey();
        $this->kernel = Kernel::single();
        if ($this->kernel->isCLI) {
            $this->lockPrefix = $this->kernel->pid . $this->kernel->tid;
        } else {
            $this->lockPrefix = GlobalFilter::env('REMOTE_ADDR') . GlobalFilter::env('REMOTE_PORT');
        }
    }

    /**
     * 
     * @param array $data
     */
    public function sendMessage($data) {
        $data[$this->lockFeild] = $this->unprocessed;
        $this->lastId = $this->tableInstance->insert($data);
    }

    /**
     * 
     * @param int $limit
     * @return $this
     */
    public function setLimit($limit) {
        $this->limit = $limit;
        return $this;
    }

    /**
     * 
     * @param string $feild
     * @return $this
     */
    public function setLockFeild($feild) {
        $this->lockFeild = $feild;
        return $this;
    }

    /**
     * 
     * @param int|string $flag
     * @return $this
     */
    public function setUnporcessFlag($flag) {
        $this->unprocessed = $flag;
        return $this;
    }

    /**
     * 
     * @param int|string $flag
     * @return $this
     */
    public function processFlag($flag) {
        $this->processed = $flag;
        return $this;
    }

    /**
     * 
     * @param string $prefix
     */
    public function setLockPrefix($prefix) {
        $this->lockPrefix = $prefix;
    }

    /**
     * 
     * @param callable $receiver
     * @param boolean $rollback
     * @throws VieMessageException
     */
    public function receiveMessage($receiver, $rollback = true) {
        $prefix = md5($this->lockPrefix, $this->lastId) . '-';
        $uniqid = uniqid($prefix, true);

        $filter = QueryHelper::equal($this->lockFeild, $this->unprocessed);
        $this->tableInstance->update([$this->lockFeild => $uniqid], $filter, $this->limit);

        $where = QueryHelper::equal($this->lockFeild, $uniqid);
        $res = $this->tableInstance->iterator($where, $this->limit);
        foreach ($res as $row) {
            $pkv = $row[$this->pk];
            try {
                self::callFunc($receiver, [$row]);
            } catch (\Exception $e) {
                $rollbackMsg = $this->rollback($rollback, $uniqid);
                throw new VieMessageException($pkv, $uniqid, $e, $rollbackMsg);
            }
            $where = QueryHelper::andX(QueryHelper::equal($this->pk, $pkv), QueryHelper::equal($this->lockFeild, $uniqid));
            $this->tableInstance->update(QueryHelper::set($this->lockFeild, $this->processed), $where, 1);
        }
    }

    /**
     * 
     * @param array $messageData
     * @param callable $receiver
     * @param boolean $rollback
     */
    public function process($messageData, $receiver, $rollback = true) {
        $this->sendMessage($messageData);
        $this->setLockPrefix(serialize($messageData));
        $this->receiveMessage($receiver, $rollback);
    }

    /**
     * 
     * @param boolean $rollback
     * @param string $uniqid
     * @return string
     */
    protected function rollback($rollback, $uniqid) {
        if ($rollback) {
            try {
                $set = QueryHelper::set($this->lockFeild, $this->unprocessed);
                $filter = QueryHelper::equal($this->lockFeild, $uniqid);
                $this->tableInstance->update($set, $filter, $this->limit);
                return 'Has been rollback unprocess message';
            } catch (\Exception $e) {
                $err = $e->getMessage();
                return "rollback failure,Message:$err";
            }
        }
    }

}
