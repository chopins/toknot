<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2018 chopin xiao (xiao@toknot.com)
 */

namespace Toknot\Lib\Exception;

use Exception;
use Toknot\Boot\Kernel;

class ErrorReportException extends Exception {

    public function __construct($message, $code, $file, $line) {
        parent::__construct($message, $code);
        $this->file = $file;
        $this->line = $line;
    }
}
