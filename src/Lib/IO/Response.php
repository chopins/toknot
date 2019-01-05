<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2018 chopin xiao (xiao@toknot.com)
 */

namespace Toknot\Lib\IO;

use Toknot\Boot\TKObject;
use Toknot\Lib\IO\Request;
use Toknot\App\View;
use Toknot\Boot\Kernel;
use Toknot\Lib\IO\HttpHeader;

class Response extends TKObject {

    private $thenRes = null;
    private $accessInstance = null;
    private $responseData = [];
    private $charset = 'utf-8';
    private $route = null;
    private $webserverSendFile = false;
    private $config = null;
    private $responseCode = 200;
    private $view = null;
    private $invokeStatus = self::STATUS_SUCCESS;
    public $responseType = '';
    public $responseFilePath = '';
    public $responseFileType = '';

    const RESP_TYPE_JSON = 'json';
    const RESP_TYPE_XML = 'xml';
    const RESP_TYPE_HTML = 'html';
    const RESP_TYPE_FILE = 'file';
    const RESP_TYPE_EXCEPTION = 'exception';
    const STATUS_NOT_FOUND_CONTROLLER = 1;
    const STATUS_NOT_FOUND_ACTION = 2;
    const STATUS_SUCCESS = 0;

    protected function __construct() {
        $this->thenRes = Kernel::onYes();
        $this->route = Route::instance();
        $this->config = Kernel::instance()->config();
    }

    public function __call($name, $argv = []) {
        if (strpos($name, Kernel::THEN) === 0) {
            return $this->then(Kernel::thenName($name));
        }
        parent::__call($name, $argv);
    }

    /**
     * set response code number
     */
    public function setResponseCode($code = null) {
        if ($code !== null) {
            $this->responseCode = $code;
        } elseif (!$this->responseCode && PHP_SAPI !== Kernel::CLI) {
            $this->responseCode = http_response_code();
        }
    }

    /**
     * set default action name of controller
     */
    public function setIndexAction($action) {
        $this->route->defaultAction = $action;
    }

    /**
     * set default controller name
     */
    public function setIndexController($controller) {
        $this->route->defaultController = $controller;
    }

    public function getResponseCode() {
        return $this->responseCode;
    }

    public function data($params, $value = '') {
        if (is_array($params)) {
            $this->responseData = array_merge($this->responseData, $params);
        } else {
            $this->responseData[$params] = $value;
        }
    }

    public function charset($charset = null) {
        if ($charset === null) {
            return $this->charset;
        }
        $this->charset = $charset;
    }

    /**
     * 
     * @return \Toknot\Boot\Route
     */
    public function getRoute() {
        return $this->route;
    }

    public function redict($controller, $params = [], $domain = '', $scheme = '') {
        $url = $this->route->generateUrl($controller, $params, $domain, $scheme);
        $this->redictUrl($url);
    }

    public function move($controller, $params = [], $domain = '', $scheme = '') {
        $url = $this->route->generateUrl($controller, $params, $domain, $scheme);
        $this->moveUrl($url);
    }

    public function moveUrl($url) {
        HttpHeader::h301($url);
        exit(301);
    }

    public function redictUrl($url) {
        HttpHeader::h302($url);
        exit(302);
    }

    public function getStatus() {
        return $this->invokeStatus;
    }

    public function return404() {
        HttpHeader::h404();
        $this->responseUnfound();
        exit(404);
    }

    public function setBrowserId($id = '') {
        $id = $id ? $id : Request::requestHash();
        setcookie(Kernel::BROWSER_ID, $id, time() + 3600 * 24 * 365, '/');
    }

    public function then($call) {
        if (Kernel::isYes($this->thenRes)) {
            $this->thenRes = $this->$call();
        } else {
            $this->end();
        }
        return $this;
    }

    public function responseFile($file, $type = '') {
        $filename = pathinfo($file, PATHINFO_BASENAME);
        HttpHeader::attachment($filename);
        HttpHeader::contentType($type);
        if (!$this->webserverSendFile) {
            $size = filesize($file);
            HttpHeader::contentLength($size);
            readfile($file);
        } elseif (Request::isApache() || Request::isLighttpd()) {
            header("X-Sendfile: $file");
        } elseif (Request::isNginx()) {
            header("X-Accel-Redirect: $file");
        }
    }

    public function launch() {
        $this->route->setup();
        $controller = $this->route->getController();
        if (!class_exists($controller, true)) {
            $this->invokeStatus = self::STATUS_NOT_FOUND_CONTROLLER;
            $this->return404();
        }
        $this->accessInstance = new $controller;
        if (isset($this->config->charset)) {
            $this->charset = $this->config->charset;
        }
        if (isset($this->config->xsendfile)) {
            $this->webserverSendFile = $this->config->xsendfile;
        }
    }

    protected function middleware() {
        return Kernel::onYes();
    }

    protected function before() {
        return $this->autoAction(__FUNCTION__);
    }

    protected function after() {
        return $this->autoAction(__FUNCTION__);
    }

    protected function end() {
        if (Kernel::isUnfound($this->thenRes)) {
            $this->invokeStatus = self::STATUS_NOT_FOUND_ACTION;
            return $this->return404();
        }
        $this->view = $this->accessInstance->getView();
        if (Request::wantJSON() || $this->responseType === self::RESP_TYPE_JSON) {
            $this->responseJSON();
        } elseif (Request::wantXML() || $this->responseType === self::RESP_TYPE_XML) {
            $this->responseXML();
        } elseif ($this->responseType === self::RESP_TYPE_FILE) {
            $this->responseFile($this->responseFilePath, $this->responseFileType);
        } elseif ($this->responseType === self::RESP_TYPE_EXCEPTION) {
            $this->responseException();
        } else {
            $this->responseHTML();
        }
        $this->setResponseCode();
    }

    protected function responseUnfound() {
        if ($this->config->view->unfound) {
            $fullClass = View::class . Kernel::NS . $this->config->view->unfound;
            $ins = new $fullClass;
            $ins->route = $this->route;
            $ins->response = $this;
            return $ins->put();
        }
    }

    protected function responseJSON() {
        HttpHeader::contentType('application/json');
        if ($this->view) {
            return $this->callView();
        }
        echo json_encode($this->responseData);
    }

    protected function responseXML() {
        HttpHeader::contentType('text/xml');
        if ($this->view) {
            return $this->callView();
        }

        echo '<?xml version="1.0" encoding="' . $this->charset . '"?>';
        echo '<data>';
        $this->array2Xml($this->responseData);
        echo '</data>';
    }

    protected function array2Xml($arr) {
        foreach ($arr as $k => $v) {
            if (is_array($v)) {
                echo "<$k>";
                $this->array2Xml($v);
                echo "</$k>";
            } else {
                echo "<$k>$v</$k>";
            }
        }
    }

    public function responseException($e) {
        if (PHP_SAPI !== Kernel::CLI) {
            echo "<html><head><meta charset='{$this->charset}'></head><body><pre style=\"white-space: pre-wrap;word-wrap: break-word;\">";
        }
        echo $e;
        if (PHP_SAPI !== Kernel::CLI) {
            echo '</pre></body></html>';
        }
    }

    protected function responseHTML() {
        if ($this->view) {
            return $this->callView();
        } elseif ($this->responseData) {
            echo "<html><head><meta charset='{$this->charset}'></head><body><pre style=\"white-space: pre-wrap;word-wrap: break-word;\">";
            var_dump($this->responseData);
            echo '</pre></body></html>';
        }
    }

    protected function callView() {
        $view = $this->view;
        try {
            $ins = new $view($this->accessInstance);
        } catch (\Error $e) {
            if(class_exists($view)) {
                throw $e;
            }
            Kernel::runtimeException("View Class Not Found ($view)", E_USER_ERROR);
        }
        $ins->setData($this->responseData);
        $viewAction = $this->accessInstance->getViewAction();
        try {
            $ins->{$viewAction}();
        } catch (\Exception $e) {
            Kernel::runtimeException("View Class Not Found ($view)", E_USER_ERROR);
        }
        $ins->output();
    }

    /**
     * call controller action, call step:
     * has userPostAction called, otherwise check whether has userAction and has called
     * no one return unfound
     * 
     * @return \Toknot\Lib\Flag\Flag
     */
    protected function doAction() {
        if (Kernel::isNo($this->route->getRoute())) {
            return Kernel::onUnfound();
        }
        $action = $this->route->getAction() . $this->route->getModifier();
        $anyMethodAction = $this->route->getAction() . Kernel::ACTION;

        if (method_exists($this->accessInstance, $action)) {
            $callAction = $action;
        } else if (method_exists($this->accessInstance, $anyMethodAction)) {
            $callAction = $anyMethodAction;
        } else {
            return Kernel::onUnfound();
        }
        if ($this->route->getParameter()) {
            call_user_func_array(array($this->accessInstance, $callAction), $this->route->getParameter());
        } else {
            $this->accessInstance->$callAction();
        }
        return Kernel::onYes();
    }

    protected function autoAction($function) {
        $action = $function . $this->route->getModifier();
        $defAction = $function . Kernel::ACTION;
        if (method_exists($this->accessInstance, $defAction)) {
            $res = $this->accessInstance->$defAction();
            if (Kernel::isNo($res)) {
                return Kernel::onNo();
            }
        }
        if (method_exists($this->accessInstance, $action)) {
            $res = $this->accessInstance->$action();
            if (Kernel::isNo($res)) {
                return Kernel::onNo();
            }
        }
        return Kernel::onYes();
    }

}
