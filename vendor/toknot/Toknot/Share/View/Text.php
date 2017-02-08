<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2017 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Share\View;

class Text {

    private $textContent = '';

    public function __construct($string) {
        $this->textContent = $string;
    }

    public function getTags() {
        return $this->textContent;
    }

}
