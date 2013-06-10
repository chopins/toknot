<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @link       https://github.com/chopins/toknot
 */
class CreateApp {

    public $workDir = '';
    public $appName = '';
    public $isAdmin = false;
    public $toknotDir = '';

    public function __construct() {
        $this->toknotDir = dirname(__DIR__);
        $this->workDir = getcwd();
        $this->message("Whether current path yes/no(default:no)");
        $isCurrent = trim(fgets(STDIN));
        $dir = $this->createAppRootDir($isCurrent);
        $this->message('Whether admin of applicaton yes/no(default:no):');
        $admin = trim(fgets(STDIN));
        if ($admin == 'yes') {
            $this->isAdmin = true;
            while (($password = $this->enterRootPass()) === false) {
                $this->message('Twice password not same, enter again:');
            }
            include_once $this->toknotDir . '/Control/Application.php';
            $app = new Toknot\Control\Application;
            \Toknot\Control\StandardAutoloader::importToknotModule('User', 'UserControl');
            $encrypt = Toknot\User\Root::getEncryptionRootPass($password);
            
        }

        while(file_exists($dir)) {
            $this->message("$dir is exists, change other");
            $dir = $this->createAppRootDir($isCurrent);
        }
        $this->message("Create $dir");
        $res = mkdir($dir, 0777, true);
        if ($res === false) {
            return $this->message("$dir create fail");
        }
        $dir = realpath($dir);
        $this->appName = basename($dir);

        $this->message("Create $dir/Controller");
        mkdir($dir . '/Controller');
        $this->writeIndexController($dir . '/Controller');

        $this->message("Create $dir/WebRoot");
        mkdir($dir . '/WebRoot');

        $this->message("Create $dir/Config");
        mkdir($dir . '/Config');

        $this->message("Create $dir/Config/config.ini");

        $configure = file_get_contents($this->toknotDir . '/Config/default.ini');
        if ($this->isAdmin) {
            $configure = preg_replace('/(allowRootLogin\s*)=(.*)$/im', "$1= true", $configure);
            $configure = preg_replace('/(rootPassword\s*)=(.*)$/im', "$1={$encrypt[1]}", $configure);
            $configure = preg_replace('/(rootEncriyptionAlgorithms\s*)=(.*)$/im', "$1={$encrypt[0]}", $configure);
        }
        file_put_contents($dir . '/Config/config.ini', $configure);

        $this->writeIndex($dir . '/WebRoot');
        if (!$this->isAdmin) {
            $this->writeAppBaseClass($dir);
        }
        $this->message("Create $dir/View");
        mkdir($dir . '/View');
        if ($this->isAdmin) {
            $this->copyDir($this->toknotDir . '/Admin/View', $dir . '/View');
            $this->copyDir($this->toknotDir . '/Admin/Static', $dir . '/WebRoot/Static');
        }
        $this->message("Create $dir/Data/View");
        mkdir($dir . '/Data/View', 0777, true);

        $this->message("Create $dir/Data/View/Compile");
        mkdir($dir . '/Data/View/Compile', 0777, true);

        $this->message('Create Success');
        $this->message("Configure your web root to $dir/WebRoot and visit your Application on browser");
    }

    public function enterRootPass() {
        $this->message('Enter root password:');
        $password = trim(fgets(STDIN));
        while (strlen($password) < 6) {
            $this->message('root password too short,enter again:');
            $password = trim(fgets(STDIN));
        }
        $this->message('Enter root password again:');
        $repassword = trim(fgets(STDIN));
        while (empty($password)) {
            $this->message('must enter root password again:');
            $repassword = trim(fgets(STDIN));
        }
        if ($repassword != $password) {
            return false;
        } else {
            return $password;
        }
    }

    public function createAppRootDir($isCurrent) {
        if ($isCurrent == 'yes') {
            $this->message("Enter application root namespace name:");
            $topnamespace = '';
            while (empty($topnamespace)) {
                $topnamespace = trim(fgets(STDIN));
            }
            $dir = $this->workDir . '/' . $topnamespace;
        } else {
            $this->message("Enter application path, the basename is root namespace name:");
            $dir = trim(fgets(STDIN));
            while (empty($dir)) {
                $this->message("must enter application path:");
                $dir = trim(fgets(STDIN));
            }
        }
        return $dir;
    }

    public function copyDir($source, $dest) {
        if (is_file($source)) {
            return copy($source, $dest);
        } else if (is_dir($source)) {
            $dir = dir($source);
            if (is_file($dest)) {
                return $this->message($dest . ' is exist file');
            }
            if (!is_dir($dest)) {
                mkdir($dest, 0777, true);
            }
            while (false !== ($f = $dir->read())) {
                if ($f == '.' || $f == '..') {
                    continue;
                }
                $file = $source . '/' . $f;
                $this->message($file);
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
        $use = $this->isAdmin ? 'Toknot\Admin' : "{$this->appName}\{$this->appName}Base";
        $base = $this->isAdmin ? 'AdminBase' : "{$this->appName}Base";
        $phpCode = '<?php
namespace ' . $this->appName . '\Controller;
            
use ' . $use . 'Base;

class Index extends ' . $base . '{
    public $perms = 0777;

    public function GET() {
        //$database = $this->AR->connect();
        print "hello world";

        //$this->display(\'index\');
    }';
        $this->message("Create $path/Index.php");
        file_put_contents("$path/Index.php", $phpCode);
    }

    public function writeAppBaseClass($path) {
        $phpCode = '<?php
namespace ' . $this->appName . ';
use Toknot\User\ClassUserControl;

class ' . $this->appName . 'Base extends ClassUserControl {

    protected $FMAI;
    protected $CFG;
    protected $AppPath;
    protected $AR;
    protected $view;
    protected $prems;
    protected $classGroup;
    public function __construct($FMAI) {
        $this->FMAI = $FMAI;
        $this->CFG = $this->FMAI->loadConfigure($FMAI->appRoot . \'/Config/config.ini\');
        
        $this->AR = $this->FMAI->getActiveRecord();

        //$this->AR->config($this->CFG->Database);
        
        $this->FMAI->enableHTMLCache();
        
        $this->view = $this->FMAI->newTemplateView($this->CFG->View);

        $FMAI->checkAccess($this);
    }

    public function CLI() {
        $this->GET();
    }

}';
        $this->message("Create $path/{$this->appName}Base.php");
        file_put_contents("$path/{$this->appName}Base.php", $phpCode);
    }

    public function writeIndex($path) {
        $toknot = dirname(__DIR__) . '/Application.php';
        $namespace = '\\' . $this->appName;
        $phpCode = '<?php
use Toknot\Control\Application;
use Toknot\Control\Router;

//If developement set true, product set false
define(\'DEVELOPMENT\', true);
require_once "' . $toknot . '";

$app = new Application;
$app->setRouterArgs(Router::ROUTER_PATH, 2);
$app->run("' . $namespace . '",dirname(__DIR__));';
        $this->message("Create $path/index.php");
        file_put_contents($path . '/index.php', $phpCode);
    }

    public function message($str) {
        echo "$str\r\n";
    }

}

return new CreateApp();
?>
