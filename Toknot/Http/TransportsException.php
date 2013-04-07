<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Http\TransportsException;

use Toknot\Exception\StandardException;

class TransportsException extends StandardException{
       protected $exceptionMessage = 'Socket Transports Un-support'; 
}

?>
