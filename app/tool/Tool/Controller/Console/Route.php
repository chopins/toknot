<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2017 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Tool\Controller\Console;

use Toknot\Boot\Kernel;
use Toknot\Share\CommandLine;
use Toknot\Boot\Import;
use Zend\Reflection\Docblock;
use Toknot\Boot\Tookit;

/**
 * Route
 *
 */
class Route {

    public $appNs;
    private $confgType = 'ini';

    /**
     * generate route ini based on controller class
     * 
     * -a app path
     * -o output file
     * -t generate config type,yml|ini
     * 
     * @console route
     */
    public function __construct() {
        $path = Kernel::single()->getOption('-a');
        $output = Kernel::single()->getOption('-o');
        $confgType = Kernel::single()->getOption('-t');
        if ($confgType == 'yml') {
            $this->confgType = 'yml';
        }
        $cmd = new CommandLine;
        if (empty($path) || !is_dir($path)) {
            $cmd->error("must give app dir");
        }
        $apppath = realpath($path);
        $appTopNs = ucwords(basename($apppath));
        $dir = $apppath . DIRECTORY_SEPARATOR . $appTopNs;
        Import::addPath($dir);
        $this->appNs = $appTopNs . PHP_NS . 'Controller' . PHP_NS;
        $ini = $this->dir($dir, $this->appNs);
        $output = $output ? $output : "$apppath/config.router.ini";
        file_put_contents($output, $ini);
    }

    public function dir($dir, $appNs) {
        $path = Import::transformNamespaceToPath($appNs, $dir);

        $ini = '';
        if ($this->confgType == 'yml') {
            $configTpl = <<<EOF
%s :
    path : %s
    controller : %s%s
    method : %s
EOF;
        } else {
            $configTpl = <<<EOF
[%s]
path = %s
controller = %s%s
method = %s
EOF;
        }
        $configTpl .= PHP_EOL;
 
        Tookit::dirWalk($path, function($file) use($appNs, $configTpl, &$ini) {
            $class = $appNs . basename($file, '.php');

            $rf = new \ReflectionClass($class);
            $ms = $rf->getMethods();
            $rm = 'GET';
            foreach ($ms as $m) {
                $routepath = $this->parseDocComment($m, $rm);
                if ($routepath !== false) {
                    $method = $m->getName();
                    $cls = str_replace($this->appNs, '', $class);
                    $routepath = $routepath ? $routepath : strtolower('/' . str_replace(PHP_NS, '/', $cls));
                    $cont = str_replace(PHP_NS, '.', $cls);
                    $routename = strtolower(str_replace(PHP_NS, '-', $cls));
                    $mp = $m->isConstructor() || $m->isDestructor() ? '' : ":$method";
                    $ini .= sprintf($configTpl, $routename, $routepath, $cont, $mp, $rm);
                }
            }
        });

        return $ini;
    }

    /**
     * parse code comment, the method will get @console tag and short and long description
     * 
     * @param array $message
     * @param string $docs
     * @param int $maxlength
     * @return boolean|string
     */
    public function parseDocComment($m, &$method = 'GET') {
        $docs = $m->getDocComment();
        $tagType = ['GET' => 'route', 'CLI' => 'console', 'POST' => 'post', 'GET' => 'get'];
        if ($docs) {
            $docblock = new Docblock($docs);
            foreach ($tagType as $rm => $tag) {
                if ($docblock->hasTag($tag)) {
                    $method = $rm;
                    return $docblock->getTag($tag)->getDescription();
                }
            }
        }
        return false;
    }

}
