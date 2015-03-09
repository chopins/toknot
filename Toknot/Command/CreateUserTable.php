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

        define('DEVELOPMENT', true);
        
        $appPath = false;
        if (!empty($argv[1]) && $argv[1] != 'CreateUserTable') {
            $appPath = $this->checkAppPath($argv[1]);
        }
        if (!empty($argv[1]) && $argv[1] == 'CreateUserTable' && !empty($argv[2])) {
            $appPath = $this->checkAppPath($argv[2]);
        }
        if (!$appPath) {
            while (true) {
                Toknot\Core\Log::colorMessage('Enter path of app path:', null, false);
                $appPath = trim(fgets(STDIN));
                if (!empty($appPath)) {
                    $appPath = $this->checkAppPath($appPath);
                    if ($appPath) {
                        break;
                    }
                }
            }
        }
        
        $cfg = Toknot\Config\ConfigLoader::CFG();
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
        Toknot\Core\Log::colorMessage('create user table success', 'green');
    }

    public function checkAppPath($file) {
        $config = realpath($file);
        if ($config) {
            return $config;
        }
        Toknot\Core\Log::colorMessage("$file not exits", 'red');
        return false;
    }

    public function activeRecord($cfg) {
        Toknot\Core\Autoloader::importToknotModule('Db', 'DbCRUD');
        $ar = Toknot\Db\ActiveRecord::singleton();
        $ar->config($cfg->Database);
        return $ar->connect();
    }

}

