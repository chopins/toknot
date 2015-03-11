<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2015 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Lib\Process\Exception;

use Toknot\Exception\BaseException;

class ProcessException extends BaseException {

    public function __construct() {
        parent::__construct('Process Opreate Exception');
    }

}

?>
