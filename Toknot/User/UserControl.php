<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @link       https://github.com/chopins/toknot
 */
namespace Toknot\User;

abstract class UserControl {
    
    /**
     * user's account of name
     *
     * @var string
     */
    protected $userName = '';
    
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
     * group of user
     *
     * @var string 
     */
    protected $groupName = '';
    
    /**
     * ID of group
     *
     * @var integer
     */
    protected $gid = '';
    
    /**
     * whether allow user login
     *
     * @var boolean
     */
    protected $login = true;
    
    /**
     * whether enable admin group, if true and group id equal 1, the user will is admin
     *
     * @var type 
     */
    protected $enableAdmin = false;
    
    public function getUid() {
        return $this->uid;
    }
    public function getGid() {
        return $this->gid;
    }
    public function getLogin() {
        return $this->userName;
    }
    public function getGroup() {
        return $this->groupName;
    }
}