<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2018 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Exception\PHPError;

use Toknot\Exception\PHPError\NoticeException;

class UndefinedConstantException extends NoticeException {

    protected $exceptionConstant = '';

    public function __construct($error, $previous) {
        parent::__construct($error, $previous);
        $matches = [];
        preg_match('/constant\s(.*)\s-/i', $error[1],$matches);
        $this->exceptionConstant = $matches[1];
    }

    public function getExceptionConstant() {
        return $this->exceptionConstant;
    }

}
