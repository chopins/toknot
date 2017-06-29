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
use Toknot\Boot\Tookit;

/**
 * Form
 *
 * @author chopin
 */
class Form extends TagBulid {

    const FORM = 0;
    const FILE = 1;
    const TEXT = 2;

    private static $forms = [];

    public function __construct($attr) {
        $this->tagName = 'form';
        $this->initTag($attr);
        self::$forms[] = $this;
        if (isset($attr['name'])) {
            self::$forms[$attr['name']] = $this;
        }
    }

    public static function getForms() {
        return self::$forms;
    }

    public function setAction($value) {
        return $this->addAttr('action', $value);
    }

    public function setPost() {
        return $this->addAttr('method', 'post');
    }

    public function setGet() {
        return $this->addAttr('method', 'get');
    }

    public function setMethod($method) {
        return $this->addAttr('method', $method);
    }

    public function setType($value = self::FORM) {
        $enctypeArr = ['application/x-www-form-urlencoded', 'multipart/form-data', 'text/plain'];
        if (empty($enctypeArr[$value])) {
            throw new BaseException('unsupport give form type');
        }
        return $this->addAttr('enctype', $enctypeArr[$value]);
    }

    public function label($id = '', $labelHit = '', $parent = null) {
        $parent = $parent ? $parent : $this;
        $labelAttr = $id ? ['for' => $id] : [];
        $label = new AnyTag('label', $labelAttr);
        $label->pushText($labelHit);
        $parent->push($label);
        return $label;
    }

    public function input($attr, $labelHit = null, $parent = null) {
        if ($parent) {
            $this->push($parent);
        } else {
            $parent = $this;
        }

        if ($labelHit !== null) {
            $parent = $this->label(Tookit::coalesce($attr, 'id'), $labelHit, $parent);
        }
        Tookit::coalesce($attr, 'value');
        $tag = new Input($attr);
        $hit = Tookit::coalesce($attr, 'text', $attr['value']);
        $tag->pushText($hit);
        $tag->setHit($hit);
        $tag->getType() == 'radio' ? $parent->unshift($tag) : $parent->push($tag);
        return $this;
    }

    public function select($attr = [], $labelHit = null, $parent = null) {
        if ($parent) {
            $this->push($parent);
        } else {
            $parent = $this;
        }
        if ($labelHit !== null) {
            $parent = $this->label(Tookit::coalesce($attr, 'id'), $labelHit);
        }
        $select = new Select($attr);
        $parent->push($select);
        return $this;
    }

    public function inputs($inputs) {
        foreach ($inputs as $key => $input) {
            Tookit::coalesce($input, 'label', null);

            $input['name'] = is_numeric($key) ? ($input['name'] ? $input['name'] : '') : $key;
            $parent = isset($input['parent']) && $input['parent'] instanceof TagBulid ? $input['parent'] : null;
            if ($input['type'] == 'select') {
                $this->select($input, $input['label'], $parent);
            } elseif ($input['type'] == 'textarea') {
                $this->textarea($input, $input['label'], $parent);
            } else {
                Tookit::coalesce($input, 'label', null);

                $this->input($input, $input['label'], $parent);
            }
        }
        return $this;
    }

    public function textarea($attr = [], $labelHit = null, $parent = null) {
        if ($parent) {
            $this->push($parent);
        } else {
            $parent = $this;
        }
        if ($labelHit !== null) {
            $parent = $this->label(Tookit::coalesce($attr, 'id'), $labelHit);
        }
        $value = Tookit::arrayDelete($attr, 'value');
        $area = new AnyTag('textarea', $attr);
        $value && $area->pushText($value);
        $parent->push($area);
        return $this;
    }

}
