<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2017 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Share\NoSQL;

use MongoDB\Driver\Manager;
use MongoDB\Driver\BulkWrite;
use MongoDB\Driver\WriteConcern;
use MongoDB\Driver\Query;
use MongoDB\BSON\ObjectID;
use MongoDB\BSON\Binary;
use MongoDB\Driver\ReadPreference;
use Toknot\Exception\BaseException;

/**
 * Mongodb
 *
 */
class Mongodb {

    private $client = null;
    private $writer = null;
    private $uriOption = ['appname' => 'Toknot PHP Mongodb Client', 'ssl' => false];
    private $dirverOption = [];
    private $host = null;
    private $port = null;
    private $dbname = null;
    private $collation = null;
    private $multi = false;
    private $upsert = false;
    private $queryOption = [];
    private static $version = 0;
    private $reader = null;
    private $writeMode = WriteConcern::MAJORITY;
    private $writeTimeout = 100;

    public function setPassword($password) {
        $this->uriOption['password'] = $password;
        return $this;
    }

    public function setUsername($uername) {
        $this->uriOption['password'] = $uername;
        return $this;
    }

    public function setUriOption($key, $value) {
        $this->uriOption[$key] = $value;
        return $this;
    }

    public function setDriverOption($key, $value) {
        $this->dirverOption[$key] = $value;
        return $this;
    }

    public function setTimeout($time) {
        $this->uriOption['connectTimeoutMS'] = $time;
        return $this;
    }

    public function setCollation($collation) {
        $this->collation = $collation;
        return $this;
    }

    public function enableSSL() {
        $this->uriOption['ssl'] = true;
        return $this;
    }

    public function selectDB($dbname) {
        $this->dbname = $dbname;
        return $this;
    }

    public function setWriteMode($w) {
        $this->writeMode = $w;
        return $this;
    }

    public function setWriteTimeout($timeout) {
        $this->writeTimeout = $timeout;
        return $this;
    }

    public function __construct($host, $dbname, $option = []) {
        if(!self::$version) {
            self::$version = phpversion('mongodb');
        }
        if(!self::$version) {
            throw new BaseException('mongodb extension unload');
        }
        if (is_array($host)) {
            $host = implode(',', $host);
        }
        $this->host = $host;
        $this->dbname = $dbname;
        $this->uriOption = array_merge($this->uriOption, $option);
    }

    public function getVersion() {
        return $this->version;
    }

    public function conn() {
        $this->client = new Manager("mongodb://{$this->host}:{$this->port}", $this->uriOption, $this->dirverOption);
        $this->writer = new WriteConcern($this->writeMode, $this->writeTimeout);
        $this->reader = new ReadPreference();
        return $this;
    }

    public function getServers() {
        return $this->client->getServers();
    }

    public function insert($data, &$id) {
        $bulk = new BulkWrite();
        if (empty($data['_id'])) {
            $data['_id'] = new ObjectID;
        }
        $id = $bulk->insert($data);
        return $this->client->executeBulkWrite($this->dbname, $bulk, $this->writer);
    }

    public function setMulti($v = false) {
        $this->multi = $v;
        return $this;
    }

    public function setUpsert($v = false) {
        $this->upsert = $v;
        return $this;
    }

    public function update($operator, $filter, $data) {
        $bulk = new BulkWrite();
        $option = ['multi' => $this->multi, 'upsert' => $this->upsert];
        if ($this->collation) {
            $option['collation'] = $this->collation;
        }
        $bulk->updata($filter, [$operator => $data], $option);
        return $this->client->executeBulkWrite($this->dbname, $bulk, $this->writer);
    }

    public function set($filter, $data) {
        return $this->update('$set', $filter, $data);
    }

    public function increment($filter, $data) {
        return $this->update('$inc', $filter, $data);
    }

    public function multiply($filter, $data) {
        return $this->update('$mul', $filter, $data);
    }

    public function delFeild($filter, $fields) {
        $data = [];
        foreach ($fields as $f) {
            $data[$f] = '';
        }
        return $this->update('$unset', $filter, $data);
    }

    public function rename($filter, $fields) {
        return $this->update('$rename', $filter, $fields);
    }

    public function replace($filter, $id, $data) {
        $bulk = new BulkWrite();
        $option = ['multi' => $this->multi, 'upsert' => $this->upsert];
        if ($this->collation) {
            $option['collation'] = $this->collation;
        }
        $data['_id'] = new ObjectID($id);
        $bulk->updata($filter, $data, $option);
        return $this->client->executeBulkWrite($this->dbname, $bulk, $this->writer);
    }

    public function delete($filter, $limit) {
        $bulk = new MongoDB\Driver\BulkWrite;
        $option = ['limit' => $limit];
        if ($this->collation) {
            $option['collation'] = $this->collation;
        }
        $bulk->delete($filter, $option);
        return $this->client->executeBulkWrite($this->dbname, $bulk, $this->writer);
    }

    public function setDefaultQueryOption($key, $value) {
        $this->queryOption[$key] = $value;
        return $this;
    }

    public function md5Binary($data) {
        return new Binary($data, Binary::TYPE_MD5);
    }

    public function binData($data, $type = Binary:: TYPE_GENERIC) {
        return new Binary($data, $type);
    }

    public function query($filter, $limit = 10, $option = []) {
        $option = array_merge($this->queryOption, $option);
        if ($this->collation) {
            $option['collation'] = $this->collation;
        }
        $option['limit'] = $limit;
        $query = new Query($filter, $option);
        return $this->client->executeQuery($this->dbname, $query);
    }

}
