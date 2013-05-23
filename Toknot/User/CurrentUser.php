<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\User;

use Toknot\User\UserControl;
use Toknot\User\Root;

class CurrentUser extends UserControl {
    public function __construct($id) {
        $this->getUserInfo($id);
    }
    public function getUserInfo($id) {
        
    }
}