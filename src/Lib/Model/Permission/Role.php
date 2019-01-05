<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2018 chopin xiao (xiao@toknot.com)
 */

namespace Toknot\Lib\Model\Permission;

use Toknot\Lib\Model\Permission\Permission;
use Toknot\Boot\Kernel;
use Toknot\Boot\TKObject;

abstract class Role extends TKObject {

    protected $id = 0;
    protected $token = Kernel::NOP;
    protected $selfUnlimit = false;
    protected $selfView = 0;
    protected $selfUpdate = 0;
    protected $selfCreate = 0;
    protected $selfDelete = 0;
    protected $holdView = 0;
    protected $holdUpdate = 0;
    protected $holdCreate = 0;
    protected $holdDelete = 0;

    const UNLIMIT = 'Unlimit';
    const P_S = 'self';
    const P_H = 'hold';
    const VIEW = 'View';
    const UPDATE = 'Update';
    const CREATE = 'Create';
    const DELETE = 'Delete';
    const S_UNLIMIT = 0;
    const S_VIEW = 1;
    const S_UPDATE = 2;
    const S_CREATE = 3;
    const S_DELETE = 4;
    const H_VIEW = 5;
    const H_UPDATE = 6;
    const H_CREATE = 7;
    const H_DELETE = 8;
    const A_VIEW = 1;
    const A_UPDATE = 2;
    const A_CREATE = 3;
    const A_DELETE = 4;
    const PERM_MAP = [0 => self::P_S . self::UNLIMIT, 1 => self::P_S . self::VIEW,
        2 => self::P_S . self::UPDATE, 3 => self::P_S . self::CREATE, 4 => self::P_S . self::DELETE,
        5 => self::P_H . self::VIEW, 6 => self::P_H . self::UPDATE,
        7 => self::P_H . self::CREATE, 8 => self::P_H . self::DELETE,];

    abstract public static function addRole($data);

    public function toArray() {
        return ['id' => $this->id, 'token' => $this->token, 'perm' => $this->allPermission()];
    }

    public function getId() {
        return $this->id;
    }

    public function getToken() {
        return $this->token;
    }

    public function push(&$allRole) {
        $allRole[$this->id] = $this;
    }

    public function allPermission() {
        $perm = [];
        foreach (self::PERM_MAP as $id => $name) {
            $perm[$id] = $this->$name;
        }
        return $perm;
    }

    public function setUnlimit() {
        $this->selfUnlimit = true;
    }

    public function add(Role $role, $action) {
        if ($role->isUnlmit()) {
            return true;
        }
        $key = '';
        $proper = $this->accessProper($action, null, $key);
        $this->$key = Permission::set($proper, $role->getPermission($action));
        return $this->$key;
    }

    public function remove(Role $role, $action) {
        $key = '';
        $proper = $this->accessProper($action, null, $key);
        $this->$key = Permission::remove($proper, $role->getPermission($action));
        return $this->$key;
    }

    public function permission(Role $role, $action) {
        if ($this->selfUnlimit) {
            return true;
        }
        return Permission::has($role->getPermission($action + 4), $this->accessProper($action));
    }

    public function hasPermission(Role $role, $action) {
        if ($role->isUnlmit()) {
            return true;
        }
        return Permission::has($this->accessProper($action + 4), $role->getPermission($action));
    }

    public function getPermission($action) {
        return $this->accessProper($action);
    }

    public function isUnlmit() {
        return $this->selfUnlimit;
    }

    public function initPermission($id, $token = '', $permission = []) {
        $this->id = $id;
        $this->token = $token;
        foreach ($permission as $id => $p) {
            if ($id == self::S_UNLIMIT) {
                $this->selfUnlimit = $p;
            } else if (!empty(self::PERM_MAP[$id])) {
                $this->accessProper($id, $p);
            }
        }
    }

    protected function accessProper($action, $value = null, &$key = '') {
        if ($action < 1) {
            Kernel::runtimeException('unknow action');
        }
        $proper = self::PERM_MAP[$action];
        $key = $proper;
        if ($value !== null) {
            $this->$proper = $value;
            return $this->$proper;
        } else {
            return $this->$proper;
        }
    }

}
