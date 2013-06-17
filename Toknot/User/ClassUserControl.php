<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\User;

use Toknot\User\UserAccessControl;
use Toknot\User\Root;
use Toknot\User\Nobody;
use Toknot\Exception\StandardException;

class ClassAccessControl extends UserAccessControl {

    /**
     * 8 bit permission of current object instance
     *
     * @var integer
     */
    protected $permissions = 0777;
    
    /**
     * current object type of opreate data
     *
     * @var integer
     */
    protected $classType = self::CLASS_READ;
    
    /**
     * only show data
     */
    const CLASS_READ = 1;
    
    /**
     * only add data
     */
    const CLASS_WRITE = 2;
    
    /**
     * only change data of current exists
     */
    const CLASS_UPDATE = 3;
    
    public function getClassType() {
        return $this->classType;
    }

     /**
     * Use Root user change class of permission with is temp
     * 
     * @param \Toknot\User\Root $user
     * @param integer $perms
     */
    public function changeClassPermissions(Root $user, $perms) {
        $this->permissions = $perms;
    }

    /**
     * Use Root user change class of group with is temp
     * 
     * @param \Toknot\User\Root $user
     * @param string $group
     */
    public function changeClassGroup(Root $user, $group) {
        $this->classGroup = $group;
    }

    private function checkPermes($user, $perm) {
        if (!($user instanceof UserAccessControl)) {
            throw new StandardException('Undefined user type');
        }
        if ($user instanceof Root) {
            return true;
        }
        
        if (($this->permissions ^ 0770) > $perm) {
            return true;
        }
        if ($user instanceof Nobody) {
            return false;
        }
        if ($user->inGroup($this->gid) && $this->permissions ^ 0707 >> 3 > $perm) {
            return true;
        }
        if ($this->uid == $user->getUid() && $this->permissions ^ 0077 >> 6 > $perm) {
            return true;
        }
        return false;
    }

    /**
     * Check current use whether can access the GET method of invoke class
     * 
     * @param \Toknot\User\UserClass $user
     * @return boolean
     */
    public function checkRead($user) {
        return $this->checkPermes($user, 04);
    }

    /**
     * Check current use whether can access the POST method of invoke class
     * 
     * @param \Toknot\User\UserClass $user
     * @return boolean
     */
    public function checkWrite($user) {
        return $this->checkPermes($user, 06);
    }

    /**
     * Check current use whether change current class data
     *  
     * @param \Toknot\User\UserClass $user
     * @return boolean
     */
    public function checkChange($user) {
        return $this->checkPermes($user, 07);
    }

}
