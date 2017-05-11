<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2017 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Exception;

use Symfony\Component\HttpFoundation\Response;

/**
 * HttpResponseExcetion
 *
 */
class HttpResponseExcetion extends BaseException {

    public function __construct($code, $message = '', $exception = null) {
        if ($exception) {
            parent::__construct($exception->getMessage(), $exception->getCode(), $exception->getFile(), $exception->getLine(), $exception);
        } else {
            parent::__construct($message, $code);
        }
        $this->httpStatusCode = $code;
        $this->httpMessage = $message ? $message : (isset(Response::$statusTexts[$code]) ? Response::$statusTexts[$code] : '');
    }

}
