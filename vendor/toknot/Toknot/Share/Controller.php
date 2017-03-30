<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2017 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Share;

use Toknot\Boot\Object;
use Toknot\Share\DB\DBA;
use Toknot\Boot\Kernel;
use Toknot\Boot\Tookit;
use Toknot\Share\Session\Session;
use Symfony\Component\HttpFoundation\Response;
use Toknot\Share\View\XML;
use Toknot\Share\View\ParameterBag;
use Toknot\Exception\BaseException;

class Controller extends Object {

    /**
     * @readonly
     */
    private static $viewParams = null;
    private $title = '';
    private $layout = null;
    private static $sessionStarted = false;
    private $header = [];

    /**
     * 
     * @return \Toknot\Boot\Kernel
     */
    public function kernel() {
        return Kernel::single();
    }

    /**
     * instance table of database
     * 
     * @param string $tableName the database of table name
     * @param string $db    The config of db item of key
     * @return \Toknot\Share\DBTable
     */
    public function table($tableName, $db = '') {
        return DBA::table($tableName, $db);
    }

    /**
     * set view page of title
     * 
     * @param string $title     The page of title string
     */
    final public function setTitle($title) {
        $this->title = $title;
    }

    /**
     * set view page use layout
     * 
     * @param string $layout    The view layout of class name
     */
    final public function setLayout($layout) {
        $this->layout = $layout;
    }

    /**
     * get all class name of without namespace class name
     * 
     * @param string $view      without namespace class name
     * @return string
     */
    public function getViewClass($view) {
        $appCfg = $this->config('app');
        $view = Tookit::ucwords($view, '.');
        $view = Tookit::dotNS($view);
        return Tookit::nsJoin($appCfg['app_ns'], $appCfg['view_ns'], ucwords($view));
    }

    /**
     * convert view to html and set response content
     * 
     * @param string $view  without namespace class name
     * @param boolean $return   if true,the method will return the view of html
     * @return string
     */
    public function view($view, $return = false) {
        $appCfg = $this->config('app');
        $viewClass = $this->getViewClass($view);
        $viewClass::setTitle($this->title);
        if (empty($this->layout)) {
            $this->layout = $appCfg['default_layout'];
        }
        $layout = new $this->layout(self::$viewParams);
        $html = $viewClass::html($layout, self::$viewParams);

        if ($return) {
            return $html;
        }
        $this->setResponse(200, $html);
    }

    /**
     * Redirect to specil route, default is 302 redirect
     * 
     * @param string $route
     * @param array $params
     * @param int $status
     */
    final public function redirect($route, $params = [], $status = 302) {
        $url = $this->url($route, $params);
        $this->response($status, $url);
    }

    /**
     * immediately response
     * 
     * @param int $statusCode
     * @param string $responseContent
     */
    final public function response($statusCode, $responseContent = '') {
        $this->setResponse($statusCode, $responseContent);
        $this->kernel()->shutdown();
    }

    /**
     * immediately response json data
     * 
     * @param array $data
     */
    final public function responseJson($data) {
        $this->header = 'Content-Type: text/json';
        $this->response(200, json_encode($data));
    }

    final public function responesXml($data) {
        $this->header = 'Content-Type: text/xml';
        $xml = new XML($data);
        $this->response(200, $xml);
    }

    final public function allowOrigin($host) {
        $route = $this->kernel()->routerIns()->findRouteByController($this->kernel()->call['controller']);

        $spro = $this->kernel()->schemes;
        $schemes = $route->getSchemes();
        $pro = !in_array($spro, $schemes) ? $schemes[0] : $spro;

        $url = "$pro://$host";
        $this->header = "Access-Control-Allow-Origin: $url";

        $method = implode(',', $route->getMethods());

        $this->header = "Access-Control-Allow-Methods: $method";
    }

    final public function header($header) {
        $this->header[] = $header;
    }

    /**
     * return the controller response content
     * 
     * @return array
     * @final
     */
    final public function setResponse($statusCode = Kernel::PASS_STATE, $responseContent = '') {
        $httpStatus = Response::$statusTexts;
        $return = ['option' => $this->header];
        if (is_numeric($statusCode) && isset($httpStatus[$statusCode])) {
            $return['code'] = $statusCode;
            $return['message'] = $httpStatus[$statusCode];

            if (!empty($responseContent) &&
                    (strpos($statusCode, '3') === 0 || $statusCode == 201)) {
                $return['option'][] = "Location: $responseContent";
            }
        } else {
            $return['code'] = Kernel::PASS_STATE;
            $return['message'] = '';
        }
        $return['content'] = $responseContent;
        $this->kernel()->setResponse($return['code'], $return['message'], $return['content'], $return['option']);
    }

    /**
     * generate route url
     * 
     * @param string $route     The route name
     * @param array $params     the params
     * @return string
     */
    public function url($route, $params = []) {
        return $this->kernel()->routerIns()->url($route, $params);
    }

    /**
     * get config with specify key
     * 
     * @param string $key0      the config first level item of key
     * @param string $key1      the config second level item of key
     * @param string $key2      the config third level item of key
     * @param string $_         the other level item of key
     * @return array|string
     */
    public function config($key) {
        return $this->kernel()->config($key);
    }

    /**
     * get the request params with specify field
     * 
     * @param string $key   the request field
     * @return string
     */
    public function get($key = '') {
        $request = $this->kernel()->request;
        return $request->get($key);
    }

    /**
     * check whether has key in request
     * 
     * @param string $key
     * @return boolean
     */
    public function has($key) {
        $v = $this->get($key);
        return !empty($v);
    }

    /**
     * The method will run default mode session
     * 
     * @return Session
     */
    public function startSession() {
        if (self::$sessionStarted) {
            return true;
        }
        $session = new Session;
        $session->start();
        self::$sessionStarted = true;
        return $session;
    }

    /**
     * get view of params
     * 
     */
    final public function v() {
        if (self::$viewParams instanceof ParameterBag) {
            return self::$viewParams;
        }
        self::$viewParams = new ParameterBag();
        return self::$viewParams;
    }

    final public function __get($name) {
        if ($name == 'v') {
            return $this->v();
        }
        $this->exception("property $name undefined");
    }

    final public function exception($msg) {
        throw new BaseException($msg);
    }

    public function getTimeZone() {
        $configZone = $this->kernel()->cfg->find('app.timezone');
        return $configZone ? $configZone : 'UTC';
    }

}
