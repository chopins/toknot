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

class ClassUserControl extends UserControl {
    protected $permissions;
    protected $classGroup;
    public function changeClassPermissions(Root $user, $perms) {
        if($user instanceof Root) {
            $this->permissions = $perms;
        }
    }
    public function changeClassGroup(Root $user, $group) {
        if($user instanceof Root) {
            $this->classGroup = $group;
        }
    }
}
