<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2017 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Share\View;

use Toknot\Boot\Tookit;

/**
 * Select
 *
 * @author chopin
 */
class Select extends TagBulid {

    private $option;

    public function __construct($attr) {
        $this->tagName = 'select';

        $option = Tookit::arrayDelete($attr, 'option');
        if ($option && is_array($option)) {
            $this->option = $option;
        }
        $this->initTag($attr);
        $this->setOption();
    }

    public function setOption($selected = null) {
        foreach ($this->option as $showText => $option) {
            if ($selected !== null && isset($option['value']) && $option['value'] == $selected) {
                $option['selected'] = 'selected';
            }
            $op = new AnyTag('option', $option);
            $op->pushText($showText);
            $this->add($op);
        }
    }

    public function addOption($showText, $option) {
        $op = new AnyTag('option', $option);
        Tag::text($op, $showText);
        $this->add($op);
    }

    public function isMultiple() {
        $this->addAttr('multiple', 'multiple');
    }

    public function size($size) {
        $this->addAttr('size', $size);
    }

}
