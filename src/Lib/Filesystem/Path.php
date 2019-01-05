<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2018 chopin xiao (xiao@toknot.com)
 */

namespace Toknot\Lib\Filesystem;

use Toknot\Boot\TKObject;

class Path extends TKObject {

    public static function join(array $param, $root = false) {
        if ($root && stripos(PHP_OS, 'Win') !== false && preg_match('/^[a-z]:$/i', $param[0])) {
            $prefix = '';
        } else {
            $prefix = $root ? DIRECTORY_SEPARATOR : '';
        }
        return $prefix . implode(DIRECTORY_SEPARATOR, $param);
    }

}
