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

namespace Toknot\Exception\PHPError;

use Toknot\Exception\PHPError\WarningException;

/**
 * FileOrDirException
 *
 * @author chopin
 */
class NoFileOrDirException extends WarningException {

    protected $exceptionFile = '';

    public function __construct($errinfo, $previous) {
        parent::__construct($errinfo, $previous);
        $matches = [];
        preg_match('/[a-z]+\((.*)\):/', $errinfo[1], $matches);
        $this->exceptionFile = $matches[1];
    }

    public function getExceptionFile() {
        return $this->exceptionFile;
    }

}
