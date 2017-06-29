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
    protected $type = '';
    protected $value = '';
    protected $property = ['type', 'value'];

    public function __construct($attr = []) {
        if (isset($attr['type']) && !self::hasType($attr['type'])) {
            throw new BaseException("input tag unsupport {$attr['type']} type ");
        }

        if (version_compare(self::$page->getVer(), 4) === 1 &&
                $attr['type'] == 'button' || $attr['type'] == 'submit') {
            $this->tagName = 'button';
        } else {
            $this->tagName = 'input';
        }
        $this->initTag($attr);
        if ($this->tagName == 'button' && isset($attr['value'])) {
            $this->pushText($attr['value']);
        }
        $this->type = $attr['type'];
        $this->value = isset($attr['value']) ? $attr['value'] : '';
    }

    public function getType() {
        return $this->type;
    }

    public function getValue() {
        return $this->value;
    }

    public static function hasType($type) {
        return in_array($type, self::$inputTag);
    }

    public static function addType($type) {
        array_push(self::$inputTag, $type);
    }

    public function setType($type) {
        if (!self::hasType($type)) {
            throw new BaseException("input tag unsupport type $type");
        }

        $this->addAttr('type', $type);
        return $this;
    }

    public function setValue($value = '') {
        $this->addAttr('value', $value);
        $this->value = $value;
        return $this;
    }

    public function setHit($value) {
        $this->addAttr('placeholder', $value);
        return $this;
    }

    public function required() {
        $this->addAttr('required', 'required');
        return $this;
    }

    public function readonly() {
        $this->addAttr('readonly', 'readonly');
        return $this;
    }

    public function disabled() {
        $this->addAttr('disabled', 'disabled');
        return $this;
    }

}
