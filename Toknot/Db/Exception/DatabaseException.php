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

class DatabaseException extends StandardException {

    public $sqls = array();
    public $params = array();

    public function __construct($message, $code = 0, $sql = null, $param = null) {
        $PDOCode = $code;
        if (is_array($message)) {
            $code = $message[1];
            $message = $message[2];
        }
        $message = "Database Failed({$PDOCode}) : $message ($code)";
        if ($sql) {
            $this->sqls[] = $sql;
            $this->params[] = $param;
        }
        parent::__construct($message);
    }

}