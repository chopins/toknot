<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2017 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Share\View;

use Toknot\Exception\BaseException;

/**
 * Input
 *
 * @author chopin
 */
class Input extends TagBulid {

    protected static $inputTag = ['text', 'password', 'button', 'checkbox',
        'image', 'hidden', 'file', 'radio', 'reset',
        'submit'];

    public function __construct($attr) {
        if (self::hasType($attr['type'])) {
            $this->tagName = 'input';
            $attr['type'] = $attr['type'];
            $this->initTag($attr);
        } else {
            throw new BaseException("input tag not defined type {$attr['type']}");
        }
    }

    public static function hasType($type) {
        return in_array($type, self::$inputTag);
    }

    public static function addType($type) {
        array_push(self::$inputTag, $type);
    }

}
