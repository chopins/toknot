<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\User\Exception;

use Toknot\Exception\StandardException;

class NoPermissionExecption extends StandardException {
    public static $displayController = null;
    public static $method = 'GET';
    private $html;
    public function __construct($message) {
        if(!is_object(self::$displayController)) {
            return parent::__construct($message);
        }
        ob_start();
        self::$displayController->message = $message;
        $method = $this->method;
        self::$displayController->$method();
        $this->html = ob_get_clean();
    }
    
    public function __toString() {
        return $this->html;
    }
}

?>
