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

final class Root extends UserControl {
    protected $userName = 'root';
    protected $uid = 0;
    protected $password = false;
    protected $groupName = 'root';
    protected $gid = 0;
    protected $login = false;
}