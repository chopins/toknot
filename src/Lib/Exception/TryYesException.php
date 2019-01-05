<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2018 chopin xiao (xiao@toknot.com)
 */

namespace Toknot\Lib\Exception;

class TryYesException extends Exception {

    public function __construct(&$result) {
        $result = $this;
        parent::__construct();
    }

}
