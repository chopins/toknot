<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2018 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Share\AppGenerator;
use Toknot\Boot\Kernel;
use Toknot\Share\CommandLine;
use Toknot\Boot\Logs;

class AppGenerator {

    public function __construct($argc, $argv) {
        define('TOKNOT_DIR', __DIR__);
        define('GENERATOR_DIR', __DIR__);
        include_once __DIR__ . '/Toknot/Boot/Tookit.php';
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

    public function replaceTpl($file, $var) {
        $content = file_get_contents(GENERATOR_DIR . "/templates/{$file}.tpl");
        $keys = array_keys($var);
        return str_replace($keys, $var, $content);
    }

    public function gindex() {
        $this->cmd->message('Generate webroot index file......');
        $boot = __DIR__ . '/boot.php';
        $index = "{$this->appPath}/index.php";
        $code = $this->replaceTpl('index', array('{{boot}}' => $boot, '{{appPath}}' => $this->appPath));

        file_put_contents($index, $code);
        $this->cmd->message('Generate webroot index file success');
        $this->cmd->message("copy file $index to your webroot directory", 'green|bold');
    }

    public function gmainConfig() {
        $this->cmd->message('Generate main config file......');

        $code = $this->replaceTpl('config.ini', array('{{appNS}}' => $this->appNS));

        file_put_contents("{$this->appPath}/config/config.ini", $code);
        $this->cmd->message('Generate main config file success');
        $this->cmd->message('Generate router config file......');
        $route = file_get_contents(GENERATOR_DIR . '/templates/route.ini.tpl');
        file_put_contents("{$this->appPath}/config/router.ini", $route);
        $this->cmd->message('Generate router config file success');
    }

    public function checkPath($path) {
        $this->appPath = Kernel::getRealPath($path);
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
