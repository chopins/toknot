<?php
/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2015 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Command;

use Toknot\Boot\Log;
use Toknot\Boot\Autoloader;
use Toknot\Share\User\Root;
use Toknot\Config\ConfigLoader;

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
                Log::colorMessage('Enter path of config file:', null, false);
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
            Log::colorMessage('Twice password not same, enter again:', 'red');
        }
        ConfigLoader::singleton();
        $cfg = ConfigLoader::importCfg($config);

        if (empty($cfg->User->userPasswordEncriyptionAlgorithms)) {
            Log::colorMessage('config of Algorithm is empty, must set in config.ini', 'red');
            return;
        }
        if (empty($cfg->User->userPasswordEncriyptionSalt)) {
            Log::colorMessage('config of salt is empty,must set in config.ini', 'red');
            return;
        }
  
        Autoloader::importToknotModule('Share\User', 'UserAccessControl');
        $password = Root::getTextHashCleanSalt($password, $cfg->User->userPasswordEncriyptionAlgorithms, $cfg->User->userPasswordEncriyptionSalt);
        Log::colorMessage('Set Root Password is below string in config.ini','green');
        Log::colorMessage($password,'green');
    }

    public function checkIni($file) {
        $config = realpath($file);
        if ($config) {
            return $config;
        }
        Log::colorMessage("$file not exits", 'red');
        return false;
    }

    public function enterPass() {
        Log::colorMessage('Enter password:', null, false);
        $password = trim(fgets(STDIN));
        while (strlen($password) < 6) {
            Log::colorMessage('password too short,enter again:', 'red', false);
            $password = trim(fgets(STDIN));
        }
        Log::colorMessage('Enter password again:', null, false);
        $repassword = trim(fgets(STDIN));
        while (empty($password)) {
            Log::colorMessage('must enter password again:', 'red', false);
            $repassword = trim(fgets(STDIN));
        }
        if ($repassword != $password) {
            return false;
        } else {
            return $password;
        }
    }

}
