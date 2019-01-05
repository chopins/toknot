<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2018 chopin xiao (xiao@toknot.com)
 */

namespace Toknot\Lib\Exception;

use RuntimeException;
use Toknot\Boot\Kernel;
use Toknot\Lib\Exception\ErrorReportHandler;

class SQLQueryErrorException extends RuntimeException {

    public function __construct($message, $code, $sql, $params) {
        $message .= Kernel::EOL . "QuerySQL:[ $sql ]" . Kernel::EOL . "Parameter: " . var_export($params, true);
        $trace = $this->getTrace();
        foreach($trace as $t) {
            if($t['function'] === 'throwException' && $t['class'] === ErrorReportHandler::class) {
                $this->file = $t['file'];
                $this->line = $t['line'];
            }
        }
        parent::__construct($message, $code);
    }

}
