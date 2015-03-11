<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2015 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */
namespace Toknot\Lib\User;

use Toknot\Lib\User\UserAccessControl;

/**
 * The Nobody class
 */
final class Nobody extends UserAccessControl{
    protected $userName = 'nobody';
    protected $uid = -1;
    protected $gid = -1;
    public function getPropertie($name) {
        return $this->$name;
    }
    public function logout() {
        
    }
    public function login() {
        return false;
    }
}

?>
