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

/**
 * The Nobody class
 */
class Nobody extends UserAccessControl{
    protected $userName = 'nobody';
    protected $uid = -1;
    protected $gid = -1;
}

?>
