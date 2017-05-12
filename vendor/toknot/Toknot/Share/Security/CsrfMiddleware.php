<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2017 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Share\Security;

use Toknot\Share\Controller;
use Toknot\Exception\ForbiddenException;
use Toknot\Exception\BaseException;
/**
 * CSRFMiddeleware
 *
 * @author chopin
 */
class CsrfMiddleware extends Controller {

    protected $crsfFeildName = '_page_token';
    protected $mainCalled = null;
    protected $mainView = null;

    public function __construct() {
        $this->startSession();
        if (!isset($_SESSION[$this->crsfFeildName]) || !is_array($_SESSION[$this->crsfFeildName])) {
            $_SESSION[$this->crsfFeildName] = [];
        }
    }

    public function setCsrf() {
        $this->mainCalled = $this->getMainCalled();
        $this->mainView = $this->mainCalled->getViewInstance();
        $forms = $this->mainView->getForms();
        $routes = $this->mainView->getRouteStorage();

        foreach ($forms as $node) {
            $this->hidden($node, $routes);
        }
    }

    /**
     * verify request of CSRF info
     * 
     * @return boolean
     */
    public function checkCsrf() {
        $contorller = $this->getMianController();
        $route = $this->route()->findRouteByController($contorller);
        $routeName = key($route);

        $token = $this->get($this->crsfFeildName);

        if (!$token || !isset($_SESSION[$this->crsfFeildName][$routeName]) || $token != $_SESSION[$this->crsfFeildName][$routeName]) {
            throw new ForbiddenException;
        }
        unset($_SESSION[$this->crsfFeildName][$routeName]);
    }

    protected function hidden($form, $routes) {
        $formUrl = $form->getAttr('action');
        $method = $form->getAttr('method');
        $findRoute = '';

        foreach ($routes as $r => $info) {
            if ($info['url'] == $formUrl && in_array(strtoupper($method), $info['methods'])) {
                $findRoute = $r;
                break;
            }
        }
        if (empty($findRoute)) {
            throw new BaseException("form action is $formUrl as such route not found");
        }
        $hash = sha1(uniqid() . microtime());

        $_SESSION[$this->crsfFeildName][$findRoute] = $hash;

        $hidden = $this->mainView->input(['type' => 'hidden', 'value' => $hash, 'name' => $this->crsfFeildName]);
        $form->push($hidden);
    }

}
