<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2017 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Share\Session;

use Toknot\Share\DB\DBA;
use Toknot\Boot\Tookit;
use Toknot\Exception\BaseException;

class DBSessionHandler implements \SessionHandlerInterface {

    /**
     *
     * @var \Toknot\Share\DBTable
     */
    private $model;
    private $table;

    /**
     *
     * @var boolean
     */
    private $sessionExpired = false;
    private $gcCalled = false;
    private $sidCol = 'sid';
    private $dataCol = 'sess_data';

    /**
     * create time of column name
     *
     * @var string
     */
    private $createTimeCol = 'create_time';

    /**
     * expire of column name
     *
     * @var string
     */
    private $expireCol = 'expire';

    public function echoException($e) {
        throw new BaseException($e->getMessage(), $e->getCode(), $e->getFile(), $e->getLine(), $e);
    }

    /**
     * 
     * @param string $table
     */
    public function __construct($table, $option = []) {
        $this->table = $table;
        $this->sidCol = Tookit::coalesce($option, 'idCol', $this->sidCol);
        $this->dataCol = Tookit::coalesce($option, 'dataCol', $this->dataCol);
        $this->expireCol = Tookit::coalesce($option, 'expireCol', $this->expireCol);
        $this->createTimeCol = Tookit::coalesce($option, 'timeCol', $this->createTimeCol);
    }

    public function isSessionExpired() {
        return $this->sessionExpired;
    }

    /**
     * the php internal call occur exception must direct reponse content
     * 
     * @param type $savePath
     * @param type $sessionName
     * @return boolean
     */
    public function open($savePath, $sessionName) {
        try {
            $this->model = DBA::table($this->table);
        } catch (\Exception $e) {
            $this->echoException($e);
            return false;
        }
        return true;
    }

    public function gc($maxlifetime) {
        $this->gcCalled = true;
        return true;
    }

    public function destroy($sessionId) {
        $this->model->delete($this->model->cols($this->sidCol)->eq($sessionId));
        return true;
    }

    public function write($sesssionId, $data) {
        $maxlifetime = (int) ini_get('session.gc_maxlifetime');
        try {
            DBA::single()->beginTransaction();
        } catch (\Exception $e) {
            return false;
        }
        try {
            $dataCol = $this->model->getTableStructure()['column'][$this->dataCol];
            if (!isset($dataCol['length'])) {
                $dataCol['length'] = DBA::single()->getColumnTypeDefaultLength($dataCol['type']);
            }
            $dataLen = strlen($data);
            if ($dataLen > $dataCol['length']) {
                throw new BaseException("the maximun length of the data column of session table is {$dataCol['length']}, $dataLen be given");
            }
            $this->model->save([$this->sidCol => $sesssionId, $this->dataCol => $data, $this->expireCol => $maxlifetime, $this->createTimeCol => time()]);
        } catch (\Exception $e) {
            DBA::single()->rollBack();
            DBA::single()->beginTransaction();
            $this->echoException($e);
            return false;
        }
        return true;
    }

    public function read($sessionId) {
        $this->sessionExpired = false;
        DBA::single()->beginTransaction();
        try {
            $sessionRow = $this->model->findKeyRow($sessionId);

            if ($sessionRow) {
                if ($sessionRow[$this->expireCol] + $sessionRow[$this->createTimeCol] < time()) {
                    $this->sessionExpired = true;
                    return '';
                }
                return $sessionRow[$this->dataCol];
            }

            $this->model->insert([$this->sidCol => $sessionId, $this->dataCol => '', $this->expireCol => 0, $this->createTimeCol => time()]);
        } catch (\Exception $e) {
            DBA::single()->rollBack();

            DBA::single()->beginTransaction();
            $this->echoException($e);
        }
        return '';
    }

    public function close() {
        try {
            DBA::single()->commit();
        } catch (\Exception $e) {
            return false;
        }
        if ($this->gcCalled) {
            $this->gcCalled = false;
            $filter = $this->model->filter();
            $col = $filter->cols($this->createTimeCol)->add($filter->cols($this->expireCol));
            $filter->lt($col, time());
            $this->model->delete($filter);
        }
        return true;
    }

}
