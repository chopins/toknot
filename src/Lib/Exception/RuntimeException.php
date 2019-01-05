<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2018 chopin xiao (xiao@toknot.com)
 */

namespace Toknot\Lib\Exception;

use RuntimeException as RE;

class RuntimeException extends RE {

    public function __construct($message, $code, $previous = null) {
        $debug = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 6);
        $this->line = $debug[4]['line'];
        $this->file = $debug[4]['file'];
        parent::__construct($message, $code, $previous);
    }

}
