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
 * FileOrDirException
 *
 * @author chopin
 */
class NoFileOrDirException extends BaseException {

    protected $exceptionFile = '';

    public function __construct($message = '', $code = 0, $file = null, $line = null, $exceIns = null) {
        parent::__construct($message, $code, $exceIns);
        $start = strpos($message, '(') + 1;
        $end = strpos($message, ')');
        $this->exceptionFile = substr($message, $start, $end - $start);
    }

    public function getExceptionFile() {
        return $this->exceptionFile;
    }

}
