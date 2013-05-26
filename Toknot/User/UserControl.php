<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @link       https://github.com/chopins/toknot
 */
namespace Toknot\User;
use Toknot\Di\Object;

abstract class UserControl extends Object {
    
    /**
     * user's account of name
     *
     * @var string
     */
    protected $userName = 'nobody';
    
    /**
     * ID of username
     *
     * @var integer 
     */
    protected $uid = '';
    
    /**
     * password of user account
     *
     * @var string 
     */
    protected $password = '';
    
    /**
     * ID of group
     *
     * @var mixed  May be array or string
     */
    protected $gid = '';
    
    /**
     * whether allow user login
     *
     * @var boolean
     */
    protected $allUserLogin = true;
    
    /**
     * whether enable admin group, if true and group id equal 1, the user will is admin
     *
     * @var boolean 
     */
    protected $enableAdmin = false;
    
    /**
     * whether allow change to root user
     *
     * @var boolean 
     */
    protected $allSu = false;
    /**
     * Get user Id number
     * 
     * @return integer
     */
    final public function getUid() {
        return $this->uid;
    }
    
    /**
     * Get group id number
     * 
     * @return type
     */
    final public function getGid() {
        return $this->gid;
    }
    
    /**
     * Get current login user of name
     * 
     * @return string
     */
    final public function getLogin() {
        return $this->userName;
    }
    
    /**
     * Get current group of user or class
     * 
     * @return string
     */
    final public function getGroup() {
        return $this->groupName;
    }
}