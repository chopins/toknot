#!/bin/env php
<?php
/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Tool;
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
		require_once $this->toknotDir . '/Control/Application.php';
		define('DEVELOPMENT', false);
		new Toknot\Control\Application;
		$this->versionInfo();

		$this->message("Whether create to current path yes/no(default:no):", null, false);
		$isCurrent = trim(fgets(STDIN));
		$dir = $this->createAppRootDir($isCurrent);
		$this->message('Whether admin of applicaton yes/no(default:no):', null, false);
		$admin = trim(fgets(STDIN));
		if ($admin == 'yes') {
			$this->isAdmin = true;
			while (($password = $this->enterRootPass()) === false) {
				$this->message('Twice password not same, enter again:', 'red');
			}

			\Toknot\Control\StandardAutoloader::importToknotModule('User', 'UserAccessControl');
			$this->message('Generate hash salt');
			$salt = substr(str_shuffle('1234567890qwertyuiopasdfghjklzxcvbnm'), 0, 8);
			$algo = Toknot\User\Root::bestHashAlgos();
			$password = Toknot\User\Root::getTextHashCleanSalt($password, $algo, $salt);
			$this->message('Generate Root password hash string');
		}

		while (file_exists($dir)) {
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
		$this->message("Create $dir/View");
		mkdir($dir . '/View');
		if ($this->isAdmin) {
			mkdir($dir.'/Controller/User');
			$this->message("Create $dir/Controller/User");
			$this->writeAdminAppUserController($dir.'/Controller/User');
			$this->copyDir($this->toknotDir . '/Admin/View', $dir . '/View');
			$this->copyDir($this->toknotDir . '/Admin/Static', $dir . '/WebRoot/static');
		}
		$this->message("Create $dir/Data/View");
		mkdir($dir . '/Data/View', 0777, true);

		$this->message("Create $dir/Data/View/Compile");
		mkdir($dir . '/Data/View/Compile', 0777, true);

		$this->message('Create Success', 'green');
		$this->message('You should configure ' . $dir . '/Config/config.ini');
		$this->message("Configure your web root to $dir/WebRoot and visit your Application on browser");
	}

	public function versionInfo() {
		$this->message('Toknot Framework Application Create Script');
		$this->message('Toknot ' . \Toknot\Di\Version::VERSION . '-' . \Toknot\Di\Version::STATUS . ';PHP ' . PHP_VERSION);
		$this->message('Copyright (c) 2010-2013 Szopen Xiao');
		$this->message('New BSD Licenses <http://toknot.com/LICENSE.txt>');
		$this->message('');
	}

	public function enterRootPass() {
		$this->message('Enter root password:', null, false);
		$password = trim(fgets(STDIN));
		while (strlen($password) < 6) {
			$this->message('root password too short,enter again:', 'red', false);
			$password = trim(fgets(STDIN));
		}
		$this->message('Enter root password again:', null, false);
		$repassword = trim(fgets(STDIN));
		while (empty($password)) {
			$this->message('must enter root password again:', 'red', false);
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
				$this->message("Enter application root namespace name:", null, false);
				$topnamespace = trim(fgets(STDIN));
			}
			$dir = $this->workDir . '/' . $topnamespace;
		} else {
			$this->message("Enter application path, the basename is root namespace name:", null, false);
			$dir = trim(fgets(STDIN));
			while (empty($dir)) {
				$this->message("must enter application path: ", null, false);
				$dir = trim(fgets(STDIN));
			}
		}
		if (file_exists($dir)) {
			$this->message('Path (' . $dir . ') is exists, change other path', 'red');
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
		$use = $this->isAdmin ? 'Toknot\Admin\Admin' : "{$this->appName}\{$this->appName}";
		$base = $this->isAdmin ? 'AdminBase' : "{$this->appName}Base";
		$phpCode = <<<EOS
<?php
namespace  {$this->appName}\Controller;
            
use {$use}Base;

class Index extends {$base}{
EOS;
		$phpCode .= <<<'EOS'
 	
    protected $permissions = 0770;

    public function GET() {
        //$database = $this->AR->connect();
        print "hello world";

        //self::$FMAI->display('index');
    }
 }
EOS;
		$this->message("Create $path/Index.php");
		file_put_contents("$path/Index.php", $phpCode);
	}

	public function writeAppBaseClass($path) {
		$phpCode = <<<EOS
<?php
namespace ' . $this->appName . ';
use Toknot\User\ClassAccessControl;

class ' . $this->appName . 'Base extends ClassAccessControl {
EOS;
		$phpCode .= <<<'EOS'
    protected static $FMAI;
    protected static $CFG;
    protected $AppPath;
    protected $AR;
    protected $view;
    protected $permissions;
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

}
EOS;
		$this->message("Create $path/{$this->appName}Base.php");
		file_put_contents("$path/{$this->appName}Base.php", $phpCode);
	}

	public function writeIndex($path) {
		$toknot = dirname(__DIR__) . '/Control/Application.php';
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

	public function message($str, $color = null, $newLine = true) {
		$number = FALSE;
		switch ($color) {
			case 'red':
				$number = 31;
				break;
			case 'green':
				$number = 32;
				break;
			case 'blue':
				$number = 44;
				break;
			case 'yellow':
				$number = 43;
				break;
		}
		if ($number) {
			echo "\e[1;{$number}m";
		}
		echo "$str";
		if ($newLine) {
			echo "\r\n";
		}
		if ($number) {
			echo "\e[0m";
		}
	}

}

return new CreateApp();
?>
