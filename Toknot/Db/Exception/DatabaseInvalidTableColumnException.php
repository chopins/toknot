<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Db\Exception;

use Toknot\Exception\BaseException;

class DatabaseInvalidTableColumnException extends BaseException {
    public function __construct($column,$table,$dbname) {
        $message = "Table $table not exists $column Column in Database $dbname";
        parent::__construct($message);
    }
}