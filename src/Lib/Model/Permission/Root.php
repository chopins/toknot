<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2018 chopin xiao (xiao@toknot.com)
 */

namespace Toknot\Lib\Model\Permission;

final class Root extends Role {

    public function __construct($id = 0, $token = 'root', $permission = array()) {
        parent::__construct($id, $token, $permission);
    }

    public function getId() {
        return 0;
    }

    public function getToken() {
        return 'root';
    }

    public function hasPermission(Role $role, $action) {
        return true;
    }

    public function permission(Role $role, $action) {
        return false;
    }

}
