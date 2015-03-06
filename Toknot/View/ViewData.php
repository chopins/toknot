<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\View;

use Toknot\Core\ArrayObject;
use Toknot\Exception\BadPropertyGetException;

class ViewData extends ArrayObject{
    public function getPropertie($propertie) {
        try {
            return parent::getPropertie($propertie);
        } catch (BadPropertyGetException $e) {
            return '';
        }
    }
}
