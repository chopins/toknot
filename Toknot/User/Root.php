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
use Toknot\Config\ConfigLoader;

final class Root extends UserControl {
    protected $userName = 'root';
    protected $uid = 0;
    private static $password = null;
    private static $allowLogin = false;
    private static $algorithms = null;
    protected $groupName = 'root';
    protected $gid = 0;
    private $suUser = false;
    private function __construct() {
        $this->suUser = 0;
        $this->uid = 0;
        $this->gid = 0;
        $this->userName = 'root';
        $this->groupName = 'root';
    }
    private static  function loadUserConfigure() {
        $cfg = ConfigLoader::CFG();
        if(!isset($cfg->User)) {
            self::$allowLogin = false;
            self::$password = null;
            self::$algorithms = 'sha1';
            return;
        }
        if(!isset($cfg->User->allowLogin)) {
            self::$allowLogin = false;
        } else {
             self::$allowLogin = $cfg->User->allowLogin;
        }
        if(!isset($cfg->User->rootPassword) || empty($cfg->User->rootPassword)) {
            self::$password = null;
        } else {
            self::$password = $cfg->User->rootPassword;
        }
        if(!isset($cfg->User->rootEncriyptionAlgorithms)) {
            self::$algorithms = 'sha1';
        } else {
            self::$algorithms = $cfg->User->rootEncriyptionAlgorithms;
        }
    }
    
    /**
     * use current configure encrypt a password
     * 
     * @param string $password
     * @return string
     */
    public static function hashPassword($password) {
        if(self::$algorithms != 'sha1') {
            return hash(self::$algorithms, $password);
        } else {
            return sha1($password);
        }
    }

    /**
     * Change current to root 
     * 
     * @param \Toknot\User\CurrentUser $user
     * @param string $password
     * @return boolean|\Toknot\User\Root
     */
    public static function su(CurrentUser $user, $password) {
        self::loadUserConfigure();
        if(self::$password === null && $password != self::$password) {
            return false;
        }
        if(self::$password !== null && self::hashPassword($password) != self::$password ) {
            return false;
        }
        $rootObject = new static;
        $rootObject->suUser = true;
        $rootObject->uid = $user->uid;
        $rootObject->userName  = $user->userName;
        return $rootObject;
    }
    
    /**
     * Login root user, if root not configure password will not allow login
     * 
     * @param string $password
     * @return boolean|\Toknot\User\Root
     */
    public static function login($password) {
        self::loadUserConfigure();
        if(!self::$allowLogin) {
            return false;
        }
        if(self::$password === null) {
            return false;
        }
        if(self::$password != self::hashPassword($password)) {
            return false;
        }
        return new static;
    }
    
    /**
     * Passed a password text generate a encryption sting for root password
     * 
     * @param string $password
     * @return array  The array 1st is algos name , 2 is encryption sting
     */
    public static function getEncryptionRootPass($password) {
        $algo = self::bestHashAlgos();
        if($algo) {
            return array($algo,hash($algo, $password));
        } else {
            return array('sha1', sha1($password));
        }
    }
}