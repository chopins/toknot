<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2018 chopin xiao (xiao@toknot.com)
 */

namespace Toknot\Lib\Flag;

use Toknot\Lib\Flag\Flag;

class Yes extends Flag {

    public function __toString() {
        return 'yes';
    }

    public function toInt() {
        return 1;
    }

}
