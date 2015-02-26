<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Exception;

use Toknot\Exception\TKException;

class BadNamespaceException extends TKException {

    protected $exceptionMessage = 'Bad Namespace Declaration (%s)';

    public function __construct($namespace) {
        $message = sprintf($this->exceptionMessage, $namespace);
        parent::__construct($message);
    }

}