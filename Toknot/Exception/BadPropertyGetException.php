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

class BadPropertyGetException extends StandardException {
    protected $exceptionMessage = 'Bad Property Get (%s::$%s)';
    public function __construct($class,$property) {
        $this->exceptionMessage = sprintf($this->exceptionMessage, $class,$property);
        parent::__construct($this->exceptionMessage);
    }

}