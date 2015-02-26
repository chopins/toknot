<?php
/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Control\Exception;

use Toknot\Exception\TKException;
class PHPVersionException extends TKException {
    protected $exceptionMessage = 'only support php version or 5.3 or lastest';
}

