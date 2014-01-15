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
use Toknot\User\Exception\NoPermissionExecption;
use Toknot\User\Exception\UndefinedUserExecption;

abstract class ClassAccessControl extends UserAccessControl {

    /**
     * 8 bit permission of current object instance
     * owner get post update
     * grouper get post
     * nobody  get
     *
     * @var integer
     * @access protected
     */
    protected $permissions = 0754;

    /**
     * Name of the class owner
     * 
     * @var string
     * @access protected
     */
    protected $userName = 'root';

    /**
     * Group id of the class
     *
     * @var integer 
     */
    protected $gid = 0;

    /**
     * Group name of the class
     *
     * @var string
     */
    protected $group = 'root';

    /**
     * uid of the class owner
     *
     * @var integer
     */
    protected $uid = 0;

    /**
     * current object type of opreate data
     *
     * @var integer
     */
    protected $operateType = self::CLASS_READ;

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

    final public function getOperateType() {
        return $this->operateType;
    }

    public function updateMethodPerms($methodName) {
        $methodName = "{$methodName}Perms";
        if (!empty($this->$methodName) && is_array($this->$methodName)) {
            foreach ($this->$methodName as $k => $v) {
                switch ($k) {
                    case 'opType':
                        $this->setOperateType($this, $v);
                        break;
                    case 'permissions':
                        $this->permissions = $v;
                        break;
                    case 'gid':
                        $this->gid = $v;
                        break;
                    case 'uid':
                        $this->uid = $v;
                        break;
                }
            }
        }
    }

    final public function setOperateType(UserAccessControl $user, $operate) {
        if (!$user instanceof Root && $user->uid != $this->uid) {
            throw new NoPermissionExecption('no permission to set operate type');
        }
        if (is_numeric($operate)) {
            if ($operate >= 1 && $operate <= 3) {
                $this->operateType = $operate;
            } else {
                $this->operateType = self::CLASS_READ;
            }
            return;
        }
        $opStr = 'rwu';
        $idx = strpos($opStr, strtolower($operate));
        if ($idx >= 0) {
            $this->operateType = $idx + 1;
        } else {
            $this->operateType = self::CLASS_READ;
        }
    }

    /**
     * Use Root user change class of permission with is temp
     * 
     * @param \Toknot\User\Root $user
     * @param integer $perms
     */
    final public function changeClassPermissions(UserAccessControl $user, $perms) {
        if ($user instanceof Root || $user->uid == $this->uid) {
            $this->permissions = $perms;
        } else {
            throw new NoPermissionExecption('no permission to set class permisson');
        }
    }

    /**
     * Use Root user change class of group with is temp
     * 
     * @param \Toknot\User\Root $user
     * @param string $group
     */
    final public function changeClassGroup(UserAccessControl $user, $group) {
        if ($user instanceof Root || $user->uid == $this->uid) {
            $this->classGroup = $group;
        } else {
            throw new NoPermissionExecption('no permission to set class group');
        }
    }

    final private function checkPerms($user, $perm) {
        if (!($user instanceof UserAccessControl)) {
            throw new UndefinedUserExecption();
        }
        if ($user instanceof Root) {
            return true;
        }
        if (($this->permissions > 0770 && $this->permissions ^ 0770) >= $perm) {
            return true;
        }
        if ($user instanceof Nobody) {
            return false;
        }
        if ($user->inGroup($this->gid) && $this->permissions > 0707 && ($this->permissions ^ 0707) >> 3 >= $perm) {
            return true;
        }
        if ($this->uid == $user->getUid() && ($this->permissions ^ 0077) >> 6 >= $perm) {
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
    final public function checkRead($user) {
        return $this->checkPerms($user, 04);
    }

    /**
     * Check current use whether can access the POST method of invoke class
     * 
     * @param \Toknot\User\UserClass $user
     * @return boolean
     */
    final public function checkWrite($user) {
        return $this->checkPerms($user, 06);
    }

    /**
     * Check current use whether change current class data
     *  
     * @param \Toknot\User\UserClass $user
     * @return boolean
     */
    final public function checkChange($user) {
        return $this->checkPerms($user, 07);
    }

    final public function __toString() {
        return get_called_class();
    }

}
