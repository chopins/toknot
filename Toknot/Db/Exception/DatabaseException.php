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
    public function __construct($message, $code =0, $sql = '',$param = null) {
        $message = "Database Failed : $message ($code)";
        $this->sqls[] = $sql;
        $this->params[] = $param;
        parent::__construct($message);
    }
}