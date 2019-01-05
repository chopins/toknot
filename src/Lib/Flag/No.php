<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2018 chopin xiao (xiao@toknot.com)
 */

namespace Toknot\Lib\Flag;

use Toknot\Lib\Flag\Flag;

class No extends Flag {

    public function __toString() {
        return 'no';
    }
    
    public function toInt() {
        return 0;
    }

}
