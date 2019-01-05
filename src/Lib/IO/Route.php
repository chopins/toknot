<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2018 chopin xiao (xiao@toknot.com)
 */

namespace Toknot\Lib\IO;

use Toknot\Lib\IO\Request;
use Toknot\Boot\Kernel;
use Toknot\Boot\TKObject;

class Route extends TKObject {

    const MATCH_LIST = ['<ALPHABET+>', '<ALPHABET*>', '<NUMBER+>', '<NUMBER*>'];
    const REG_MATCH_LIST = ['([a-z]+)', '([a-z]*)', '([0-9]+)', '([0-9]*)'];

    private $controller = null;
    private $route = '';
    private $uri = '/';
    private $actionModifier = 'GET' . Kernel::ACTION;
    private $requestParams = [];
    private $action = '';
    private $suffixLen = 5;
    private $routeConfig = null;
    protected $config = null;
    public $defaultAction = 'index';
    public $defaultController = 'Index';
    public static $sep = Kernel::HZL;
    public static $sepAction = Kernel::AT;

    protected function __construct() {
        $this->config = Kernel::instance()->config();
        $this->routeConfig = $this->config->route;
        if ($this->config->sepAction) {
            self::$sepAction = $this->config->sepAction;
        }
        if($this->config->rewriteUnavailable) {
            if($this->config->routeFeild) {
                $this->uri = Request::get()->value($this->config->routeFeild);
            } else {
                $this->uri = Request::get()->index(0);
            }
        } else {
            $this->uri = Request::uri();
        }
        $this->suffixLen = strlen(Kernel::ACTION);
    }

    public function setup() {
        $this->setRoute();
        $this->route2Controller();
    }

    public static function __callStatic($name, $params = []) {
        $ins = self::instance();
        return $ins->invoke($name, $params);
    }

    public function getController() {
        return $this->controller;
    }

    public function getModifier() {
        return $this->actionModifier;
    }

    public function getAction() {
        return $this->action;
    }

    public function getRoute() {
        return $this->route;
    }

    public function getParameter($idx = -1) {
        if ($idx < 0) {
            return $this->requestParams;
        }
        return $this->requestParams[$idx];
    }

    /**
     * convert controller action to url
     * 
     * @param string $controller    the controller action name ,like: NamespaceOne\ClassOne@actionName, 
     *                              the namespace without Toknot\App\Controller
     * @param array $params         url query string
     * @param string $domain        url domain, if not pass, return path only
     * @param string $scheme        url scheme
     * 
     */
    public function generateUrl($controller, $params = [], $domain = '', $scheme = '') {
        $url = Kernel::URL_SEP . $this->controller2Route($controller);
        if ($params) {
            $url = $this->buildParams($url, $params);
        }
        if ($domain) {
            $url = $domain . $url;
        }
        if ($scheme) {
            $url = $scheme . '://' . $url;
        }
        return $url;
    }

    /**
     * bind url query string
     * 
     * @param string $url       the url path
     * @param string $param     the query args
     */
    protected function buildParams($url, $param) {
        $rule = $this->findRule($url);
        $regs = $this->findRuleReg($rule);
        if (!$regs) {
            return $url . Kernel::QUTM . http_build_query($param);
        }
        $option = $httpGets = [];
        foreach ($param as $i => $v) {
            if (is_numeric($i)) {
                $option[$i] = $v;
            } else {
                $httpGets[$i] = $v;
            }
        }
        if (empty($option)) {
            return $url . Kernel::QUTM . http_build_query($param);
        }

        $replace = [];
        $k = 0;
        foreach ($regs as $i => $v) {
            if (!isset($option[$k])) {
                $option[$k] = '';
            }
            $replace[$k] = self::MATCH_LIST[$i];
            $k++;
        }
        $all = array_merge($httpGets, array_diff_key($option, $replace));
        $getParams = Kernel::QUTM . http_build_query($all);
        return str_replace($replace, $option, $option) . $getParams;
    }

    /**
     * convert controller action name to route path
     */
    protected function controller2Route($controller) {
        $appNs = Kernel::NS . Kernel::instance()->appControllerNs() . Kernel::NS;
        $controller = str_replace($appNs, '', $controller);

        $controllerCompent = explode(self::$sepAction, $controller);
        $controllerClass = $controllerCompent[0];
        $action = isset($controllerCompent[1]) ? $controllerCompent[1] : $this->defaultAction;
        
        $path = Kernel::classToLower($controllerClass);
        $actionRoute = Kernel::NOP;
        if ($action) {
            $len = strlen($action);
           
            foreach (Request::METHOD_LIST as $m) {
                $len = strlen($m);
                $offset = -($len + $this->suffixLen);
                if (strtoupper(substr($action, $offset, $len)) === $m) {
                    $actionRoute = substr($action, 0, $offset);
                    break;
                }
            }
            if (!$actionRoute) {
                $actionRoute = $action;
            }
            $actionRoute = Kernel::classToLower($actionRoute);
        }

        return ltrim(strtolower($path), Kernel::UDL) . Kernel::URL_SEP . $actionRoute;
    }

    /**
     * set current route according to access uri
     */
    protected function setRoute() {
        if (is_array($this->uri)) {
            $path = isset($this->uri[1]) ? $this->uri[1] : Kernel::NOP;
        } else {
            $path = parse_url($this->uri, PHP_URL_PATH);
        }
        $path = trim($path);

        foreach ($this->routeConfig as $match => $m) {
            if ($match === $path) {
                $this->route = $m;
                return true;
            } elseif ($this->match($match, $path)) {
                $this->route = $m;
                return true;
            }
        }
        $isDir = (substr($path, -1) === Kernel::URL_SEP || $path === Kernel::NOP);
        if ($isDir) {
            if ($path === Kernel::URL_SEP || $path === Kernel::NOP) {
                $controller = $this->defaultController;
            } else {
                $controller = rtrim(Kernel::toUpper($path, self::$sep . Kernel::URL_SEP), Kernel::URL_SEP);
            }
            $this->route = $controller . self::$sepAction . $this->defaultAction;
        } else {
            $findIdx = strrpos($path, Kernel::URL_SEP);
            $lastIdx = $findIdx + 1;
            $controller = substr($path, 0, $lastIdx);
            if ($controller === Kernel::URL_SEP || $findIdx === false) {
                $controller = Kernel::toUpper($path, self::$sep . Kernel::URL_SEP);
                $action = $this->defaultAction;
                $this->route = $controller . self::$sepAction . $action;
            } else {
                $controller = Kernel::toUpper($controller, self::$sep . Kernel::URL_SEP);
                $action = substr($path, $lastIdx);
                $this->route = $controller . self::$sepAction . $action;
            }
        }
        return true;
    }

    /**
     * route to controller action 
     */
    protected function route2Controller() {
        $route = strtr(rtrim($this->route, Kernel::NS . Kernel::SP), Kernel::URL_SEP, Kernel::NS);
        list($controller, $action) = explode(self::$sepAction, $route);
        $this->action = lcfirst(Kernel::toUpper($action, Kernel::HZL));
        $this->actionModifier = Request::method() . Kernel::ACTION;
        $this->controller = Kernel::instance()->appControllerNs() . Kernel::NS . Kernel::toUpper(trim($controller, Kernel::NS));
    }

    protected function match($match, $path) {
        $match = strtolower($match);
        $path = strtolower($path);
        $findReg = $this->findRuleReg($match);
        if (empty($findReg)) {
            return false;
        }
        $pathStart = $ruleStart = 0;
        $m = [];
        foreach ($findReg as $i => $idx) {
            $normalStrLen = $idx - $ruleStart;
            $pre = substr($path, $pathStart, $normalStrLen);
            if ($pre != substr($match, $ruleStart, $normalStrLen)) {
                return false;
            }
            $matchPath = substr($path, $pathStart - $normalStrLen);
            $reg = '/^' . self::REG_MATCH_LIST[$i] . Kernel::URL_SEP;
            if (!preg_match($reg, $matchPath, $m)) {
                return false;
            }
            $this->requestParams[] = $m[1];
            $pathStart = $idx + strlen($m[1]);
            $ruleStart = $idx + strlen(self::MATCH_LIST[$i]);
        }
        return true;
    }

    protected function findRuleReg($match) {
        $findReg = [];
        foreach (self::MATCH_LIST as $i => $reg) {
            if (($idx = strpos($match, $reg)) !== false) {
                $findReg = [$i => $idx];
            }
        }
        return $findReg;
    }

    protected function findRule($route) {
        foreach ($this->routeConfig as $rule => $map) {
            if ($route == $map) {
                return $rule;
            }
        }
    }

}
