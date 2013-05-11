<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @link       https://github.com/chopins/toknot
 */
namespace Toknot\Exception;

use Toknot\Exception\StandardException;

class BadClassCallException extends StandardException {
    protected $exceptionMessage = 'Bad Class Call (%s)';
    public function __construct($class) {
        $message = sprintf($this->exceptionMessage, $class);
        parent::__construct($message);
    }

}