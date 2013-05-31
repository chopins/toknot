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

    /**
     * current user token id
     *
     * @var string
     * @access protected
     */
    protected $sessionId = 0;

    /**
     * Table name of the user-account table in database
     *
     * @var string
     * @access public
     * @static
     */
    public static $tableName = null;

    /**
     * Column name of the UID field in the user-account table on database
     *
     * @var string
     * @access public
     * @static 
     */
    public static $uidColumn = null;

    /**
     * Column name of the user-account field in the user-account table on database
     *
     * @var string
     * @access public
     * @static
     */
    public static $userNameColumn = null;

    /**
     * column name of the user-account group id field in the user-account table on database
     *
     * @var string
     * @access public
     * @static
     */
    public static $gidColumn = null;

    /**
     * column name of the user-account password field in the user-account table on database
     *
     * @var string
     * @access public
     * @static
     */
    public static $passColumn = null;

    /**
     * the database connect object of the user-account table in daabase
     * 
     * <code>
     * $AR  = $this->FMAI->getActiveRecord();
     * CurrentUser::$DBConnect = $AR->connect();s
     * </code>
     *
     * @var Toknot\Db\DatabaseObject
     * @access public
     * @static 
     */
    public static $DBConnect = null;

    /**
     * name of algorithm for the hash function
     *
     * @var string
     */
    public static $hashAlgo = 'sha512';

    /**
     * whether use hash extension of function
     *
     * @var boolean
     */
    public static $useHashFunction = true;

    /**
     * set salt of the hash 
     *
     * @var string
     */
    public static $hashSalt = '';

    /**
     * User exists status code
     */

    const USER_EXISTS = 1;

    /**
     * user not exists status code
     */
    const USER_NOT_EXISTS = 2;

    /**
     * opreate of user success status code
     */
    const OPRATE_SUCC = 200;

    /**
     * opreate of user fail status code
     */
    const OPRATE_FAIL = 500;

    /**
     * The password salt string
     */
    const PASSWORD_SALT = 'ToKnot-PHP-Framework-Password-Default-Salt';

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
     * @access protected
     * @throws Toknot\Exception\StandardException
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
        $this->generateSessionId();
    }

    /**
     * find a best hash algos from current PHP hash algorithms list
     * 
     * @return boolean|string  If have sha and tiger algorithm, will return max bit algo otherise return false
     * @access public
     * @static
     */
    public static function bestHashAlgos() {
        if (!function_exists('hash_algos')) {
            return false;
        }
        $algoList = array_reverse(hash_algos());
        foreach ($algoList as $algo) {
            if (strlen($algo) < 5) {
                continue;
            }
            if (substr($algo, 0, 3) == 'sha') {
                return $algo;
            }
            if (substr($algo, 0, 5) == 'tiger') {
                return $algo;
            }
        }
        return false;
    }

    /**
     * Generate a seesion id that is hash string
     */
    protected function generateSessionId() {
        $seed = md5($this->userName . $this->uid . $this->gid);
        $str = str_shuffle(microtime() . $seed . mt_rand(100000, 9999999));
        $algo = self::bestHashAlgos();
        if ($algo) {
            $this->sessionId = hash($algo, $str);
        } else {
            $this->sessionId = sha1($str);
        }
    }

    /**
     * get session id of the CurrentUser object
     * 
     * @return string
     */
    public function getSessionId() {
        return $this->sessionId;
    }

    /**
     * username and password be used for user login
     * 
     * @static
     * @access public
     * @param type $id  The account of user
     * @param type $password    The account of password
     * @return boolean|Toknot\User\CurrentUser
     */
    public static function login($id, $password) {
        if (is_numeric($id) && $id === 0) {
            return Root::login($password);
        }
        $tableName = self::$tableName;
        $passwordColumn = self::$passColumn;
        $username = self::$userNameColumn;
        self::$DBConnect->$tableName->$username = $id;
        self::$DBConnect->$tableName->$password = $password;
        $userInfo = self::$DBConnect->$tableName->findByAttr($passwordColumn);
        if (empty($userInfo)) {
            return false;
        } else {
            return new static($userInfo);
        }
    }

    /**
     * change current user to root user and have super permissions
     * 
     * @param string $password  The Root user password
     * @return boolean|Toknot\User\Root
     * @access public
     */
    public function suRoot($password = null) {
        return Root::su($this, $password);
    }

    /**
     * Get user info of CurrentObject instance
     * 
     * @return array
     */
    public function getUserInfo() {
        $tableName = self::$tableName;
        return self::$DBConnect->$tableName->findByPK($this->uid);
    }

    /**
     * change password of user
     * 
     * @param string $oldPassword user current password
     * @param string $newPassword   set new password
     * @return integer
     */
    public function changePassword($oldPassword, $newPassword) {
        $oldPasswordHash = self::hashPassword($oldPassword);
        $newPasswordHash = self::hashPassword($newPassword);
        $userInfo = $this->getUserInfo();
        $passColumn = self::$passColumn;
        if ($userInfo[$passColumn] == $oldPasswordHash) {
            $tableName = self::$tableName;
            self::$DBConnect->$tableName->$passColumn = $newPasswordHash;
            $row = self::$DBConnect->$tableName->updateByPk($this->uid);
            if ($row) {
                return self::OPRATE_SUCC;
            } else {
                return self::OPRATE_FAIL;
            }
        }
    }

    public function addGroup($gid) {
        if(!is_array($this->gid) && $this->gid != $gid) {
            $this->gid = array($this->gid, $gid);
        } else {
            $key = array_search($gid, $this->gid);
            if($key === false) {
                $this->gid[] = $gid;
            } else {
                return self::OPRATE_SUCC;
            }
        }
        $tableName = self::$tableName;
        $gidColumn = self::$gidColumn;
        self::$DBConnect->$tableName->$gidColumn = serialize($this->gid);
        $row = self::$DBConnect->$tableName->updateByPk($this->uid);
        return $row ? self::OPRATE_SUCC : self::OPRATE_FAIL;
    }

    public function deleteGroup($gid) {
        if(!is_array($this->gid) || count($this->gid) == 1) {
            return self::OPRATE_FAIL;
        }
        $key = array_search($gid, $this->gid);
        if($key === false) {
            return self::OPRATE_FAIL;
        }
        unset($this->gid[$key]);
        $tableName = self::$tableName;
        $gidColumn = self::$gidColumn;
        self::$DBConnect->$tableName->$gidColumn = serialize($this->gid);
        $row = self::$DBConnect->$tableName->updateByPk($this->uid);
        return $row ? self::OPRATE_SUCC : self::OPRATE_FAIL;
    }

    public function deleteUser() {
        $tableName = self::$tableName;
        $row = self::$DBConnect->$tableName->deleteByPk($this->uid);
        if ($row) {
            return self::OPRATE_SUCC;
        } else {
            return self::OPRATE_FAIL;
        }
    }

    /**
     * add user by user info data, the key name of data is same the user table of column name
     * 
     * @param array $data
     * @return Toknot\User\CurrentUser|boolean
     * @access public
     * @static
     */
    public static function addUser(array $data) {
        if ($data[self::$userNameColumn] == 'root') {
            return self::USER_EXISTS;
        }
        if (isset($data[self::$uidColumn]) && $data[self::$uidColumn] === 0) {
            return self::USER_EXISTS;
        }
        if (isset($data[self::$gidColumn]) && is_array($data[self::$gidColumn])) {
            $data[self::$gidColumn] = serialize($data[self::$gidColumn]);
        }
        if (isset($data[self::$passColumn])) {
            $data[self::$passColumn] = self::hashPassword($data[self::$passColumn]);
        }
        $tabname = self::$tableName;
        self::$DBConnect->$tabname->import($data);
        $re = self::$DBConnect->$tabname->save();
        if ($re) {
            return new static($data);
        } else {
            return false;
        }
    }

    /**
     * Get password string of hash value, the method use {@see CurrentUser::$hashSalt} static property
     * for the password of hash salt, if not set {@see CurrentUser::$hashSalt} will use {@see CurrentUser::PASSWORD_SALT}
     * if set {@see CurrentUser::$useHashFunction} is true will use {@see CurrentUser::$hashAlgo} for 
     * the hash function algorithm
     * 
     * @param string $password
     * @return string
     * @access public
     * @static
     * @throws Toknot\Exception\StandardException if set use hash function and hash function not exists
     */
    public static function hashPassword($password) {
        if (self::$useHashFunction && !function_exists('hash') && !in_array(self::$hashAlgo, hash_algos())) {
            throw new StandardException('need hash extension or ' . self::$hashAlgo . ' algo un-support');
        }
        $salt = $salt ? self::$hashSalt : self::PASSWORD_SALT;
        if (self::$useHashFunction) {
            return hash(self::$hashAlgo, $password . $salt);
        } else {
            return sha1($password . $salt);
        }
    }

}