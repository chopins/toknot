<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2018 chopin xiao (xiao@toknot.com)
 */

namespace Toknot\Lib\View;

use Toknot\Boot\ArrayObject;

class ViewData extends ArrayObject {

    public function __set($name, $value) {
        $this->offsetSet($name, $value);
    }
    
    public function add($name, $value) {
        $this->offsetSet($name, $value);
    }

}
