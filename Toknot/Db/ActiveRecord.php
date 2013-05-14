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
use Toknot\Db\DatabaseObject;
use Toknot\Db\Connect;
use Toknot\Config\ConfigObject;

class ActiveRecord extends Object {
    private $dbObject = null;
    public function __construct() {
        $this->dbObject = new DatabaseObject;
    }

    public function connect() {
        new Connect($this->dbObject);
        return clone $this->dbObject;
    }
    public function config(ConfigObject $config) {
        $this->dbObject->dsn = $config->dsn;
        $this->dbObject->username = $config->username;
        $this->dbObject->password = $config->password;
        $this->dbObject->driverOptions = $config->dirverOptions;
    }
}