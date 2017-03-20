<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2017 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */
use Toknot\Boot\Kernel;
use Toknot\Share\CommandLine;
use Toknot\Boot\Tookit;
use Toknot\Boot\Logs;

class InitApp {

    public $cmd;
    public $appPath = '';
    public $appNS = '';

    public function __construct($argc, $argv) {
        define('TKROOT', __DIR__);
        include __DIR__ . '/Toknot/Boot/Object.php';
        include __DIR__ . "/Toknot/Boot/Kernel.php";
        Kernel::single($argc, $argv);
        $this->cmd = new CommandLine;
        $this->cmd->message('App Init Guide 0.1');
        $this->cmd->message('Copyright (c) 2011-2017 Toknot.com');
        $this->cmd->message('( Ctrl+C ) Quit Guide');
        $this->cmd->freadline('set your app path :', '', array($this, 'checkPath'), Logs::COLOR_GREEN | Logs::SET_BOLD);
        $this->createAppDir();
        $this->gindex();
        $this->gmainConfig();
    }

    public function createAppDir() {
        $appName = basename($this->appPath);
        $this->appNS = ucwords($appName);
        $app = "{$this->appPath}/" . $this->appNS;
        mkdir($app, 0755);
        mkdir("{$app}/Controller", 0755);
        mkdir("{$app}/Middleware", 0755);
        mkdir("{$app}/Model", 0755);
        mkdir("{$app}/View", 0755);

        mkdir("{$this->appPath}/config", 0755);
        mkdir("{$this->appPath}/runtime", 0755);
    }

    public function gindex() {
        $this->cmd->message('Generate webroot index file......');
        $boot = __DIR__ . '/boot.php';
        $index = "{$this->appPath}/index.php";
        $code = <<<EOF
<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2017 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

include '$boot';
main('$this->appPath');
EOF;
        file_put_contents($index, $code);
        $this->cmd->message('Generate webroot index file success');
        $this->cmd->message("copy file $index to your webroot directory", 'green|bold');
    }

    public function gmainConfig() {
        $this->cmd->message('Generate main config file......');

        $code = <<<EOF
;
; Toknot (http://toknot.com)
;
; @copyright  Copyright (c) 2011 - 2017 Toknot.com
; @license    http://toknot.com/LICENSE.txt New BSD License
; @link       https://github.com/chopins/toknot
;
[app]
trace = true
timezone = UTC
charet = utf8
app_ns={$this->appNS}
ctl_ns=Controller
model_ns=Model
middleware_ns=Middleware
service_ns=
view_ns= View
router = Toknot\Share\Router
default_db_config_key = 
short_except_path = true
model_dir = runtime/model
default_layout = 

;session config see http://php.net/session.configuration
session.table = session
session.name = sid
session.cookie_httponly = 1
log.enable = false
log.logger = runtime/logs/trace
;log.logger = APP\Logger        
[vendor]
dbal = doctrine/Doctrine
routing = symfony/Symfony
phpdoc = zend/Zend
EOF;
        file_put_contents("{$this->appPath}/config/config.ini", $code);
        $this->cmd->message('Generate main config file success');
        $this->cmd->message('Generate router config file......');
        $route = <<<EOF
;
; Toknot (http://toknot.com)
;
; @copyright  Copyright (c) 2011 - 2017 Toknot.com
; @license    http://toknot.com/LICENSE.txt New BSD License
; @link       https://github.com/chopins/toknot
;

[test-rooter]
prefix.path = '/p'
prefix.controller = 
path = /foo
controller = MyController::test
method = GET
require.id = [0-9]{9-12}
require.subdomain = www
options = 
schemes = 
host = 
EOF;
        file_put_contents("{$this->appPath}/config/router.ini", $route);
        $this->cmd->message('Generate router config file success');
    }

    public function checkPath($path) {
        $this->appPath = Tookit::getRealPath($path);
        if (file_exists($this->appPath)) {
            $msg = 'path is exist, whether enter new path (y/n,default n):';
            $ask = $this->cmd->readline($msg);
            if ($ask == 'y') {
                return -1;
            }
            return $this->appPath = realpath($this->appPath);
        }

        $ret = mkdir($this->appPath, 0755, true);
        if (!$ret) {
            $msg = 'dir create failure,whether enter new path (y/n,default y):';
            $ask = $this->cmd->readline($msg);
            if ($ask != 'y') {
                exit;
            }
            return -1;
        }
        $this->appPath = realpath($this->appPath);
        $this->cmd->message("Your app path is: $this->appPath", 'green|bold');
    }

}

new InitApp($argc, $argv);
