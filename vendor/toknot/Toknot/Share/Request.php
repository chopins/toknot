<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2017 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Share;

use Symfony\Component\HttpFoundation\Request as SRequest;

/**
 * Request
 *
 * @author chopin
 */
class Request extends SRequest {

    public function get($key = '', $default = '') {
        if (empty($key)) {
            return array_merge($this->request->all(), $this->query->all(),
                    $this->attributes->all());
        }
        return parent::get($key, $default);
    }

    public function file($key = '') {
        if (empty($key)) {
            return $this->files->all();
        }
        return $this->files->get($key);
    }
    

}
