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
    protected $exceptionMessage = 'Database Failed : %s (%s)';
    public function __construct($message, $code) {
        $message = sprintf($this->exceptionMessage,$message, $code);
        parent::__construct($message);
    }
}