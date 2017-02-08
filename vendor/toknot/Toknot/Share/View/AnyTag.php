<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2017 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Share\View;

/**
 * HTML of AnyTag
 *
 * @author chopin
 */
class AnyTag extends TagBulid {

    public function __construct($tagName, $attr = []) {
        $this->tagName = $tagName;
        $this->initTag($attr);
    }

}
