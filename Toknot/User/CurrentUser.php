<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\User;

use Toknot\User\UserControl;
use Toknot\User\Root;
use Toknot\Exception\StandardException;

class CurrentUser extends UserControl {

    protected $sessionId = 0;
    public static $tableName = null;
    public static $uidColumn = null;
    public static $userNameColumn = null;
    public static $gidColumn = null;
    public static $passColumn = null;
    public static $DBConnect = null;

    const USER_EXISTS = 1;
    const USER_NOT_EXISTS = 2;
    const OPRATE_SUCC = 200;

    /**
     * create a user instance
     * <code>
     * use Toknot\User\CurrentUser;
     * class UserInfo {
     *     public function __construct($FMAI) {
     *         CurrentUser::$tableName = 'userTable';
     *         CurrentUser::$uidColumn = 'uid';
     *         CurrentUser::$userNameColumn = 'username';
     *         CurrentUser::$gidColumn = 'gid';
     *         $CFG = $FMAI->loadConfigure('/Config/config.ini');
     *         $AR = $FMAI->getActiveRecord();
     *         $AR->config($CFG->Database);
     *         CurrentUser::$DBConnect = $AR->connect();
     * 
     *         //Get url:http://domian/UserInfo/123 parameter, below $uid = 123
     *         $uid = $FMAI->getParam(0);  
     *         $user = new CurrentUser($uid);
     *     }
     * }
     * </code>
     * 
     * @param integer $id
     * @throws StandardException
     */
    protected function __construct($userinfo) {
        if (self::$DBConnect == null) {
            throw new StandardException('Must set Db connect instance');
        }
        if (self::$tableName == null || self::$uidColumn == null || self::$userNameColumn == null) {
            throw new StandardException('Must set user tablename and  uidColumn name, userNameColumn name of database');
        }
        $this->gid = unserialize($userinfo[self::$gidColumn]);
        $this->uid = $userinfo[self::$uidColumn];
        $this->userName = $userinfo[self::$userNameColumn];
    }
    public static function login($id, $password) {
        if(is_numeric($id) && $id ===0) {
            return Root::login($password);
        }
        $tableName = self::$tableName;
        $password = self::$passColumn;
        $username = is_numeric($id) ?  self::$uidColumn : self::$userNameColumn;
        self::$DBConnect->$tableName->$username = $id;
        self::$DBConnect->$tableName->$password = $password;
        $userInfo = self::$DBConnect->$tableName->findByAttr($passColumn);
        if(empty($userInfo)) {
            return false;
        } else {
            return new static($userInfo);
        }
    }

    public function suRoot($password = null) {
        return Root::su($this, $password);
    }

    public function getUserInfo($uid) {
        $tableName = self::$tableName;
        return self::$DBConnect->$tableName->findByPK($uid);
    }

    public static function addUser($data) {
        if ($data[self::$userNameColumn] == 'root') {
            return self::USER_EXISTS;
        }
        if (isset($data[self::$uidColumn]) && $data[self::$uidColumn] === 0) {
            return self::USER_EXISTS;
        }
        if(isset($data[self::$gidColumn]) && is_array($data[self::$gidColumn])) {
            $data[self::$gidColumn] = serialize($data[self::$gidColumn]);
        }
    }

}