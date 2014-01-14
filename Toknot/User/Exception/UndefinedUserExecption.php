<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\User\Exception;

use Toknot\Exception\StandardException;
class UndefinedUserExecption extends StandardException {
    protected $message = 'Undefined User';
}

?>
