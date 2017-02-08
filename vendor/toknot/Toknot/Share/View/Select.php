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
 * Select
 *
 * @author chopin
 */
class Select extends TagBulid {

    private $option;

    public function __construct($attr) {
        $this->tagName = 'select';

        $option = \Toknot\Boot\Tookit::arrayDelete($attr, 'option');
        if ($option && is_array($option)) {
            $this->option = $option;
        }
        $this->initTag($attr);
        $this->setOption();
    }

    public function setOption() {
        foreach ($this->option as $showText => $option) {
            $op = new AnyTag('option', $option);
            Tag::text($op, $showText);
            $this->add($op);
        }
    }

    public function addOption($showText, $option) {
        $op = new AnyTag('option', $option);
        Tag::text($op, $showText);
        $this->add($op);
    }

}
