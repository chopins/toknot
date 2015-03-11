<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2015 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */
namespace Toknot\Exception;

use Toknot\Exception\BaseException;

class BadClassCallException extends BaseException {
    protected $exceptionMessage = 'Bad Class Call (%s)';
    public function __construct($class) {
        $this->exceptionMessage = sprintf($this->exceptionMessage, $class);
        parent::__construct($this->exceptionMessage);
    }

}