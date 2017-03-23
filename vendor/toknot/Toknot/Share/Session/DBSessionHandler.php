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
use Toknot\Boot\Kernel;

class DBSessionHandler implements \SessionHandlerInterface {

    /**
     *
     * @var \Toknot\Share\Model
     */
    private $model;
    private $table;
    private $sessionExpired = false;
    private $gcCalled = false;
    private $sidCol = 'sid';
    private $dataCol = 'sess_data';
    private $timeCol = 'create_time';
    private $expireCol = 'expire';

    public function echoException($e) {
        Kernel::single()->echoException($e);
        Kernel::single()->response();
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
        $this->timeCol = Tookit::coalesce($option, 'timeCol', $this->timeCol);
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
        $this->model->delete(['sid' => $sessionId]);
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
            $this->model->save([$this->sidCol => $sesssionId, $this->dataCol => $data, $this->expireCol => $maxlifetime, $this->timeCol => time()]);
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
            $sessionRow = $this->model->getKeyValue($sessionId);
            if ($sessionRow) {
                if ($sessionRow[0][$this->expireCol] + $sessionRow[0][$this->timeCol] < time()) {
                    $this->sessionExpired = true;
                    return '';
                }

                return is_resource($sessionRow[0][$this->dataCol]) ? stream_get_contents($sessionRow[0][$this->dataCol]) : $sessionRow[0][$this->dataCol];
            }

            $this->model->insert([$this->sidCol => $sessionId, $this->dataCol => '', $this->expireCol => 0, $this->timeCol => time()]);
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
            $this->model->delete(["$this->expireCol + $this->timeCol", time(), '<']);
        }
        return true;
    }

}
