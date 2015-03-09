#!/bin/env php
<?php
/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

/**
 * Create a application, the script is a guide that help you create a application
 * of base directory struncture and create some code of php
 * just run the script, like : php CreateApp.php
 */
class CreateApp {

    public $workDir = '';
    public $appName = '';
    public $isAdmin = false;
    public $toknotDir = '';

    public function __construct() {
        $this->toknotDir = dirname(__DIR__);
        $this->workDir = getcwd();
        define('DEVELOPMENT', false);
        $this->versionInfo();

        Toknot\Core\Log::colorMessage("Whether create to current path yes/no(default:no):", null, false);
        $isCurrent = trim(fgets(STDIN));
        $dir = $this->createAppRootDir($isCurrent);
        Toknot\Core\Log::colorMessage('Whether admin of applicaton yes/no(default:no):', null, false);
        $admin = trim(fgets(STDIN));
        if ($admin == 'yes') {
            $this->isAdmin = true;
            while (($password = $this->enterRootPass()) === false) {
                Toknot\Core\Log::colorMessage('Twice password not same, enter again:', 'red');
            }

            \Toknot\Control\StandardAutoloader::importToknotModule('User', 'UserAccessControl');
            Toknot\Core\Log::colorMessage('Generate hash salt');
            $salt = substr(str_shuffle('1234567890qwertyuiopasdfghjklzxcvbnm'), 0, 8);
            $algo = Toknot\Lib\User\Root::bestHashAlgos();
            $password = Toknot\Lib\User\Root::getTextHashCleanSalt($password, $algo, $salt);
            Toknot\Core\Log::colorMessage('Generate Root password hash string');
        }

        while (file_exists($dir)) {
            Toknot\Core\Log::colorMessage("$dir is exists, change other");
            $dir = $this->createAppRootDir($isCurrent);
        }
        Toknot\Core\Log::colorMessage("Create $dir");
        $res = mkdir($dir, 0777, true);
        if ($res === false) {
            return Toknot\Core\Log::colorMessage("$dir create fail");
        }
        $dir = realpath($dir);
        $this->appName = basename($dir);

        Toknot\Core\Log::colorMessage("Create $dir/Controller");
        mkdir($dir . '/Controller');
        $this->writeIndexController($dir . '/Controller');

        Toknot\Core\Log::colorMessage("Create $dir/WebRoot");
        mkdir($dir . '/WebRoot');

        Toknot\Core\Log::colorMessage("Create $dir/Config");
        mkdir($dir . '/Config');

        Toknot\Core\Log::colorMessage("Create $dir/Config/config.ini");

        $configure = file_get_contents($this->toknotDir . '/Config/default.ini');
        $configure = str_replace(array(";DO NOT EDIT THIS FILE !!!\n", ";EDIT APP OF CONFIG.INI INSTEAD !!!\n"), '', $configure);
        if ($this->isAdmin) {
            $configure = preg_replace('/(allowRootLogin\040*)=(.*)$/im', "$1= true", $configure);
            $configure = preg_replace('/(rootPassword\040*)=(.*)$/im', "$1={$password}", $configure);
            $configure = preg_replace('/(userPasswordEncriyptionAlgorithms\040*)=(.*)$/im', "$1={$algo}", $configure);
            $configure = preg_replace('/(userPasswordEncriyptionSalt\040*)=(.*)$/im', "$1={$salt}", $configure);
        }
        file_put_contents($dir . '/Config/config.ini', $configure);

        $this->writeIndex($dir . '/WebRoot');
        if (!$this->isAdmin) {
            $this->writeAppBaseClass($dir);
        }
        Toknot\Core\Log::colorMessage("Create $dir/View");
        mkdir($dir . '/View');
        if ($this->isAdmin) {
            mkdir($dir . '/Controller/User');
            Toknot\Core\Log::colorMessage("Create $dir/Controller/User");
            $this->writeAdminAppUserController($dir . '/Controller/User');
            $this->copyDir($this->toknotDir . '/Admin/View', $dir . '/View');
            $this->copyDir($this->toknotDir . '/Admin/Static', $dir . '/WebRoot/static');
            $this->writeManageListConfig($dir);
        }
        Toknot\Core\Log::colorMessage("Create $dir/Data/View");
        mkdir($dir . '/Data/View', 0777, true);

        Toknot\Core\Log::colorMessage("Create $dir/Data/View/Compile");
        mkdir($dir . '/Data/View/Compile', 0777, true);

        Toknot\Core\Log::colorMessage('Create Success', 'green');
        Toknot\Core\Log::colorMessage('You should configure ' . $dir . '/Config/config.ini');
        Toknot\Core\Log::colorMessage("Configure your web root to $dir/WebRoot and visit your Application on browser");
    }

    public function writeManageListConfig($dir) {
        $configure = <<<EOF
; this is manage list configure of Toknot Admin

;one section is a manage category
[User]

;category name
name = UserManage

;wheteher has sub item
hassub = true

;the category name whether has action jump
action = false

;sub is the category child item list
;one item contain action and show name and use | split
sub[] = 'UserList'
sub[] = 'AddUser'

[UserList]
name = UserList
hassub = false
action = User\Lists
                
[AddUser]
name = Add User
hassub = false
action = User\Add

EOF;
        file_put_contents($dir . '/Config/navigation.ini', $configure);
    }

    public function versionInfo() {
        Toknot\Core\Log::colorMessage('Toknot Framework Application Create Script');
        Toknot\Core\Log::colorMessage('Toknot ' . \Toknot\Core\Version::VERSION . '-' . \Toknot\Core\Version::STATUS . ';PHP ' . PHP_VERSION);
        Toknot\Core\Log::colorMessage('Copyright (c) 2010-2013 Szopen Xiao');
        Toknot\Core\Log::colorMessage('New BSD Licenses <http://toknot.com/LICENSE.txt>');
        Toknot\Core\Log::colorMessage('');
    }

    public function enterRootPass() {
        Toknot\Core\Log::colorMessage('Enter root password:', null, false);
        $password = trim(fgets(STDIN));
        while (strlen($password) < 6) {
            Toknot\Core\Log::colorMessage('root password too short,enter again:', 'red', false);
            $password = trim(fgets(STDIN));
        }
        Toknot\Core\Log::colorMessage('Enter root password again:', null, false);
        $repassword = trim(fgets(STDIN));
        while (empty($password)) {
            Toknot\Core\Log::colorMessage('must enter root password again:', 'red', false);
            $repassword = trim(fgets(STDIN));
        }
        if ($repassword != $password) {
            return false;
        } else {
            return $password;
        }
    }

    public function writeAdminAppUserController($path) {
        $phpCode = <<<EOS
<?php
namespace {$this->appName}\Controller\User;

use Toknot\Admin\Login as AdminLogin;

class Login extends AdminLogin {
}
EOS;
        file_put_contents($path . '/Login.php', $phpCode);
        $phpCode = <<<EOS
<?php
namespace {$this->appName}\Controller\User;
use Toknot\Admin\Logout;
class Logout extends Logout {
}
EOS;
        file_put_contents("$path/Logout.php", $phpCode);
    }

    public function createAppRootDir($isCurrent) {
        if ($isCurrent == 'yes') {
            $topnamespace = '';
            while (empty($topnamespace)) {
                Toknot\Core\Log::colorMessage("Enter application root namespace name:", null, false);
                $topnamespace = trim(fgets(STDIN));
            }
            $dir = $this->workDir . '/' . $topnamespace;
        } else {
            Toknot\Core\Log::colorMessage("Enter application path, the basename is root namespace name:", null, false);
            $dir = trim(fgets(STDIN));
            while (empty($dir)) {
                Toknot\Core\Log::colorMessage("must enter application path: ", null, false);
                $dir = trim(fgets(STDIN));
            }
        }
        if (file_exists($dir)) {
            Toknot\Core\Log::colorMessage('Path (' . $dir . ') is exists, change other path', 'red');
            $this->createAppRootDir($isCurrent);
        }
        return $dir;
    }

    public function copyDir($source, $dest) {
        if (is_file($source)) {
            return copy($source, $dest);
        } else if (is_dir($source)) {
            $dir = dir($source);
            if (is_file($dest)) {
                return Toknot\Core\Log::colorMessage($dest . ' is exist file');
            }
            if (!is_dir($dest)) {
                mkdir($dest, 0777, true);
            }
            while (false !== ($f = $dir->read())) {
                if ($f == '.' || $f == '..') {
                    continue;
                }
                $file = $source . '/' . $f;
                Toknot\Core\Log::colorMessage("copy $file");
                $destfile = $dest . '/' . $f;
                if (is_dir($file)) {
                    $this->copyDir($file, $destfile);
                } else {
                    copy($file, $destfile);
                }
            }
        }
    }

    public function writeIndexController($path) {
        $use = $this->isAdmin ? 'Toknot\Admin\Admin' : "{$this->appName}\\{$this->appName}";
        $base = $this->isAdmin ? 'AdminBase' : "{$this->appName}Base";
        $phpCode = <<<EOS
<?php
namespace  {$this->appName}\Controller;
            
use {$use}Base;

EOS;
        if ($this->isAdmin) {
            $phpCode .= 'use Toknot\Admin\Menu;';
        }
        $phpCode .= <<<EOS
class Index extends {$base}{
EOS;
        $phpCode .= <<<'EOS'
     
    protected $permissions = 0770;
    protected $gid = 0;
    protected $uid = 0;
    protected $operateType = 'r';
    public function GET() {
        //$database = $this->AR->connect();
        print "hello world";
EOS;
        if ($this->isAdmin) {
            $phpCode .= <<<'EOS'
        $menu = new Menu;
        //self::$FMAI->D->navList = $menu->getAllMenu();
        $this->D->navList = $menu->getAllMenu();
        
        //self::$FMAI->D->act = 'list';
        $this->D->act = 'list';

        //self::$FMAI->display('index');
        $this->display('index');
EOS;
        }
        $phpCode .= <<<'EOS'
        //self::$FMAI->display('index');
    }
 }
EOS;
        Toknot\Core\Log::colorMessage("Create $path/Index.php");
        file_put_contents("$path/Index.php", $phpCode);
    }

    public function writeAppBaseClass($path) {
        $phpCode = <<<EOS
<?php
namespace {$this->appName};
use Toknot\Lib\User\ClassAccessControl;
use Toknot\Lib\User\Nobody;
class {$this->appName}Base extends ClassAccessControl {
EOS;
        $phpCode .= <<<'EOS'

    //protected static $FMAI;
    protected static $CFG;
    protected $AppPath;
    protected $AR;
    protected $view;
    protected $permissions = 0777;
    protected $operateType = 'r';
    protected $gid =0;
    protected $uid =0;
    public function __init($FMAI) {
       
        //$this->AR = self::$FMAI->getActiveRecord(); 
        $this->AR = $this->getActiveRecord();

        //$this->AR->config(self::$CFG->Database);
        
        //self::$FMAI->enableHTMLCache(self::$CFG->View);
        
        //$this->view = self::$FMAI->newTemplateView(self::$CFG->View);
        
        //$this->checkAccess($this, new Nobody());
        $FMAI->checkAccess($this, new Nobody());
    }

    public function CLI() {
        $this->GET();
    }

}
EOS;
        Toknot\Core\Log::colorMessage("Create $path/{$this->appName}Base.php");
        file_put_contents("$path/{$this->appName}Base.php", $phpCode);
    }

    public function writeIndex($path) {
        $toknot = dirname(__DIR__) . '/Toknot.php';
        $namespace = '\\' . $this->appName;
        $phpCode = '<?php
use Toknot\Control\Application;
//use Toknot\Control\Router;

//If developement set true, product set false
define(\'DEVELOPMENT\', true);
require_once "' . $toknot . '";

$app = new Application;

/**
the first paramter of function what is router mode that value maybe is below:
Router::ROUTER_PATH         is default, the path similar class full name with namespace
                            the URI un-match-part use FMAI::getParam() which pass
                            index of order
Router::ROUTER_GET_QUERY    is router use $_GET[\'r\']
Router::ROUTER_MAP_TABLE    is use Config/router_map.ini, the file is ini configure
                            key is pattern, value is class full name with namespace
                            use FMAI::getParam() get match sub
NOTE: if you set value here and different config.ini will use config.ini set value
*/
//$app->setRouterArgs(Router::ROUTER_PATH, 2);
$app->run("' . $namespace . '",dirname(__DIR__));';

        Toknot\Core\Log::colorMessage("Create $path/index.php");
        file_put_contents($path . '/index.php', $phpCode);
    }

}

?>
