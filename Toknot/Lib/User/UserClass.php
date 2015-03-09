<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Lib\User;

use Toknot\Lib\User\UserAccessControl;
use Toknot\Lib\User\Root;
use Toknot\Exception\BaseException;
use Toknot\Config\ConfigLoader;

class UserClass extends UserAccessControl {

	/**
	 * user password
	 *
	 * @var string
	 */
	private $password = '';

	/**
	 * current login active expire time, set 0 will depends SESSION or Cookie set
	 *
	 * @var integer
	 */
	public $loginExpire = 0;
	private $loginTime = 0;

	/**
	 * Table name of the user-account table in database
	 *
	 * @var string
	 * @access public
	 * @static
	 */
	private static $tableName = null;

	/**
	 * Column name of the UID field in the user-account table on database
	 *
	 * @var string
	 * @access public
	 * @static 
	 */
	private static $uidColumn = null;

	/**
	 * Column name of the user-account field in the user-account table on database
	 *
	 * @var string
	 * @access public
	 * @static
	 */
	private static $userNameColumn = null;

	/**
	 * column name of the user-account group id field in the user-account table on database
	 *
	 * @var string
	 * @access public
	 * @static
	 */
	private static $gidColumn = null;

	/**
	 * column name of the user-account password field in the user-account table on database
	 *
	 * @var string
	 * @access public
	 * @static
	 */
	private static $passColumn = null;

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
	 * All number
	 */
	const PASSWD_ALL_NUMBER = 9000;

	/**
	 * natural order number
	 */
	const PASSWD_NUMBER_SORT = 9001;

	/**
	 * All letter
	 */
	const PASSWD_ALL_ABC = 9100;

	/**
	 * All lower letter
	 */
	const PASSWD_ALL_LOWER = 9101;

	/**
	 * All upper letter
	 */
	const PASSWD_ALL_UPPER = 9102;

	/**
	 * natural order letter
	 */
	const PASSWD_ABC_SORT = 9103;

	/**
	 * same keyboard letter order
	 */
	const PASSWD_ABC_KEYBOARD_SORT = 9104;

	/**
	 * password length less 6
	 */
	const PASSWD_SHORT6 = 9200;

	/**
	 * the string is orderless
	 */
	const PASSWD_ORDERLESS = 9300;

	/**
	 * create a user instance
	 * <code>
	 * use Toknot\Lib\User\CurrentUser;
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
	 * @param array $userinfo
	 * @access protected
	 * @throws Toknot\Exception\BaseException
	 */
	protected function __init($userinfo) {
		if (self::$DBConnect == null) {
			throw new BaseException('Must set Db connect instance');
		}
		$this->loginTime = time();
		$this->uid = $userinfo[self::$uidColumn];
		$this->userName = $userinfo[self::$userNameColumn];
		if (empty(self::$gidColumn)) {
			$this->gid = $this->uid;
		} else {
			$this->gid = unserialize($userinfo[self::$gidColumn]);
		}
		$this->password = $userinfo[self::$passColumn];
		$this->generateUserFlag();
	}

	public function getActiveStatus() {
		if ($this->loginExpire == 0) {
			return true;
		} elseif ($this->loginTime + $this->loginExpire > time()) {
			return true;
		} else {
			return false;
		}
	}

	public function generateLoginKey() {
		$userInfoHash = self::getUserKey($this->userName, $this->password);
		$userSidHash = self::getSidKey($this->uid, $this->userFlag);
		return self::hash($userInfoHash . $userSidHash);
	}

	public static function getSidKey($uid, $sessionid) {
		return self::hash($uid . $sessionid);
	}

	public static function getUserKey($username, $password) {
		return self::hash($username . $password);
	}

	public static function checkLogin($uid, $flag, $loginKey) {
		$sidKey = self::getSidKey($uid, $flag);
		$user = self::getInstanceByUid($uid);
		if (empty($user)) {
			return false;
		}
		if (!$user->checkUserFlag()) {
			return false;
		}
		$userKey = self::getUserKey($user[self::$userNameColumn], $user[self::$passColumn]);
		$checkKey = self::hash($userKey . $sidKey);
		if ($checkKey == $loginKey) {
			return $user;
		} else {
			return false;
		}
	}

	/**
	 * username and password be used for user login
	 * 
	 * @static
	 * @access public
	 * @param string $userName  The account of user
	 * @param string $password    The account of password
	 * @return boolean|Toknot\Lib\User\UserClass
	 */
	public static function login($userName, $password) {
		if ($userName == 'root' || $userName === 0) {
			return Root::login($password);
		}
		self::loadConfigure();
		$tableName = self::$tableName;
		$passwordColumn = self::$passColumn;
		$username = self::$userNameColumn;
		self::$DBConnect->$tableName->$username = $userName;
		self::$DBConnect->$tableName->$passwordColumn = self::hashPassword($password);
		$userInfo = self::$DBConnect->$tableName->findByAttr(1);
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
	 * @return boolean|Toknot\Lib\User\Root
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
	 * Set login expire time, if $time is number, login expire time is current time 
	 * plus the passed time, if number is followed a letter are recognized in the $time
	 * parameter string:
	 *      d   Number of the days after the current time, e.g  7d for 7*24*3600 seconds
	 *      h   Number of the hours after the current time, e.g 24h
	 *      m   Number of the months after the current time, 30 days of a month
	 *      w   Number of the weeks after the current time, e.g 2w for 2*7*24*3600 seconds
	 *      y   Number of the years after the current time, 365 days of a year
	 * other string be passed will use {@see strtotime} get time for the expire time
	 *      
	 * @param string $time 
	 * @return boolean
	 */
	public function setLoginExpire($time) {
		$currentTime = time();
		if (is_numeric($time)) {
			$this->loginExpire = $currentTime + $time;
			return true;
		}
		$last = strtolower(substr($time, -1, 1));
		$timeNumber = substr($time, 0, -1);
		if (!is_numeric($timeNumber)) {
			$t = strtotime($time, $currentTime);
			if ($t) {
				$this->loginExpire = $t;
				return true;
			}
			return false;
		}
		switch ($last) {
			case 'd':
				$this->loginExpire = $timeNumber * 86400;
				return true;
			case 'h':
				$this->loginExpire = $timeNumber * 3600;
				return true;
			case 'm':
				$this->loginExpire = $timeNumber * 2592000;
				return true;
			case 'w':
				$this->loginExpire = $timeNumber * 604800;
				return true;
			case 'y':
				$this->loginExpire = $timeNumber * 31536000;
				return true;
			default :
		}
	}

	/**
	 * change password of user
	 * 
	 * @param string $oldPassword user current password
	 * @param string $newPassword   set new password
	 * @return integer  opreate status use {@see CurrentUser::OPRATE_SUCC} 
	 *                   and {@see CurrentUser::OPRATE_FAIL}
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

	/**
	 * Add a group to current user 
	 * 
	 * @param integer $gid
	 * @return integer opreate status use {@see CurrentUser::OPRATE_SUCC} 
	 *                  and {@see CurrentUser::OPRATE_FAIL}
	 */
	public function addGroup($gid) {
		if (!is_array($this->gid) && $this->gid != $gid) {
			$this->gid = array($this->gid, $gid);
		} else {
			$key = array_search($gid, $this->gid);
			if ($key === false) {
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

	/**
	 * delete a group from current of group list
	 * 
	 * @param integer $gid
	 * @return integer
	 */
	public function deleteGroup($gid) {
		if (!is_array($this->gid) || count($this->gid) == 1) {
			return self::OPRATE_FAIL;
		}
		$key = array_search($gid, $this->gid);
		if ($key === false) {
			return self::OPRATE_FAIL;
		}
		unset($this->gid[$key]);
		$tableName = self::$tableName;
		$gidColumn = self::$gidColumn;
		self::$DBConnect->$tableName->$gidColumn = serialize($this->gid);
		$row = self::$DBConnect->$tableName->updateByPk($this->uid);
		return $row ? self::OPRATE_SUCC : self::OPRATE_FAIL;
	}

	/**
	 * Delete current user
	 * 
	 * @return integer opreate status use {@see CurrentUser::OPRATE_SUCC} 
	 *                   and {@see CurrentUser::OPRATE_FAIL}
	 */
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
	 * add user by user info data, the key name of data is same the user table 
	 * of column name
	 * 
	 * @param array $data
	 * @return Toknot\Lib\User\UserClass|boolean
	 * @access public
	 * @static
	 */
	public static function addUser(array $data) {
		self::loadConfigure();
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
	 * get password sting texture
	 * 
	 * @param string $password
	 * @return integer value is {@see CurrentUser::PASSWD_ALL_NUMBER},
	 * 					{@see CurrentUser::PASSWD_SHORT6}
	 *                  {@see CurrentUser::PASSWD_NUMBER_SORT},
	 * 					{@see CurrentUser::PASSWD_ABC_SORT},
	 *                  {@see CurrentUser::PASSWD_ABC_KEYBOARD_SORT},
	 * 					{@see CurrentUser::PASSWD_ALL_LOWER},
	 *                  {@see CurrentUser::PASSWD_ALL_UPPER},
	 * 					{@see CurrentUser::PASSWD_ALL_ABC}
	 *                  {@see CurrentUser::PASSWD_ORDERLESS}
	 */
	public static function getPasswordTexture($password) {
		$len = strlen($password);
		if ($len < 6) {
			return self::PASSWD_SHORT6;
		}
		$strArr = str_split($password);
		natsort($strArr);
		$natstr = implode('', $strArr);
		if (is_numeric($password)) {
			if ($natstr == $password || $password == strrev($natstr)) {
				return self::PASSWD_NUMBER_SORT;
			}
			return self::PASSWD_ALL_NUMBER;
		}
		if ($natstr == $password || $password == strrev($natstr)) {
			return self::PASSWD_ABC_SORT;
		}
		$keyboardChar = array('qwertyuiop', 'asdfghjkl', 'zxcvbnm');
		foreach ($keyboardChar as $wordList) {
			if (strpos($password, $wordList) !== false) {
				return self::PASSWD_ABC_KEYBOARD_SORT;
			}
		}
		if (preg_match('/[a-z]/', $password)) {
			return self::PASSWD_ALL_LOWER;
		}
		if (preg_match('/[A-Z]/', $password)) {
			return self::PASSWD_ALL_UPPER;
		}
		if (preg_match('/[A-Za-z]/i', $password)) {
			return self::PASSWD_ALL_ABC;
		}
		return self::PASSWD_ORDERLESS;
	}

	/**
	 * Get a CurrentUser object by uid, recommended ser serialize() the user object
	 * 
	 * @param integer $uid
	 * @return Toknot\Lib\User\UserClass
	 * @static
	 */
	public static function getInstanceByUid($uid) {
		if (!is_numeric($uid)) {
			return false;
		}
		if ($uid === -1) {
			return new Nobody;
		}
		if ($uid === 0) {
			return false;
		}
		self::loadConfigure();
		$tableName = self::$tableName;
		$userInfo = self::$DBConnect->$tableName->findByPK($uid);
		return new static($userInfo);
	}

	private static function loadConfigure() {
		$cfg = ConfigLoader::CFG();
		if (!isset($cfg->User)) {
			throw new BaseException('Must add User section in configure');
		}
		if (empty($cfg->User->userTableName)) {
			throw new BaseException('Must set userTabelName in User section of configure');
		}
		self::$tableName = $cfg->User->userTableName;
		if (empty($cfg->User->userIdColumnName)) {
			throw new BaseException('Must set userIdColumnName in User section of configure');
		} else {
			self::$uidColumn = $cfg->User->userIdColumnName;
		}
		if (empty($cfg->User->userNameColumnName)) {
			throw new BaseException('Must set userNameColumnName in User section of configure');
		} else {
			self::$userNameColumn = $cfg->User->userNameColumnName;
		}
		if (empty($cfg->User->userPasswordColumnName)) {
			throw new BaseException('Must set userPasswordColumnName in User section of configure');
		} else {
			self::$passColumn = $cfg->User->userPasswordColumnName;
		}
		if (!empty($cfg->User->userGroupIdColumnName)) {
			self::$gidColumn = $cfg->User->userGroupIdColumnName;
		}
		if (!empty($cfg->User->userPasswordEncriyptionAlgorithms)) {
			self::$hashAlgo = $cfg->User->userPasswordEncriyptionAlgorithms;
		}
		if (!empty($cfg->User->enableUseHashFunction)) {
			self::$useHashFunction = $cfg->User->enableUseHashFunction;
		}
		if (!empty($cfg->User->userPasswordEncriyptionSalt)) {
			self::$hashSalt = $cfg->User->userPasswordEncriyptionSalt;
		}
	}

	public function __sleep() {
		return array('uid');
	}

	public function __wakeup() {
		return self::getInstanceByUid($this->uid);
	}

	/**
	 * Delete current user object instance
	 */
	public function logout() {
		unset($this);
	}
}