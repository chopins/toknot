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
 * ForbiddenException
 *
 */
class ForbiddenException extends HttpResponseExcetion {

    public function __construct($exception = null) {
        parent::__construct(403, 'Forbidden', $exception);
    }

}
