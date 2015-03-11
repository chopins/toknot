<?php
/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2015 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

/**
 * Generate password of encriyption string
 */
class GeneratePassword {

    public function __construct($argv) {
        $this->toknotDir = dirname(__DIR__);
        $this->workDir = getcwd();
        $config = false;
        
        if(!empty($argv[1]) && $argv[1] != 'GeneratePassword') {
            $config = $this->checkIni($argv[1]);
        }
        if(!empty($argv[1]) && $argv[1] == 'GeneratePassword' && !empty($argv[2])) {
            $config = $this->checkIni($argv[2]);
        }
        if (!$config) {
            while (true) {
                Toknot\Boot\Log::colorMessage('Enter path of config file:', null, false);
                $config = trim(fgets(STDIN));
                if (!empty($config)) {
                    $config = $this->checkIni($config);
                    if($config) {
                        break;
                    }
                }
            }
        }

        while (($password = $this->enterPass()) === false) {
            Toknot\Boot\Log::colorMessage('Twice password not same, enter again:', 'red');
        }
        Toknot\Config\ConfigLoader::singleton();
        $cfg = Toknot\Config\ConfigLoader::importCfg($config);

        if (empty($cfg->User->userPasswordEncriyptionAlgorithms)) {
            Toknot\Boot\Log::colorMessage('config of Algorithm is empty, must set in config.ini', 'red');
            return;
        }
        if (empty($cfg->User->userPasswordEncriyptionSalt)) {
            Toknot\Boot\Log::colorMessage('config of salt is empty,must set in config.ini', 'red');
            return;
        }
  
         \Toknot\Boot\StandardAutoloader::importToknotModule('User', 'UserAccessControl');
        $password = Toknot\Share\User\Root::getTextHashCleanSalt($password, $cfg->User->userPasswordEncriyptionAlgorithms, $cfg->User->userPasswordEncriyptionSalt);
        Toknot\Boot\Log::colorMessage('Set Root Password is below string in config.ini','green');
        Toknot\Boot\Log::colorMessage($password,'green');
    }

    public function checkIni($file) {
        $config = realpath($file);
        if ($config) {
            return $config;
        }
        Toknot\Boot\Log::colorMessage("$file not exits", 'red');
        return false;
    }

    public function enterPass() {
        Toknot\Boot\Log::colorMessage('Enter password:', null, false);
        $password = trim(fgets(STDIN));
        while (strlen($password) < 6) {
            Toknot\Boot\Log::colorMessage('password too short,enter again:', 'red', false);
            $password = trim(fgets(STDIN));
        }
        Toknot\Boot\Log::colorMessage('Enter password again:', null, false);
        $repassword = trim(fgets(STDIN));
        while (empty($password)) {
            Toknot\Boot\Log::colorMessage('must enter password again:', 'red', false);
            $repassword = trim(fgets(STDIN));
        }
        if ($repassword != $password) {
            return false;
        } else {
            return $password;
        }
    }

}
