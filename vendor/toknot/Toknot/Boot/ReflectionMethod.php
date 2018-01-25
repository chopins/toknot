<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2017 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Boot;

/**
 * ReflectionClass
 *
 */
class ReflectionMethod extends \ReflectionMethod {
    public function __toString() {
        return $this->name;
    }
}
