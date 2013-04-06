<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Process\Eception;

use Toknot\Exception\StandardException;

class PipException extends StandardException {

    public function __construct() {
        parent::__construct('Socket Opreate Exception');
    }

}

?>
