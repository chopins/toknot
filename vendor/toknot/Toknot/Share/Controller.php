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
use Toknot\Share\DB\DB;
use Toknot\Boot\Kernel;
use Toknot\Boot\Tookit;
use Toknot\Share\Session\Session;
use Symfony\Component\HttpFoundation\Response;

class Controller extends Object {

    private $viewParams = [];
    private $title = '';
    private $layout = null;
    private static $sessionStarted = false;

    /**
     * 
     * @return \Toknot\Boot\Kernel
     */
    public function kernel() {
        return Kernel::single();
    }

    /**
     * instance table model of database
     * 
     * @param string $tableName the database of table name
     * @param string $db    The config of db item of key
     * @return \Toknot\Share\Model
     */
    public function model($tableName, $db = '') {
        return DB::table($tableName, $db);
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
        return Tookit::nsJoin($appCfg['app_ns'], $appCfg['view_ns'],
                        ucwords($view));
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
        $viewClass::setLayout($this->layout);
        $html = $viewClass::html($this->viewParams);

        if ($return) {
            return $html;
        }
        $this->setResponse(200, $html);
    }

    final public function redirect($route, $params = [], $status = 302) {
        $url = $this->url($route, $params);
        $this->setResponse($status, $url);
        $this->kernel()->shutdown();
    }

    /**
     * return the controller response content
     * 
     * @return array
     * @final
     */
    final public function setResponse($statusCode = Kernel::PASS_STATE,
            $responseContent = '') {
        $httpStatus = Response::$statusTexts;
        $return = ['option' => ''];
        if (is_numeric($statusCode) && isset($httpStatus[$statusCode])) {
            $return['code'] = $statusCode;
            $return['message'] = $httpStatus[$statusCode];

            if (!empty($responseContent) &&
                    (strpos($statusCode, '3') === 0 || $statusCode == 201)) {
                $return['option'] = "Location: $responseContent";
            }
        } else {
            $return['code'] = Kernel::PASS_STATE;
            $return['message'] = '';
        }
        $return['content'] = $responseContent;
        $this->kernel()->setResponse($return['code'], $return['message'],
                $return['content'], $return['option']);
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
    public function config($key0 = '', $key1 = '', $key2 = '') {
        $num = func_num_args();
        return $this->kernel()->invokeMethod($num, 'config', func_get_args());
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
     * enable CSRF 
     */
    public function enableCsrf() {
        $hash = uniqid();
        $this->v('_csrf_hash', $hash);
        $_SESSION['_csrf_hash'] = $hash;
    }

    /**
     * verify request of CSRF info
     * 
     * @return boolean
     */
    public function checkCsrf() {
        $crsf = $this->get('_csrf_hash');
        $hash = Tookit::coalesce($_SESSION, '_csrf_hash', null);
        unset($_SESSION['_csrf_hash']);
        return $crsf === $hash;
    }

    /**
     * set view of params
     * 
     * @param string $key
     * @param mix $value
     */
    final public function v($key, $value) {
        $this->viewParams[$key] = $value;
    }

}
