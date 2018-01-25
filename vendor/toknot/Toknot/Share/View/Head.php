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
 * Head
 *
 */
class Head extends TagBulid {

    private $titleNodeCnt = 0;

    public function __construct() {
        $this->tagName = 'head';
        $this->initTag();
    }

    public function push(TagBulid $tag) {
        if ($tag->tagName == 'title') {
            if ($this->titleNodeCnt > 0) {
                throw new BaseException('head only alowed 1 title tag');
            }
            $this->titleNodeCnt++;
        }
        parent::push($tag);
    }

}
