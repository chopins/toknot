<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2018 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Exception\PHPError;

use Toknot\Exception\BaseException;

class WarningException extends BaseException {

    public function __construct($param, $previous) {
        $this->setFile($param[2]);
        $this->setLine($param[3]);
        parent::__construct($param[1], $param[0], $previous);
    }

}
