<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\View;

use Toknot\Di\ArrayObject;
use Toknot\Exception\BadPropertyGetException;

class ViewData extends ArrayObject{
    public function __get($propertie) {
        try {
            return parent::__get($propertie);
        } catch (BadPropertyGetException $e) {
            if(DEVELOPMENT) {
                return '[null]';
            } else {
                return '';
            }
        }
    }
}
