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
class ControllerInvalidException extends BaseException {
    protected $exceptionMessage = 'Controller %s Invalid';
    public function __construct($class) {
        $this->exceptionMessage = sprintf($this->exceptionMessage, $class);
        parent::__construct($this->exceptionMessage);
    }
}

