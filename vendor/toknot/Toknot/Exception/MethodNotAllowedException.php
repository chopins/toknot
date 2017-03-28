<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2017 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 * @since 4.0
 * @filesource
 * @package Toknot.Exception
 */

namespace Toknot\Exception;

/**
 * NotAllowedMethodException
 * 
 */
class MethodNotAllowedException extends BaseException {

    public function __construct($exception) {
        parent::__construct($exception->getMessage(), $exception->getCode(), $exception->getFile(), $exception->getLine(), $exception);
        $this->httpStatusCode = 405;
        $this->httpMessage = 'Method Not Allowed';
    }

}
