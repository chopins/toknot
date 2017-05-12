<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2017 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Tool\Controller\Console;

use Toknot\Share\DB\DBA;
use Toknot\Boot\Kernel;

use Toknot\Boot\Logs;
use Toknot\Boot\Configuration;

class DB {
    
    /**
     *
     * @var Toknot\Share\DB
     */
    public $tkdb;
    public $dbcfg;
    public $appcfg;
    public $appdir;
    public $kernel;
    public $usedb;
    public $force = false;
    public $dbconn;
    public $tableOption = [];
    public $confObj = null;
    public function __construct() {
        $this->kernel = Kernel::single();
        $this->appdir = $this->kernel->getArg('-a');
        $type = $this->kernel->getArg('-t');
        if ($this->appdir) {
            $this->appdir = realpath($this->appdir);
            $type || $type = 'ini';
            $config = "{$this->appdir}/config/config.$type";
            $this->confObj = new Configuration;
            $this->confObj->setAppDir($this->appdir);
            $this->appcfg = $this->confObj->load($config);
        } else {
            $this->appcfg = $this->kernel->cfg;
        }

        $this->dbcfg = $this->appcfg->database;

        $this->usedb = $this->dbcfg->default;
        $this->setOption();
        $this->tableOption = $this->dbcfg[$this->usedb];

        $this->tkdb = DBA::single($this->usedb, $this->appcfg);
        
        $this->tkdb->setAppDir($this->appdir);
        $dbs = $this->tkdb->getDBList();
        
        $dbname = $this->dbcfg[$this->usedb]['dbname'];
        
        if (!in_array($dbname, $dbs)) {
            Logs::colorMessage("Try Create database: $dbname", 'purple');
            $this->tkdb->createDatabase($dbname);
            Logs::colorMessage('Create Success', 'green');
        }
        $this->dbconn = $this->tkdb->connect();
    }

    public function setOption() {
        if ($this->kernel->hasOption('-f')) {
            $this->force = true;
        } else {
            $this->force = false;
        }
        if ($this->kernel->getArg('-d')) {
            $passdb = $this->kernel->getArg('-d');
        }

        if (isset($passdb) && isset($this->dbcfg[$passdb])) {
            $this->usedb = $passdb;
        } else if (isset($passdb)) {
            Logs::colorMessage("The $passdb db config not exists,use default config ", 'red');
        }
    }

    /**
     * init database tables
     * 
     * -f drop table if exists
     * -d key of dbname config
     * -a set app path
     * -t config type
     * 
     * @console db.init
     */
    public function init() {
        $name = $this->tableOption['table_config'];
        Logs::colorMessage('Create database:', 'green');
        $res = $this->tkdb->initDatabaseTables($this->usedb, $name, $this->force);
        foreach ($res as $sql) {
            Logs::colorMessage('Exec: ', 'purple', false);
            Logs::colorMessage($sql);
        }
    }

    /**
     * update database table struct
     * 
     * -d key of dbname config
     * -a app path 
     * -t config type
     * 
     * @console db.update
     */
    public function update() {
        $tablefile = $this->tableOption['table_config'];
       
        $from = $this->tkdb->getAllTableStructureCacheArray();
        $to = $this->tkdb->loadConfig($tablefile);
        $this->tkdb->initModel($to, $this->usedb);
        $sql = $this->tkdb->updateSchema($from, $to);
        Logs::colorMessage('update database:', 'green');
        foreach ($sql as $t) {
            Logs::colorMessage('Exec: ', 'purple', false);
            Logs::colorMessage($t);
            $this->dbconn->executeUpdate($t);
        }

        Logs::colorMessage('Update Success');
    }

}
