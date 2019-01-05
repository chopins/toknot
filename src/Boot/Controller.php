<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2018 chopin xiao (xiao@toknot.com)
 */

namespace Toknot\Boot;

use Toknot\Lib\IO\Response;
use Toknot\Lib\IO\Request;
use Toknot\Lib\IO\Route;
use Toknot\Boot\Kernel;
use Toknot\Boot\TKObject;
use Toknot\Lib\Exception\ControllerInterruptException;

class Controller extends TKObject {

    protected $viewId = '';
    protected $view = '';
    protected $viewAction = '';
    public static $hideViewError = true;
    public static $viewCacheDir = 'tpl';

    /**
     *
     * @var \Toknot\Boot\Configuration 
     */
    protected $config = null;

    /**
     *
     * @var \Toknot\Boot\Kernel
     */
    protected $kernel = null;

    /**
     *
     * @var \Toknot\Lib\IO\Response
     */
    protected $response = null;

    /**
     *
     * @var \Toknot\Lib\IO\Route
     */
    protected $route = null;

    public function __construct() {
        $this->kernel = Kernel::instance();
        $this->config = $this->kernel->config();
        $this->response = Response::instance();
        $this->route = Route::instance();
        $this->setViewCachePath();
    }

    public function kernel() {
        return $this->kernel;
    }

    public function tk() {
        return $this->kernel;
    }

    public function route() {
        if (!$this->route) {
            $this->route = Route::instance();
        }
        return $this->route;
    }

    public static function __callStatic($name, $params = []) {
        if (!method_exists(get_called_class(), $name)) {
            return call_user_func_array([Kernel::class, $name], $params);
        }
        parent::__callStatic($name, $params);
    }

    public function move($controller, $params = [], $domain = '', $scheme = '') {
        $this->response->move($controller, $params, $domain, $scheme);
    }

    public function redict($controller, $params = [], $domain = '', $scheme = '') {
        $this->response->redict($controller, $params, $domain, $scheme);
    }

    public function moveUrl($url) {
        $this->response->moveUrl($url);
    }

    public function redictUrl($url) {
        $this->response->redictUrl($url);
    }

    public function returnFile($file, $type = '') {
        $resp = $this->response;
        $resp->responseFilePath = $file;
        $resp->responseFileType = $type;
        $this->abort();
    }

    public function returnJson(array $data = []) {
        $this->response->responseType = Response::RESP_TYPE_JSON;
        $this->response->data($data);
        $this->abort();
    }

    public function returnXML(array $data = []) {
        $this->response->responseType = Response::RESP_TYPE_XML;
        $this->response->data($data);
        $this->abort();
    }

    public static function pushException($exception) {
        $response = Response::instance();
        $response->responseException($exception);
    }

    /**
     * 
     * @return \Toknot\Lib\IO\Response
     */
    public static function response() {
        $resp = Response::instance();
        $config = Kernel::instance()->config();
        if (isset($config->index) && isset($config->index->action)) {
            $resp->setIndexAction($config->index->action);
        }
        if (isset($config->index) && isset($config->index->controller)) {
            $resp->setIndexController($config->index->controller);
        }
        $resp->launch();
        try {
            $resp->thenMiddleware()->thenBefore()->thenDoAction()->thenAfter();
        } catch (ControllerInterruptException $e) {
            
        }
        $resp->thenEnd();
        return $resp;
    }

    /**
     * 使用特定异常进行手动中断操作，只能在控制器内使用
     * 
     * @throws ControllerInterruptException
     */
    protected function abort() {
        throw new ControllerInterruptException;
    }

    public function setViewCachePath() {
        $suffix = isset($this->config->viewPath) ? $this->config->viewPath : 'tpl';
        $dir = $this->kernel->runtime . DIRECTORY_SEPARATOR . $suffix;
        if (!is_dir($dir)) {
            mkdir($dir, 0777);
        }
        self::$viewCacheDir = $dir;
    }

    public static function documentRoot() {
        return Request::getDocumentRoot();
    }

    public function getViewCachePath() {
        return self::$viewCacheDir;
    }

    public function getRouteId() {
        return Route::getRoute();
    }

    public function arg($key) {
        if (PHP_SAPI === Kernel::CLI) {
            return Request::cli()->value($key);
        } else {
            $value = $this->request(null, $key);
            if (!$value && Request::method() !== Request::METHOD_LIST[0]) {
                return $this->request('get', $key);
            }
            return $value;
        }
    }

    /**
     * 
     * @param string $type
     * @param string $key
     * @return \Toknot\Lib\IO\Request
     */
    public function request($type = '', $key = '') {
        if (!$type) {
            $ins = Request::input();
        } elseif ($type === 'server') {
            return Request::server($key);
        } else {
            $ins = Request::$type();
        }
        if ($key) {
            return $ins->value($key);
        }
        return $ins;
    }

    public function cookie($key = '') {
        if ($key === '') {
            return Request::cookie();
        }
        return Request::cookie()->value($key);
    }

    public static function exitCode($code = null) {
        if ($code !== null) {
            exit($code);
        }
        $code = Response::instance()->getResponseCode();
        if (PHP_SAPI == Kernel::CLI) {
            exit($code);
        }
    }

    public function setView($viewId) {
        $this->viewId = $viewId;
        list($view, $this->viewAction) = explode(Kernel::AT, $this->viewId);
        $this->viewAction .= Kernel::ACTION;
        $view = Kernel::pathToClass(ucwords($view, Kernel::URL_SEP));
        $this->view = Kernel::instance()->appViewNs() . Kernel::NS . Kernel::toUpper($view);
    }

    public function getViewId() {
        return $this->viewId;
    }

    public function getView() {
        return $this->view;
    }

    public function getViewAction() {
        return $this->viewAction;
    }

    final public function setViewParam($var, $value) {
        Response::instance()->data($var, $value);
    }

}
