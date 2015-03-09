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
use Toknot\Lib\User\Nobody;
use Toknot\Lib\User\Exception\NoPermissionExecption;
use Toknot\Lib\User\Exception\UndefinedUserExecption;

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
    protected $operateType = null;

    /**
     * only show data
     */

    const CLASS_READ = 'r';

    /**
     * only add data
     */
    const CLASS_WRITE = 'w';

    /**
     * only change data of current exists
     */
    const CLASS_UPDATE = 'u';

    final public function getOperateType() {
        if($this->operateType == null) {
            $this->operateType = empty($_POST) ? self::CLASS_READ : self::CLASS_WRITE;
        }
        return $this->operateType;
    }

    public function updateMethodPerms($methodName) {
        $const = 'self::'.strtoupper($methodName);
        $this->parsePermissionString($const);
        if (!defined($const)) {
            $this->operateType = empty($_POST) ? self::CLASS_READ : self::CLASS_WRITE;
        }
    }

    final public function setOperateType(UserAccessControl $user, $operate) {
        if (!$user instanceof Root && $user->uid != $this->uid) {
            throw new NoPermissionExecption('no permission to set operate type');
        }
        
        $opStr = 'rwu';
        $idx = strpos($opStr, strtolower($operate));
        if ($idx >= 0) {
            $this->operateType = $operate;
        } else {
            $this->operateType = self::CLASS_READ;
        }
    }

    /**
     * Use Root user change class of permission with is temp
     * 
     * @param \Toknot\Lib\User\Root $user
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
     * @param \Toknot\Lib\User\Root $user
     * @param string $group
     */
    final public function changeClassGroup(UserAccessControl $user, $group) {
        if ($user instanceof Root || $user->uid == $this->uid) {
            $this->classGroup = $group;
        } else {
            throw new NoPermissionExecption('no permission to set class group');
        }
    }
    final public function parsePermissionString($const) {
        if(defined($const)) {
            $permissions = explode(',',constant($const));
            foreach($permissions as $item) {
                list($k,$v) = explode(':', $item);
                $k = strtolower($k);
                switch ($k) {
                    case 'M':
                        $this->permissions = $v;
                        break;
                    case 'P':
                        $this->operateType = $v;
                        break;
                    case 'G':
                        $this->gid = (int)$v;
                        break;
                    case 'U':
                        $this->uid = (int)$v;
                        break;
                }
            }
        }
    }
    final public function checkClassAccess() {
        $const = 'self::'.strtoupper(get_called_class());
        $this->parsePermissionString($const);
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
     * @param \Toknot\Lib\User\UserClass $user
     * @return boolean
     */
    final public function checkRead($user) {
        return $this->checkPerms($user, 04);
    }

    /**
     * Check current use whether can access the POST method of invoke class
     * 
     * @param \Toknot\Lib\User\UserClass $user
     * @return boolean
     */
    final public function checkWrite($user) {
        return $this->checkPerms($user, 06);
    }

    /**
     * Check current use whether change current class data
     *  
     * @param \Toknot\Lib\User\UserClass $user
     * @return boolean
     */
    final public function checkChange($user) {
        return $this->checkPerms($user, 07);
    }

    final public function __toString() {
        return get_called_class();
    }

}
