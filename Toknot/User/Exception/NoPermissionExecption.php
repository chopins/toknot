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
    public static $FMAI;
    private $html;

    public function __construct($message) {
        if (!self::$displayController) {
            $this->noCustomController = true;
            return parent::__construct($message);
        }
        ob_start();
        $clsName = self::$displayController;
        $ins = new $clsName(self::$FMAI);
        $ins->message = $message;
        $method = self::$method;
        $ins->$method();
        $this->html = ob_get_clean();
    }

    public function __toString() {
        if (PHP_SAPI !== 'cli') {
            header('HTTP/1.0 403 Forbidden');
        }
        if (!self::$displayController) {
            $traceInfo = $this->getDebugTraceAsString();
            if (DEVELOPMENT) {
                return $traceInfo;
            } else {
                Log::save($traceInfo);
                return '403 Forbidden';
            }
        }
        return $this->html;
    }

}

?>
