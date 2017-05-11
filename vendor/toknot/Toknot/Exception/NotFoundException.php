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

use Toknot\Exception\BaseException;

/**
 * NotFoundException
 *
 * @author chopin
 */
class NotFoundException extends BaseException {

    public function __construct($exception = null) {
        parent::__construct(404, 'Resource Not Found', $exception);
    }

}
