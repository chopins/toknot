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

abstract class DbCRUD extends Object{
    public function create() {
        
    }

    public function read();

    public function update();

    public function delete();
}
