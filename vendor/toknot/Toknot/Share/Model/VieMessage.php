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
use Toknot\Exception\BaseException;
use Toknot\Boot\Object;
use Toknot\Boot\Kernel;
use Toknot\Boot\GlobalFilter;

/**
 * VieMessage
 *
 * @author chopin
 */
class VieMessage extends Object {

    /**
     * message table
     *
     * @var string
     * @readonly
     */
    protected $tableName = '';

    /**
     * lock feild name in message table
     *
     * @var string
     * @readonly
     */
    protected $lockFeild = 'lockFlag';
    protected $tableInstance = null;

    /**
     * not execute flag
     *
     * @var mix
     * @readonly
     */
    protected $unprocessedFlag = 0;

    /**
     * It has been executed of flag
     *
     * @var mix
     * @readonly
     */
    protected $processedFlag = 1;

    /**
     * the number of messages processed be invoked
     *
     * @var int
     * @readonly
     */
    protected $limit = 5;

    /**
     * message table last insert id
     *
     * @var int 
     * @readonly
     */
    protected $lastId = 0;

    /**
     * message table primary key name
     *
     * @var string 
     * @readonly
     */
    protected $pk = '';

    /**
     * the value is prefix of uniqid
     *
     * @var string
     * @readonly
     */
    protected $lockPrefix = 0;
    protected $kernel = null;

    /**
     * mutex table name, table structure is below:
     *  id    : auto increment key
     *  mutex : unique key, value is message mutex value
     * @var string
     * @readonly
     */
    protected $mutexTable = 'mutexQueue';

    /**
     * the table mutexQueue of column name, the column must is unique key, value is $mutexMappingFeild in message
     *
     * @var string
     * @readonly
     */
    protected $mutexFeild = 'mutex';

    /**
     * the feild must is message of column and specify same value is mutex
     *
     * @var string
     * @readonly 
     */
    protected $mutexMappingFeild = null;

    /**
     * 
     * @param string $messageTable     message save table name of database
     */
    public function __construct($messageTable) {
        $this->tableName = $messageTable;
        $this->tableInstance = DBA::table($this->tableName);
        $this->pk = $this->tableInstance->primaryKey();
        $this->kernel = Kernel::single();
        if ($this->kernel->isCLI) {
            $this->lockPrefix = $this->kernel->pid . $this->kernel->tid;
        } else {
            $this->lockPrefix = GlobalFilter::env('REMOTE_ADDR') . GlobalFilter::env('REMOTE_PORT');
        }
    }

    public function __get($name) {
        if ($this->__isReadonlyProperty($name)) {
            return $this->$name;
        }
        throw BaseException::undefineProperty($this, $name);
    }

    public function getLockPrefix() {
        return $this->lockPrefix;
    }

    /**
     * set mutex feild
     * 
     * @param string $feild
     */
    public function setMutexMappingFeild($feild) {
        $this->mutexMappingFeild = $feild;
    }

    /**
     * set mutex queue
     * 
     * @param string $table
     */
    public function setMutexTable($table) {
        $this->mutexTable = $table;
    }

    /**
     * set mutex feild name
     * 
     * @param string $feild
     */
    public function setMutexFeild($feild) {
        $this->mutexFeild = $feild;
    }

    /**
     * send message
     * 
     * @param array $data
     */
    public function sendMessage($data) {
        $data[$this->lockFeild] = $this->unprocessedFlag;
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
        $this->unprocessedFlag = $flag;
        return $this;
    }

    /**
     * 
     * @param int|string $flag
     * @return $this
     */
    public function processFlag($flag) {
        $this->processedFlag = $flag;
        return $this;
    }

    /**
     * 
     * @param string $prefix
     */
    public function setLockPrefix($prefix) {
        $this->lockPrefix = $prefix;
    }

    public function uniqid() {
        $prefix = md5($this->lockPrefix . $this->lastId) . '-';
        if (function_exists('hash')) {
            $uniqid = hash('sha256', uniqid($prefix, true));
        } else {
            $uniqid = sha1(uniqid($prefix, true));
        }
    }

    /**
     * receive message
     * 
     * @param callable $receiver    the function be invoke and pass a message data
     * @param boolean $rollback
     * @throws VieMessageException
     */
    public function receiveMessage($receiver, $rollback = true) {
        $uniqid = $this->uniqid();

        $filter = QueryHelper::equal($this->lockFeild, $this->unprocessedFlag);

        $set = QueryHelper::set($this->lockFeild, $uniqid);
        $mutexRow = [];
        if ($this->mutexMappingFeild) {
            $mutexRow = $this->insertMutex($filter);
            $filter = QueryHelper::andX($filter, [$this->mutexMappingFeild, $mutexRow, 'in']);
        }
        
        $this->tableInstance->update($set, $filter, $this->limit);

        $where = QueryHelper::equal($this->lockFeild, $uniqid);
        $res = $this->tableInstance->iterator($where, $this->limit);
        foreach ($res as $row) {
            $pkv = $row[$this->pk];
            try {
                self::callFunc($receiver, [$row]);
            } catch (\Exception $e) {
                $rollbackMsg = $this->rollback($rollback, $uniqid, $mutexRow);
                throw new VieMessageException($pkv, $uniqid, $e, $rollbackMsg);
            }
            $where = QueryHelper::andX(QueryHelper::equal($this->pk, $pkv), QueryHelper::equal($this->lockFeild, $uniqid));
            $this->tableInstance->update(QueryHelper::set($this->lockFeild, $this->processedFlag), $where, 1);
        }
        $this->deleteMutex($mutexRow);
    }

    protected function insertMutex($filter) {
        $mutex = DBA::table($this->mutexTable);
        $mutexRow = [];
        DBA::transaction(function() use($mutex, &$mutexRow, $filter) {
            $n = 1;
            $exist = [];
            $newfilter = $filter;
            $cont = false;
            do {
                try {
                    $n++;
                    $this->tableInstance->setColumn($this->mutexMappingFeild);
                    $sql = $this->tableInstance->select($newfilter)->limit(1)->getLastSql();
                    $mutex->setColumn($this->mutexFeild);
                    $mutex->insertSelect($sql);
                    $id = $mutex->lastId();
                    $mutex = $mutex->select(['id', $id]);
                    $exist[] = $mutex[$this->mutexMappingFeild];
                    $f = [$this->mutexMappingFeild, $exist, 'out'];
                    $newfilter = QueryHelper::andX($filter, $f);
                    $mutexRow[] = $mutex[$this->mutexMappingFeild];
                } catch (\PDOException $e) {
                    $cont = stripos($e->getMessage(), 'Duplicate') !== false;
                }
                if ($n >= $this->limit) {
                    return;
                }
            } while ($cont);
            throw $e;
        });
        return $mutexRow;
    }

    protected function deleteMutex($mutexRow) {
        $mutex = DBA::table($this->mutexTable);
        $mutex->delete([$this->mutexMappingFeild, $mutexRow, 'in']);
    }

    /**
     * send message and receive message
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
    protected function rollback($rollback, $uniqid, $mutexRow) {
        if ($rollback) {
            try {
                $set = QueryHelper::set($this->lockFeild, $this->unprocessedFlag);
                $filter = QueryHelper::equal($this->lockFeild, $uniqid);
                $this->tableInstance->update($set, $filter, $this->limit);
                $this->deleteMutex($mutexRow);
                return 'Has been rollback unprocess message';
            } catch (\Exception $e) {
                $err = $e->getMessage();
                return "rollback failure,Message:$err";
            }
        }
    }

}
