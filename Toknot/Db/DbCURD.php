<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Db;


abstract class DbCRUD {
    private $opretaeKeys = array('INSERT', 'CREATE','UPDATE','SELECT','DELETE','JOIN');
    public function create() {
        $class = get_called_class();
        $opreateKey = 'INSERT';
        switch ($class) {
            
        }
    }

    public function read();

    public function update();

    public function delete();
}
