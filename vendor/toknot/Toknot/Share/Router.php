<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2017 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Share;

use Toknot\Boot\Tookit;
use Toknot\Boot\Route as TKRoute;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;
use Toknot\Share\Request;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\Routing\Generator\UrlGenerator;

/**
 * Description of Router
 *
 * @author chopin
 */
class Router extends TKRoute {

    /**
     *
     * @var \Symfony\Component\Routing\RouteCollection 
     */
    private $topRoutes;
    private $routeDeclare = '';
    private $subCollection = [];
    private $subCollectionParams = [];

    /**
     *
     * @var \Toknot\Share\Request
     */
    private $request;

    protected function __construct() {
        $this->topRoutes = new RouteCollection();
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
        $parameters = $matcher->matchRequest($this->request);

        $tparams = Tookit::arrayRemove($parameters, 'controller', 'before', 'after', 'group', '_route');

        $this->request->attributes = new ParameterBag($tparams);

        return $parameters;
    }

    public function middlewareNamespace($appCfg) {
        $ctlns = Tookit::nsJoin($appCfg->app_ns, $appCfg->ctl_ns);
        $middlens = Tookit::nsJoin($appCfg->app_ns, $appCfg->middleware_ns);
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

    public function load($php, $ini) {
        return Tookit::includeCache($ini, $php, function ($ini, $target) {
                    $routerMap = Tookit::parseIni($ini);
                    foreach ($routerMap as $rn => $def) {
                        $this->add($rn, $def);
                    }
                    $this->save($target);
                });
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
