<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Db\Exception;

use Toknot\Exception\StandardException;

class DatabaseException extends StandardException {
    public function __construct($message, $code =0) {
        $message = print_r($message, true);
        $code = print_r($code, true);
        $message = "Database Failed : $message ($code)";
        parent::__construct($message);
    }
}