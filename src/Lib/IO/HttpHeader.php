<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2018 chopin xiao (xiao@toknot.com)
 */

namespace Toknot\Lib\IO;

use Toknot\Boot\Kernel;
use Toknot\Boot\TKObject;

class HttpHeader extends TKObject {

    private static $instance = null;
    private $protocol = Kernel::NOP;

    private function __construct() {
        $this->protocol = Request::protocol();
    }

    public static function __callStatic($name, $arguments = []) {
        if (self::$instance === null) {
            self::$instance = new static;
        }
        if (method_exists(get_called_class(), $name)) {
            return self::$instance->invoke($name, $arguments);
        }
        parent::__callStatic($name, $arguments);
    }

    protected function h404() {
        header("{$this->protocol} 404 Not Found", true, 404);
    }

    protected function h301($url) {
        header("Location: $url", true, 301);
        exit;
    }

    protected function h302($url) {
        header("Location: $url", true, 302);
        exit;
    }

    protected function h500() {
        header("{$this->protocol} 500 Internal Server Error", true, 500);
    }

    protected function h503() {
        header("{$this->protocol} 503 Service Unavailable", true, 503);
    }

    protected function attachment($filename) {
        header('Content-Description: File Transfer');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header("Content-Disposition: attachment; filename=\"$filename\"");
    }

    protected function contentLength($size) {
        header('Content-Length: ' . $size);
    }

    protected function contentType($type) {
        header("Content-type: $type");
    }

}
