<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2017 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Exception;

use Toknot\Exception\BaseException;

/**
 * NotFoundException
 *
 * @author chopin
 */
class NotFoundException extends BaseException {

    public function __construct($exception) {
        parent::__construct($exception->getMessage(), $exception->getCode(), $exception->getFile(), $exception->getLine(), $exception);
        $this->httpStatusCode = 404;
        $this->httpMessage = 'Resource Not Found';
    }

}
