<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2017 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Share\Service;

use Toknot\Includes\SystemCallWrapper;
use Toknot\Boot\Object;

/**
 * Wapper
 *
 * @author chopin
 */
class Wrapper extends Object implements SystemCallWrapper {

    protected $pathInfo = [];

    public function call() {
        
    }

    public function init($path = '') {
        $this->pathInfo = parse_url($path);
    }

    public static function getInstance($kernel) {
        return self::single($kernel);
    }

    public function response($runResult) {
        var_dump($runResult);
    }
    
    public function returnResponse($runResult) {
        return $runResult['content'];
    }

    public function getArg($key) {
        return $key;
    }

}
