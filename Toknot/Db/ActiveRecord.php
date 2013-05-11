<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Db;

use Toknot\Di\Object;
use Toknot\Di\DatabaseObject;
use Toknot\Db\Connect;

class ActiveRecord {
    public function connect($config) {
        $connectObject = new DatabaseObject;
        $connect = new Connect($connectObject, $config);
        return $connectObject;
    }
}