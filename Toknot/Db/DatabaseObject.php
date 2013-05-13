<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @link       https://github.com/chopins/toknot
 */
namespace Toknot\Di;
use Toknot\Db\DbCRUD;

class DatabaseObject extends DbCRUD {
    protected $dsn = null;
    protected $user = null;
    protected $password = null;
    protected $connectInstance = null;
    public function setPropertie($propertie, $value) {
        if(isset($this->$propertie)) return;
        $this->$propertie = new DbTableObject($propertie, $value);
    }
    public function __get($propertie) {
        if(isset($this->$propertie)) {
            return $this->$propertie;
        }
    }

    public function create() {
        
    }
    public function getDSN() {
        return $this->dsn;
    }
}