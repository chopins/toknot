<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */
class CreateUserTable {

    public function __construct($argv) {
        $this->toknotDir = dirname(__DIR__);
        $this->workDir = getcwd();
        require_once $this->toknotDir . '/Control/Application.php';
        define('DEVELOPMENT', true);
        new Toknot\Control\Application;
        
        $appPath = false;
        if (!empty($argv[1]) && $argv[1] != 'CreateUserTable') {
            $appPath = $this->checkAppPath($argv[1]);
        }
        if (!empty($argv[1]) && $argv[1] == 'CreateUserTable' && !empty($argv[2])) {
            $appPath = $this->checkAppPath($argv[2]);
        }
        if (!$appPath) {
            while (true) {
                Toknot\Di\Log::colorMessage('Enter path of app path:', null, false);
                $appPath = trim(fgets(STDIN));
                if (!empty($appPath)) {
                    $appPath = $this->checkAppPath($appPath);
                    if ($appPath) {
                        break;
                    }
                }
            }
        }
        Toknot\Control\FMAI::singleton(basename($appPath), $appPath);
        Toknot\Config\ConfigLoader::singleton();
        $cfg = Toknot\Config\ConfigLoader::importCfg($appPath.'/Config/config.ini');
        $db = $this->activeRecord($cfg);
        $this->createUserTable($db, $cfg);
    }

    public function createUserTable($db, $cfg) {
        $sql = Toknot\Db\ActiveQuery::createTable($db->tablePrefix.$cfg->User->userTableName);
        $sql .= "(`{$cfg->User->userIdColumnName}` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,";
        $sql .= "`{$cfg->User->userNameColumnName}` VARCHAR(200) NOT NULL,";
        $sql .= "`{$cfg->User->userGroupIdColumnName}` VARCHAR(225) NOT NULL,";
        $sql .= "`{$cfg->User->userPasswordColumnName}` VARCHAR(225) NOT NULL,";
        $sql .= "PRIMARY KEY (`{$cfg->User->userIdColumnName}`),";
        $sql .= "KEY `{$cfg->User->userNameColumnName}` (`{$cfg->User->userNameColumnName}`),";
        $sql .= "KEY `{$cfg->User->userGroupIdColumnName}` (`{$cfg->User->userGroupIdColumnName}`)";
        $sql .= ") ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1";
        $db->create($sql);
        Toknot\Di\Log::colorMessage('create user table success', 'green');
    }

    public function checkAppPath($file) {
        $config = realpath($file);
        if ($config) {
            return $config;
        }
        Toknot\Di\Log::colorMessage("$file not exits", 'red');
        return false;
    }

    public function activeRecord($cfg) {
        Toknot\Control\StandardAutoloader::importToknotModule('Db', 'DbCRUD');
        $ar = Toknot\Db\ActiveRecord::singleton();
        $ar->config($cfg->Database);
        return $ar->connect();
    }

}

new CreateUserTable($argv);
