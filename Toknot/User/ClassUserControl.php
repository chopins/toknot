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

class ClassUserControl extends UserControl {

    /**
     * 8 bit permission of current object instance
     *
     * @var integer
     */
    protected $permissions = 0777;

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
        if (!($user instanceof UserControl)) {
            throw new StandardException('Undefined user type');
        }
        if ($user instanceof Root) {
            return true;
        }
        if ($this->permissions ^ 0770 > $perm) {
            return true;
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
     * @param \Toknot\User\CurrentUser $user
     * @return boolean
     */
    public function checkRead($user) {
        return $this->checkPermes($user, 04);
    }

    /**
     * Check current use whether can access the POST method of invoke class
     * 
     * @param \Toknot\User\CurrentUser $user
     * @return boolean
     */
    public function checkWrite($user) {
        return $this->checkPermes($user, 06);
    }

    /**
     * Check current use whether change current class data
     *  
     * @param \Toknot\User\CurrentUser $user
     * @return boolean
     */
    public function checkChange($user) {
        return $this->checkPermes($user, 07);
    }

}
