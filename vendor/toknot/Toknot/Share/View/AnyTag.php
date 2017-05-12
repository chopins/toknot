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
 * HTML of AnyTag
 *
 * @author chopin
 */
class AnyTag extends TagBulid {

    protected static $frames = [];
    protected static $images = [];

    public function __construct($tagName, $attr = []) {
        $this->prepare($tagName, $attr);
        $this->tagName = $tagName;
        $this->initTag($attr);
    }

    protected function prepare($tagName, $attr) {
        $tagName = strtolower($tagName);
        $this->unableException($tagName);
        switch ($tagName) {
            case 'iframe':
            case 'frame':
                if (isset($attr['name'])) {
                    self::$frames[$attr['name']] = $this;
                }
                self::$frames[] = $this;

                break;
            case 'img':
                if (isset($attr['name'])) {
                    self::$images[$attr['name']] = $this;
                }
                self::$images[] = $this;
                break;
        }
    }

    protected function unableException($tagName) {
        $unable = ['form', 'input', 'select', 'html', 'head'];
        if (in_array($tagName, $unable)) {
            throw new BaseException("'$tagName' tag unable to use AnyTag build");
        }
    }

    public static function getFrames() {
        return self::$frames;
    }

    public static function getImages() {
        return self::$images;
    }

}
