<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Image\CartoonImage;

class CartoonImage {

    private $originImg = null;
    private $outImg = null;
    private $with = 0;
    private $height = 0;

    public function __construct($src, $des) {
        $this->orginImg = imagecreatefromjpeg($src);
        
        $this->outImg = $des;
    }

    public function toBack() {
        imagecopymergegray($this->originImg, $this->outImg,
                            0,0,0,0,50);
    }

}

?>
