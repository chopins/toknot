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

final class Root extends UserControl {
    protected $userName = 'root';
    protected $uid = 0;
    private static $password = null;
    private static $allowLogin = false;
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
    public static function setRootPassword($password) {
        self::$password = $password;
    }
    
    public static function allowRootLogin() {
        self::$allowLogin = true;
    }
    
    public static function su(CurrentUser $user, $password) {
        if($password != self::$password) {
            return false;
        }
        $rootObject = new static;
        $rootObject->suUser = true;
        $rootObject->uid = $user->uid;
        $rootObject->userName  = $user->userName;
        return $rootObject;
    }
    public static function login($password) {
        if(!self::$allowLogin) {
            return false;
        }
        if(!self::$password == null) {
            return false;
        }
        if(self::$password != $password) {
            return false;
        }
        return new static;
    }
    
}