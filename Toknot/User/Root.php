<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\User;

use Toknot\User\UserClass;
use Toknot\Config\ConfigLoader;
use Toknot\Exception\BadPropertyGetException;

/**
 * Root User object
 */
final class Root extends UserAccessControl {

    private $userName = 'root';
    private $uid = 0;
    private static $password = null;
    private static $allowLogin = false;
    private $groupName = 'root';
    private $gid = 0;
    private $suUser = null;
    private $loginExpire = 0;

    private function __construct() {
        $this->suUser = 0;
        $this->uid = 0;
        $this->gid = 0;
        $this->userName = 'root';
        $this->groupName = 'root';
        $this->loginExpire = 0;
    }
    public function __get($name) {
        if($name == 'password') {
            throw new BadPropertyGetException(__CLASS__,$name);
        }
        return $this->$name;
    }

    private static function loadConfigure() {
        $cfg = ConfigLoader::CFG();
        if (!isset($cfg->User)) {
            self::$allowLogin = false;
            self::$password = null;
            self::$hashAlgo = 'sha1';
            return;
        }
        if (!isset($cfg->User->allowRootLogin)) {
            self::$allowLogin = false;
        } else {
            self::$allowLogin = $cfg->User->allowRootLogin;
        }
        if (!isset($cfg->User->rootPassword) || empty($cfg->User->rootPassword)) {
            self::$password = null;
        } else {
            self::$password = $cfg->User->rootPassword;
        }
        if (!isset($cfg->User->userPasswordEncriyptionAlgorithms)) {
            self::$hashAlgo = 'sha1';
        } else {
            self::$hashAlgo = $cfg->User->userPasswordEncriyptionAlgorithms;
        }
        if (!empty($cfg->User->enableUseHashFunction)) {
            self::$useHashFunction = $cfg->User->enableUseHashFunction;
        }
        if (!empty($cfg->User->userPasswordEncriyptionSalt)) {
            self::$hashSalt = $cfg->User->userPasswordEncriyptionSalt;
        }
    }

    /**
     * Change current user to root 
     * 
     * @param \Toknot\User\UserClass $user
     * @param string $password
     * @return boolean|\Toknot\User\Root
     */
    public static function su(UserClass $user, $password) {
        self::loadConfigure();
        if (self::$password === null && $password != self::$password) {
            return false;
        }
        if (self::$password !== null && self::hashPassword($password) != self::$password) {
            return false;
        }
        $rootObject = new static;
        $rootObject->suUser = $user;
        return $rootObject;
    }
    
    /**
     * Delete Root object, if su to root, will return su from user object
     * 
     * @return Toknot\User\UserClass
     */
    public function logout() {
        if($this->suUser instanceof UserClass) {
            $user = $this->suUser;
            unset($this);
            return $user;
        }
        unset($this);
    }

    /**
     * Login root user, if root not configure password will not allow login
     * 
     * @param string $password
     * @return boolean|\Toknot\User\Root
     */
    public static function login($password) {
        self::loadConfigure();
        if (!self::$allowLogin) {
            return false;
        }

        if (self::$password === null) {
            return false;
        }
        if (self::$password != self::hashPassword($password)) {
            return false;
        }
        return new static;
    }
    public function __wakeup() {
        self::loadConfigure();
    }

}