<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2018 chopin xiao (xiao@toknot.com)
 */

namespace Toknot\Lib\Flag;

abstract class Flag {

    public static function isme($res) {
        return is_a($res, get_called_class());
    }

}
