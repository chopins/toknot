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
use Toknot\Di\ArrayObject;

class ActiveRecord extends Object {
    private $dbObject = null;
    public function __construct() {
        $this->dbObject = DatabaseObject::singleton();
    }

    public function connect() {
        new Connect($this->dbObject);
        return clone $this->dbObject;
    }
    public function config(ArrayObject $config) {
        $this->dbObject->setDSN($config->dsn);
        $this->dbObject->setUsername($config->username);
        $this->dbObject->setPassword($config->password);
        $this->dbObject->setDriverOptions($config->dirverOptions);
    }
}