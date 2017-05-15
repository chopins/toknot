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
use Toknot\Boot\Tookit;
use Toknot\Boot\ObjectAssistant;
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

    use ObjectAssistant;

    /**
     *
     * @var \Symfony\Component\Routing\RouteCollection 
     */
    private $topRoutes;
    private $routeDeclare = '';
    private $subCollection = [];
    private $subCollectionParams = [];
    protected $confType = 'ini';
    public $appDIr = APPDIR;
    public $methodSeparator = '@';
    public $staticMethodSeparator = ':';
    protected $appns = '';
    protected $ctlns = '';
    protected $middens = '';

    /**
     *
     * @var \Toknot\Share\Request
     */
    private $request;
    private $kernel = null;
    private $callController = [];
    private $lastCall = [];
    private $rewrite = true;

    protected function __construct() {
        $this->kernel = Kernel::single();
        $this->autoConfigProperty($this->propertySetList(), $this->kernel->cfg);
        $this->topRoutes = new RouteCollection();
    }

    public function propertySetList() {
        return ['confType' => 'app.route_conf_type',
            'methodSeparator' => 'app.route_method_sp',
            'staticMethodSeparator' => 'app.route_static_method_sp',
            'appns' => 'app.app_ns',
            'ctlns' => 'app.ctl_ns',
            'middens' => 'app.middleware_ns',
            'rewrite' => 'app.rewrite'];
    }

    public static function getInstance() {
        return self::single();
    }

    public function call() {
        $parameters = $this->match();
        $this->request = $this->getRequest();
        $requireParams = $this->request->attributes;
        $exec = $this->getNamespace();
        $this->callController = $parameters;
        ob_start();
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
        if ($runResult['code'] != 200) {
            header($runResult['message'], true, $runResult['code']);
        }
        if (!empty($runResult['option'])) {
            foreach ($runResult['option'] as $op) {
                header($op);
            }
        } else {
            if (ob_get_length()) {
                ob_flush();
            }
            echo $runResult['content'];
            die;
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
                $class = Tookit::nsJoin($ns, $name);
                $this->lastCall[$type][] = $this->invoke($class, $requireParams);
            }
        } else {
            $class = Tookit::nsJoin($ns, $parameters[$type]);
            $this->lastCall[$type] = $this->invoke($class, $requireParams);
        }
    }

    private function invoke($call, $requireParams) {
        if (!$this->kernel->isPassState()) {
            return false;
        }

        if (strpos($call, $this->staticMethodSeparator) !== false) {
            $calls = explode($this->staticMethodSeparator, $call);
            if (empty($calls[0])) {
                return self::callFunc($calls[1]);
            } else {
                return self::invokeStatic(0, $calls[1], [], $calls[0]);
            }
        } else {
            $calls = explode($this->methodSeparator, $call);
        }

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
            Tookit::coalesce($param['prefix'], 'controller', '');
            Tookit::splitStr($param['prefix'], 'option', ',', $param['option']);
            Tookit::coalesce($param['prefix'], 'host', $param['host']);
            Tookit::splitStr($param['prefix'], 'schemes', ',', $param['schemes']);
            Tookit::coalesce($param['prefix'], 'condition', ',', $param['condition']);
            Tookit::splitStr($param['prefix'], 'method', ',', $param['method']);
            Tookit::coalesce($param['prefix'], 'require', $param['require']);
            $param['prefix']['defaults'] = ['group' => $param['prefix']['controller']];
            self::single()->addCollection($n, $param);
        } else {
            self::single()->addRoute($n, $param);
        }
    }

    public static function checkParamOption(&$option) {
        Tookit::splitStr($option, 'option');
        Tookit::coalesce($option, 'host');
        Tookit::splitStr($option, 'schemes');
        Tookit::coalesce($option, 'condition');
        Tookit::splitStr($option, 'method', ',', ['GET']);
        Tookit::coalesce($option, 'require', []);
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

        if (!$this->rewrite) {
            $_SERVER['REQUEST_URI'] = isset($_GET['_']) ? $_GET['_'] : '/';
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
        $tparams = Tookit::arrayRemove($parameters, 'controller', 'before', 'after', 'group', '_route');

        $this->request->attributes = new ParameterBag($tparams);

        return $parameters;
    }

    public function getNamespace() {
        $ctlns = Tookit::nsJoin($this->appns, $this->ctlns);
        $middlens = Tookit::nsJoin($this->appns, $this->middens);
        return ['group' => $middlens, 'before' => $middlens, 'controller' => $ctlns, 'after' => $middlens];
    }

    public function add($name, $option) {
        self::checkParamOption($option);
        Tookit::coalesce($option, 'before');
        Tookit::coalesce($option, 'after');

        $option['defaults'] = ['controller' => Tookit::dotNS($option['controller']),
            'before' => explode('|', Tookit::dotNS($option['before'])),
            'after' => explode('|', Tookit::dotNS($option['after']))];

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
