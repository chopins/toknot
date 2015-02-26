<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Exception;
class CustomHttpStatusExecption extends TKException {
   public static $displayController = null;
    public static $method = 'GET';
    public static $FMAI;
    protected $httpStatus = '500 Internal Server Error';
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
            header($this->httpStatus);
        }
        if (!self::$displayController) {
            $traceInfo = $this->getDebugTraceAsString();
            if (DEVELOPMENT) {
                return $traceInfo;
            } else {
                Log::save($traceInfo);
                return $this->httpStatus;
            }
        }
        return $this->html;
    }
}

?>
