<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2018 chopin xiao (xiao@toknot.com)
 */

namespace Toknot\Lib\Model\Auth;

use Toknot\Lib\Model\Database\DB;
use Toknot\Boot\Kernel;
use Toknot\Boot\TKObject;

class User extends TKObject {

    protected $id = null;
    protected $idFeild = 'id';
    private $casVer = 0;

    /**
     *
     * @var \Toknot\Lib\Model\Database\TableModel
     */
    protected $table = null;

    /**
     *
     * @var \Toknot\Lib\Model\Database\ActiveRecord
     */
    protected $record = null;
    protected $properList = [];
    public $enableCas = false;
    public $enableSalt = false;
    public static $saltFeild = 'salt';
    public static $casFeild = '_cas_ver';
    public static $passwordFeild = 'password';
    public static $saltLength = 10;
    public static $dbkey = '';
    public static $useAlgo = '';
    public static $hmac = false;
    public static $hashKey = '';

    public function __construct($table, $cas = false, $id = null) {
        $this->table($table);
        if ($cas) {
            $this->enableCas();
        }
        $this->id = $id;
        if ($this->id) {
            $this->find();
        }
    }

    public function isNobody() {
        if (!$this->id || $this->record->isIdler()) {
            return true;
        }
        return false;
    }

    protected function hashToken($bgdata = '') {
        $data = $bgdata . $this->record->username . $this->record->email . $this->record->id . $this->record->password . $this->salt;
        $data .= Kernel::serverEntropy();
        return Kernel::hash($data, self::$hashKey, self::$useAlgo, self::$hmac);
    }

    public function generateSiginUserToken($bgdata = '') {
        return self::generatePassword($this->hashToken($bgdata));
    }

    public function checkSiginUserToken($hash, $bgdata = '') {
        $needhash = $this->hashToken($bgdata);
        return self::passwordVerify($needhash, $hash);
    }

    public static function generatePassword($password) {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    public static function passwordVerify($password, $hash) {
        return password_verify($password, $hash);
    }

    public function enableCas($casCol = '_cas_ver') {
        $this->enableCas = true;
        self::$casFeild = $casCol;
        $this->table->casVerCol = $casCol;
    }

    public function enableSalt($salt = 'salt') {
        $this->enableSalt = true;
        if ($salt) {
            self::$saltFeild = $salt;
        }
    }

    public function setSaltLength($length) {
        if ($length % 2 !== 0) {
            $length = $length + 1;
        }
        self::$saltLength = $length;
    }

    public static function generateSalt() {
        return Kernel::randHex(self::$saltLength);
    }

    public function save() {
        $exp = '';
        if ($this->enableCas) {
            $this->newCasVer();
            $exp = $this->table->query()->col(self::$casFeild)->eq($this->casVer);
        }
        $this->record->save($exp);
    }

    protected function newCasVer() {
        if ($this->casVer) {
            $this->record[self::$casFeild] = $this->casVer + 1;
        } else {
            $this->record[self::$casFeild] = 1;
        }
    }

    public function setPassword($password) {
        if ($this->enableSalt) {
            $salt = $this->newSalt();
            $password .= hash('sha256', $salt);
        }
        $this->record[self::$passwordFeild] = self::generatePassword($password);
    }

    public function checkUserPassword($password) {
        if ($this->enableSalt) {
            $password .= hash('sha256', $this->record[self::$saltFeild]);
        }
        return self::passwordVerify($password, $this->record[self::$passwordFeild]);
    }

    public function changePassword($oldPassword, $newPassword) {
        if ($this->checkUserPassword($oldPassword)) {
            $this->setPassword($newPassword);
            return true;
        } else {
            return false;
        }
    }

    public function newSalt() {
        $this->record[self::$saltFeild] = self::generateSalt();
        return $this->record[self::$saltFeild];
    }

    public function __set($name, $value) {
        if ($name === $this->idFeild) {
            Kernel::runtimeException('can not set user id');
        } elseif ($name === $this->saltFeild) {
            Kernel::runtimeException('only use ' . __CLASS__ . '::newSalt() set salt');
        } else {
            if ($name === self::$passwordFeild) {
                $this->setPassword($value);
            } else {
                $this->record->$name = $value;
            }
        }
    }

    public function __get($name) {
        return $this->record->$name;
    }

    protected function find() {
        if (!$this->id) {
            return $this->record = $this->table->idler();
        }
        $this->record = $this->table->findOne($this->id);
        if ($this->record) {
            $this->checkSaltAndCas();
        }
    }

    protected function checkSaltAndCas() {
        if (isset($this->record[self::$casFeild])) {
            $this->enableCas = true;
            $this->casVer = $this->record[self::$casFeild];
        }
        if (isset($this->record[self::$saltFeild])) {
            $this->enableSalt;
        }
    }

    protected static function db() {
        return DB::instance(self::$dbkey);
    }

    protected function table($table) {
        $this->table = self::db()->table($table);
        $this->idFeild = $this->table->getKey();
        $this->properList = $this->table->getColumns();
    }

}
