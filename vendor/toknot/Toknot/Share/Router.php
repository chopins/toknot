<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2017 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Share;

use Toknot\Boot\Kernel;
use Toknot\Boot\Configuration;
use Toknot\Boot\Object;
use Toknot\Boot\SystemCallWrapper;
use Toknot\Exception\NotFoundException;
use Toknot\Share\Request;
use Toknot\Exception\MethodNotAllowedException as MethodNotAllowed;
use Toknot\Exception\BaseException;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;

/**
 * Description of Router
 *
 * @author chopin
 */
class Router extends Object implements SystemCallWrapper {

    /**
     *
     * @var \Symfony\Component\Routing\RouteCollection 
     */
    private $topRoutes;
    private $routeDeclare = '';
    private $subCollection = [];
    private $subCollectionParams = [];
    private $confType = 'ini';
    public $appDIr = APPDIR;

    /**
     *
     * @var \Toknot\Share\Request
     */
    private $request;
    private $appCfg = null;
    private $kernel = null;
    private $callController = [];
    private $lastCall = [];

    protected function __construct() {
        $this->topRoutes = new RouteCollection();
        $this->kernel = Kernel::single();
        $this->appCfg = $this->kernel->cfg->app;
        if (!empty($this->appCfg['route_conf_type'])) {
            $this->confType = $this->appCfg['route_conf_type'];
        }
    }

    public static function getInstance() {
        return self::single();
    }

    public function call() {
        $parameters = $this->match();
        $this->request = $this->getRequest();
        $requireParams = $this->request->attributes;
        $exec = $this->getNamespace($this->appCfg);
        $this->callController = $parameters;
        foreach ($exec as $key => $ns) {
            $this->launch($parameters, $ns, $key, $requireParams);
        }
        
        foreach ($this->lastCall as $call) {
            if (method_exists($call, 'responsePage')) {
                $call->responsePage();
            }
        }
    }

    public function response($runResult) {
        
        
        if ($this->kernel->isCLI) {
            echo $runResult['content'];
            exit($runResult['code']);
        }
        header($runResult['message'], true, $runResult['code']);
        if (!empty($runResult['option'])) {
            foreach ($runResult['option'] as $op) {
                header($op);
            }
        } else {
            echo $runResult['content'];
        }
    }

    public function init($path = '') {
        $this->load();
    }

    public function getArg($key = '') {
        return $this->request->get($key);
    }

    public static function register() {
        stream_register_wrapper('rt', __CLASS__);
        return true;
    }

    public function stream_stat() {
        return $this->request ? true : false;
    }

    public function stream_open($path, $mode = 'GET') {
        $this->request = Request::create($path, $mode);
        return true;
    }

    public function steam_read() {
        return $this->call();
    }

    public function getController($key = null) {
        if (isset($this->callController[$key])) {
            return $this->callController[$key];
        } else {
            foreach ($this->callController as $c) {
                if (is_array($c) && isset($c[$key])) {
                    return $c[$key];
                }
            }
        }
        return $this->callController;
    }

    public function getCalled($key) {
        if (isset($this->lastCall[$key])) {
            return $this->lastCall[$key];
        }
        return $this->lastCall;
    }

    private function launch($parameters, $ns, $type, $requireParams) {
        if (empty($parameters[$type])) {
            return false;
        }

        if (is_array($parameters[$type])) {
            $this->lastCall[$type] = [];
            foreach ($parameters[$type] as $name) {
                if (empty($name)) {
                    continue;
                }
                $class = self::nsJoin($ns, $name);
                $this->lastCall[$type][] = $this->invoke($class, $requireParams);
            }
        } else {
            $class = self::nsJoin($ns, $parameters[$type]);
            $this->lastCall[$type] = $this->invoke($class, $requireParams);
        }
    }

    private function invoke($call, $requireParams) {
        if (!$this->kernel->isPassState()) {
            return false;
        }

        $calls = explode('::', $call);
        $class = $calls[0];
        $paramsCount = $requireParams->count();

        $params = iterator_to_array($requireParams, false);
        if ($paramsCount > 0) {
            $groupins = self::constructArgs($paramsCount, $params, $class);
        } else {
            $groupins = new $class();
        }

        if (isset($calls[1])) {
            if ($paramsCount > 0) {
                self::callMethod($paramsCount, $calls[1], $params, $groupins);
            } else {
                $groupins->{$calls[1]}();
            }
        }
        return $groupins;
    }

    public static function to($n, $param) {
        if (isset($param['prefix'])) {
            self::coalesce($param['prefix'], 'controller', '');
            self::splitStr($param['prefix'], 'option', ',', $param['option']);
            self::coalesce($param['prefix'], 'host', $param['host']);
            self::splitStr($param['prefix'], 'schemes', ',', $param['schemes']);
            self::coalesce($param['prefix'], 'condition', ',', $param['condition']);
            self::splitStr($param['prefix'], 'method', ',', $param['method']);
            self::coalesce($param['prefix'], 'require', $param['require']);
            $param['prefix']['defaults'] = ['group' => $param['prefix']['controller']];
            self::single()->addCollection($n, $param);
        } else {
            self::single()->addRoute($n, $param);
        }
    }

    public static function checkParamOption(&$option) {
        self::splitStr($option, 'option');
        self::coalesce($option, 'host');
        self::splitStr($option, 'schemes');
        self::coalesce($option, 'condition');
        self::splitStr($option, 'method', ',', ['GET']);
        self::coalesce($option, 'require', []);
    }

    public function url($action, $parameters = []) {
        $g = new UrlGenerator($this->topRoutes, new RequestContext());
        return $g->generate($action, $parameters);
    }

    public function getMethods($action) {
        if (null !== $route = $this->topRoutes->get($action)) {
            return $route->getMethods();
        }
        throw new BaseException("the named route '$action' as such route does not exist.");
    }

    public function findRouteByController($controller) {
        foreach ($this->topRoutes as $n => $route) {
            $def = $route->getDefaults();
            if ($def['controller'] == $controller) {
                return [$n => $route];
            }
        }
        return null;
    }

    /**
     * 
     * @return \Toknot\Share\Request
     */
    public function getRequest() {
        if ($this->request === null) {
            $this->request = Request::createFromGlobals();
        }
        return $this->request;
    }

    public function match() {
        foreach ($this->subCollection as $key => $sub) {
            $subParams = $this->subCollectionParams[$key];
            $this->subCollection[$key]->addPrefix($subParams['path']);
            $this->subCollection[$key]->addDefaults($subParams['defaults']);
            $this->subCollection[$key]->addRequirements($subParams['require']);
            $this->subCollection[$key]->addOptions($subParams['option']);
            $this->subCollection[$key]->setHost($subParams['host']);
            $this->subCollection[$key]->setMethods($subParams['method']);
            $this->subCollection[$key]->setSchemes($subParams['schemes']);
            $this->topRoutes->addCollection($sub);
        }

        $this->request = Request::createFromGlobals();

        $context = new RequestContext();
        $context->fromRequest($this->request);
        $matcher = new UrlMatcher($this->topRoutes, $context);
        try {
            $parameters = $matcher->matchRequest($this->request);
        } catch (ResourceNotFoundException $e) {
            throw new NotFoundException($e);
        } catch (MethodNotAllowedException $e) {
            throw new MethodNotAllowed($e);
        }
        $tparams = self::arrayRemove($parameters, 'controller', 'before', 'after', 'group', '_route');

        $this->request->attributes = new ParameterBag($tparams);

        return $parameters;
    }

    public function getNamespace($appCfg) {
        $ctlns = self::nsJoin($appCfg['app_ns'], $appCfg['ctl_ns']);
        $middlens = self::nsJoin($appCfg['app_ns'], $appCfg['middleware_ns']);
        return ['group' => $middlens, 'before' => $middlens, 'controller' => $ctlns, 'after' => $middlens];
    }

    public function add($name, $option) {
        self::checkParamOption($option);
        self::coalesce($option, 'before');
        self::coalesce($option, 'after');

        $option['defaults'] = ['controller' => self::dotNS($option['controller']),
            'before' => explode('|', self::dotNS($option['before'])),
            'after' => explode('|', self::dotNS($option['after']))];

        if (isset($option['prefix']) && isset($option['prefix']['controller'])) {
            $option['prefix']['controller'] = str_replace('.', PHP_NS, $option['prefix']['controller']);
        }
        $params = var_export($option, true);
        $this->routeDeclare .= "Router::to('$name', $params);";
    }

    public function load() {
        $ini = "{$this->appDIr}/config/router.{$this->confType}";
        $php = "{$this->appDIr}/runtime/config/route.php";
        return $this->createCache($ini, $php);
    }

    public function createCache($ini, $php) {
        clearstatcache();
        if (file_exists($php) && filemtime($ini) <= filemtime($php)) {
            return include $php;
        }
        $routerMap = Configuration::parseConf($ini);
        foreach ($routerMap as $rn => $def) {
            $this->add($rn, $def);
        }
        $this->save($php);
    }

    public function save($target) {
        $head = '<?php' . PHP_EOL;
        $head .= 'use ' . __CLASS__ . ';' . PHP_EOL;
        file_put_contents($target, $head . $this->routeDeclare);
    }

    public function addRoute($n, $params) {
        $route = new Route($params['path'], $params['defaults'], $params['require'], $params['option'], $params['host'], $params['schemes'], $params['method'], $params['condition']);
        $this->topRoutes->add($n, $route);
    }

    public function addCollection($n, $params) {
        $key = $params['prefix']['path'];
        if (!isset($this->subCollection[$key])) {
            $this->subCollection[$key] = new RouteCollection();
            $this->subCollectionParams[$key] = $params['prefix'];
        }
        $route = new Route($params['path'], $params['defaults'], $params['require'], $params['option'], $params['host'], $params['schemes'], $params['method'], $params['condition']);
        $this->subCollection[$key]->add($n, $route);
    }

}
