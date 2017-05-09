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
 * Form
 *
 * @author chopin
 */
class Form extends TagBulid {

    const FORM = 0;
    const FILE = 1;
    const TEXT = 2;

    public function __construct($attr) {
        $this->tagName = 'form';
        $this->initTag($attr);
    }

    public function setAction($value) {
        $this->addAttr('action', $value);
    }

    public function setPost() {
        $this->addAttr('method', 'post');
    }

    public function setGet() {
        $this->addAttr('method', 'get');
    }

    public function setType($value = self::FORM) {
        $enctypeArr = ['application/x-www-form-urlencoded', 'multipart/form-data', 'text/plain'];
        if (empty($enctypeArr[$value])) {
            throw new BaseException('unsupport give form type');
        }
        $this->addAttr('enctype', $enctypeArr[$value]);
    }

    public function label($id = '', $labelHit = '') {
        $labelAttr = $id ? ['for' => $id] : [];
        $label = new AnyTag('label', $labelAttr);
        $label->pushText($labelHit);
        $this->push($label);
        return $label;
    }

    public function input($attr, $labelHit = null) {
        $parent = $this;
        if ($labelHit !== null) {
            $parent = $this->label(self::coalesce($attr, 'id'), $labelHit);
        }
        self::coalesce($attr, 'value');
        $hit = self::coalesce($attr, 'text', $attr['value']);
        $tag = new Input($attr);
        $tag->pushText($hit);
        $parent->push($tag);
        return $this;
    }

    public function select($attr = [], $labelHit = null) {
        $parent = $this;
        if ($labelHit !== null) {
            $parent = $this->label(self::coalesce($attr, 'id'), $labelHit);
        }
        $select = new Select($attr);
        $parent->push($select);
        return $this;
    }

    public function inputs($inputs) {
        foreach ($inputs as $key => $input) {
            self::coalesce($input, 'label', null);
            $input['name'] = is_numeric($key) ? '' : $key;
            if ($input['type'] == 'select') {
                $this->select($input, $input['label']);
            } elseif ($input['type'] == 'textarea') {
                $this->textarea($input, $input['label']);
            } else {
                self::coalesce($input, 'label', null);
                $this->input($input, $input['label']);
            }
        }
    }

    public function textarea($attr = [], $labelHit = null) {
        $parent = $this;
        if ($labelHit !== null) {
            $parent = $this->label(self::coalesce($attr, 'id'), $labelHit);
        }
        $value = self::arrayDelete($attr, 'value');
        $area = new AnyTag('textarea', $attr);
        $value && $area->pushText($value);
        $parent->push($area);
    }

}
