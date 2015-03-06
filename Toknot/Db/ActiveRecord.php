<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Db;

use Toknot\Core\Object;
use Toknot\Db\DatabaseObject;
use Toknot\Db\Connect;
use Toknot\Core\ArrayObject;
use Toknot\Db\Exception\DatabaseConfigException;

class ActiveRecord extends Object {

    private $dbObject = null;

    protected function __init() {
        $this->dbObject = DatabaseObject::singleton();
    }
    public static function singleton() {
       return parent::__singleton();
    }
    
    /**
     * Get new connect instance of databases
     * 
     * @return Toknot\Db\DatabaseObject
     */
    public function connect() {
        new Connect($this->dbObject);
        return clone $this->dbObject;
    }

    public function config(ArrayObject $config) {
        if (isset($config->dsn) && !empty($config->dsn)) {
            $this->dbObject->setDSN($config->dsn);
        } else {
            throw new DatabaseConfigException('dsn');
        }
        if (isset($config->username)) {
            $this->dbObject->setUsername($config->username);
        } else {
            throw new DatabaseConfigException('username');
        }
        if (isset($config->password)) {
            $this->dbObject->setPassword($config->password);
        } else {
            throw new DatabaseConfigException('password');
        }
        if (isset($config->dirverOptions)) {
            $this->dbObject->setDriverOptions($config->dirverOptions);
        } else {
            throw new DatabaseConfigException('dirverOptions');
        }
        $this->dbObject->setConfig($config); 
    }

}