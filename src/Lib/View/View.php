<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2018 chopin xiao (xiao@toknot.com)
 */

namespace Toknot\Lib\View;

use Toknot\Boot\Kernel;
use Toknot\Boot\TKObject;
use Toknot\Lib\View\ViewData;
use Toknot\Lib\IO\Route;

abstract class View extends TKObject {

    protected $tplData = [];
    protected $viewTplCacheFile;
    protected $config = null;
    protected $viewId = '';
    protected $controller = null;
    protected $viewTpl = [];
    protected $kernel = null;
    protected $insert = [];
    protected $tagList = [];
    public $autoUpdateRender = true;
    public $viewCacheDir = '';
    public $disableCache = false;

    public function __construct($controller) {
        $this->kernel = Kernel::instance();
        $this->config = $this->kernel->config();
        $this->initCacheConfig();
        $this->controller = $controller;
        $this->tplData = new ViewData;
        $this->viewCacheDir = $controller->getViewCachePath();
    }

    public function newViewData() {
        return new ViewData;
    }

    protected function initCacheConfig() {
        if (!isset($this->config->view->cache)) {
            return;
        }
        $cacheConfig = $this->config->view->cache;
        if (isset($cacheConfig->disable)) {
            $this->disableCache = $cacheConfig->disable;
        }
        if (isset($cacheConfig->autoupdate)) {
            $this->autoUpdateRender = $cacheConfig->autoupdate;
        }
    }

    public function getViewNS() {
        return $this->kernel->appViewNs();
    }

    public function getViewPath() {
        return $this->kernel->getToknotClassPath($this->kernel->appViewNs(), false);
    }

    public function setViewId($viewId) {
        $this->viewId = $viewId;
    }

    public function setParams($param, $value) {
        $this->tplData->add($param, $value);
    }

    public function template($tpl) {
        $this->viewTpl = $tpl;
    }

    public function getTemplate() {
        return $this->viewTpl;
    }

    public function getTplRealpath($tpl) {
        return realpath($tpl);
    }

    public function insertTemplate($tpl, $tag) {
        if (isset($this->insert[$tag])) {
            $this->insert[$tag][] = $this->getTplRealpath($tpl);
        } else {
            $this->insert[$tag] = [$this->getTplRealpath($tpl)];
        }
    }

    public function getInsertTemplate() {
        return $this->insert;
    }

    public function defineTag($tag, $exp) {
        $this->tagList[$tag] = $exp;
    }

    public function getTags() {
        return $this->tagList;
    }

    protected function gCacheTplName() {
        $filename = str_replace(Kernel::NS, Kernel::HZL, $this->controller->getViewId());
        $filename .= Kernel::HZL . md5(get_called_class());
        $filename .= Kernel::PHP_EXT;
        $filename = str_replace(DIRECTORY_SEPARATOR, '-', $filename);
        $this->viewTplCacheFile = $this->viewCacheDir . DIRECTORY_SEPARATOR . $filename;
    }

    public function output() {
        if (!$this->viewTpl) {
            return Kernel::NOP;
        }
        $this->gCacheTplName();
        $complie = new Compile($this->viewTpl, $this->viewTplCacheFile);
        $complie->view = $this;
        if (($this->autoUpdateRender && $this->checkCache()) || $this->disableCache) {
            $complie->render();
        }
        $complie->load($this->tplData);
    }

    /**
     * controller action to url
     * 
     * @param string $route     route
     * @param array $params  url query params
     */
    public function route2Url($route, $params) {
        return Route::instance()->generateUrl($route, $params);
    }

    public function checkCache() {
        return !file_exists($this->viewTplCacheFile) ||
                filemtime($this->viewTpl) > filemtime($this->viewTplCacheFile) || $this->checkInsertTpl();
    }

    public function checkInsertTpl() {
        $cacheTime = filemtime($this->viewTplCacheFile);
        foreach ($this->insert as $l) {
            foreach ($l as $tpl) {
                if (filemtime($tpl) > $cacheTime) {
                    return true;
                }
            }
        }
    }

    final public function setData($data) {
        $this->tplData = Kernel::merge($this->tplData, $data);
    }

}
