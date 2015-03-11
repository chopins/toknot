<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2015 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Share\User\Exception;

use Toknot\Exception\CustomHttpStatusExecption;

class NoPermissionExecption extends CustomHttpStatusExecption {
    protected $httpStatus = 'Status: 401 Unauthorized';
}

