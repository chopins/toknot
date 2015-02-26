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
class ControllerInvalidException extends TKException {
    protected $exceptionMessage = 'Controller %s Invalid';
    public function __construct($class) {
        $this->exceptionMessage = sprintf($this->exceptionMessage, $class);
        parent::__construct($this->exceptionMessage);
    }
}

