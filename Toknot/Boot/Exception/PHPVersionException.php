<?php
/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2015 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Boot\Exception;

use Toknot\Exception\BaseException;
class PHPVersionException extends BaseException {
    protected $exceptionMessage = 'only support php version or 5.3 or lastest';
}

