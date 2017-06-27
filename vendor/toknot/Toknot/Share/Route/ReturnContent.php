<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2017 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Share\Route;

/**
 * ReturnContent
 *
 */
class ReturnContent extends Router {

    public function response($runResult) {
        
    }

    public function returnResponse($runResult) {
        return $runResult;
    }

}
