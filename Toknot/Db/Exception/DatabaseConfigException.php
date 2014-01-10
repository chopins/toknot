<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Db\Exception;
use Toknot\Exception\StandardException;

class DatabaseConfigException extends StandardException {
    public function __construct($configParam) {
        $message = "Must be set '{$configParam}' of database connect in ini of database sections,<br />\n";
        $message .= 'database of options has dsn,username,password,dirverOptions(which is array type),prefix(which is table name prefix and option)';
        parent::__construct($message);
    }
}

?>
