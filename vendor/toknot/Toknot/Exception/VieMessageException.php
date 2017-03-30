<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2017 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Exception;

/**
 * VieMessageException
 *
 * @author chopin
 */
class VieMessageException extends BaseException {

    public function __construct($pkv, $uniqid, $rollbackMsg, $previous = null) {
        $err = $previous->geMessage();
        $message = "PK Value is:$pkv, Lock is: $uniqid, Error Message:$err,$rollbackMsg";
        parent::__construct($message);
    }

}
