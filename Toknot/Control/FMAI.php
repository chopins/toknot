<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Control;

use Toknot\Di\Object;
use Toknot\View\Renderer;
use Toknot\Config\ConfigLoader;
use Toknot\Db\ActiveRecord;
use Toknot\View\XML;
use Toknot\View\ViewCache;
use Toknot\User\ClassUserControl;
use Toknot\User\CurrentUser;
use Toknot\User\UserControl;
use Toknot\Di\DataCacheControl;
use Toknot\Di\ArrayObject;

/**
 * Framework Module Access Interfaces
 */
final class FMAI extends Object {

    /**
     * The propertie is set variable of {@see Toknot\View\Renderer} use template, because 
     * {@see Toknot\View\Renderer::$varList} use ArrayObject, so available to Application 
     * one Array of set way instead ArrayObject
     *
     * @var ArrayObject 
     */
    private $D = null;

    /**
     * Current HTTP request method
     *
     * @var string
     */
    public $requestMethod = 'GET';

    /**
     * In path mode, URI path without router controller path
     *
     * @var array
     */
    protected $uriOutRouterPath = array();
    protected $accessControlStatus = true;
    private $accessDeniedController = null;
    public $appRoot = '';
    public $enableCache = false;
    public $cacheEffective = false;

    public static function singleton($appRoot) {
        return parent::__singleton($appRoot);
    }

    /**
     * construct Framework Module Access Interfaces object instance, the instance be passed
     * when invoke Application Controller and construct
     * The method will load framework default configure file
     * 
     */
    protected function __construct($appRoot) {
        StandardAutoloader::importToknotClass('Config\ConfigLoader');
        ConfigLoader::singleton();
        $this->appRoot = $appRoot;
        DataCacheControl::$appRoot = $appRoot;
        $this->D = new ArrayObject;
    }

    /**
     * @param string $uri
     */
    public function setURIOutRouterPath($uri) {
        $this->uriOutRouterPath = $uri;
    }

    public function getURIOutRouterPath() {
        return $this->uriOutRouterPath;
    }

    public function invokeBefore(&$invokeClassReflection) {
        if ($invokeClassReflection->isSubclassOf('\Toknot\User\ClassUserControl') 
                && $this->getAccessStatus() === false) {
            $accessDeniedController = $this->getAccessDeniedController();
            $invokeObject = new $accessDeniedController($this);
            $invokeObject->GET();
            return false;
        }
        if ($this->requestMethod == 'GET' && $this->enableCache) {
            ViewCache::outPutCache();
            $this->cacheEffective = ViewCache::$cacheEffective;
            return false;
        }
        if ($this->cacheEffective == false) {
            return true;
        }
    }
    
    public function invokeAfter(&$invokeClassReflection) {
        
    }

    /**
     * Load configure file, and set cache file
     * 
     * @param string $ini
     * @param string $iniCacheFile Set configure option cache file, if empty will not use cache
     *                              the file relative to your application root directory
     * @return ArrayObject
     */
    public function loadConfigure($ini, $iniCacheFile = '') {
        ConfigLoader::$cacheFile = $iniCacheFile;
        return ConfigLoader::loadCFG($ini);
    }

    /**
     * enable HTML cache opreate, the cache will use Toknot\View\Renderer class provided of cache opreate
     * {@see Toknot\View\Renderer::dispaly} when twice request interval time less one threshold could output
     * the first save html file, so {@see Toknot\View\ViewCache::registerDisplayHandle} register what is 
     * {@see Toknot\View\Renderer::dispaly}
     * 
     * <code>
     *  public function __construct($FMAI) {
     *      //enable HTML cache
     *      $this->FMAI->enableHTMLCache();
     * 
     *      //get instance of Toknot\View\Renderer
     *      $view = $this->FMAI->newTemplateView();
     *    
     *      //set cache file
     *      $FMAI->setCacheFile('index');
     * }
     * 
     * public function GET() {
     *      $this->FMAI->display('index');
     * }
     * 
     * </code>
     */
    public function enableHTMLCache(&$CFG) {
        StandardAutoloader::importToknotClass('View\ViewCache');
        $this->enableCache = true;
        ViewCache::$enableCache = true;
        $view = $this->newTemplateView($CFG);
        ViewCache::setRenderer($view);
        ViewCache::registerDisplayHandle('outPutHTMLCache');
    }

    /**
     * set cache file be use when View class output cache, if use {@see Toknot\View\Renderer}
     * accomplish View layer page renderer class and will here is set template file name like use 
     * {@see Toknot\Control\FMAI::display()}
     * 
     * @param string $file
     * @param mixed $flag  Cache flag, value is {@see Toknot\View\Renderer::CACHE_FLAG_HTML} and
     *                      {@see Toknot\View\Renderer::CACHE_FLAG_DATA}
     */
    public function setCacheFile($file, $flag = Renderer::CACHE_FLAG_HTML) {
        Renderer::$enableCache = true;
        Renderer::$cacheFlag = $flag;
        ViewCache::setCacheFile($file);
    }

    /**
     * Instance of {@see Toknot\Db\ActiveRecord}
     * 
     * @return Toknot\Db\ActiveRecord
     */
    public function getActiveRecord() {
        StandardAutoloader::importToknotModule('Db','DbCRUD');
        return ActiveRecord::singleton();
    }

    /**
     * Instance of {@see Toknot\View\Renderer}
     * 
     * @return Toknot\View\Renderer
     */
    public function newTemplateView(& $CFG) {
        StandardAutoloader::importToknotClass('View\Renderer');
        Renderer::$cachePath = $this->appRoot . $CFG->templateCompileFileSavePath;
        Renderer::$fileExtension = $CFG->templateFileExtensionName;
        Renderer::$scanPath = $this->appRoot . $CFG->templateFileScanPath;
        Renderer::$htmlCachePath = $this->appRoot . $CFG->htmlStaticCachePath;
        Renderer::$outCacheThreshold = $CFG->defaultPrintCacheThreshold;
        Renderer::$dataCachePath = $CFG->dataCachePath;
        return Renderer::singleton();
    }
    public function setViewVar($name,$value) {
        $this->D->$name = $value;
    }
    public function &__get($name) {
        if($name == 'D') {
            return $this->D;
        }
    }

    public function newXMLView() {
        return XML::singleton();
    }

    public function newJSONView() {
        
    }

    public function newPictureView() {
        
    }

    /**
     * Use {@see Toknot\View\Renderer} output HTML 
     * 
     * @param string $tplName  The template file name which without extension name 
     *                          and without {@see Toknot\View\Renderer::$scanPath} set path
     */
    public function display($tplName) {
        $view = Renderer::singleton();
        $view->importVars($this->D);
        $view->display($tplName);
    }

    /**
     * Get parameter of passed by URI and with out router path
     * 
     * @param integer $index
     * @return string
     */
    public function getParam($index) {
        return $this->uriOutRouterPath[$index];
    }

    /**
     * Get current user access status
     * 
     * @return boolean if allow access return true otherise false
     */
    public function getAccessStatus() {
        return $this->accessControlStatus;
    }

    /**
     * Register a controller when the user access denied be invoked GET method
     * 
     * @param string $controllerName
     */
    public function registerAccessDeniedController($controllerName) {
        $this->accessDeniedController = $controllerName;
    }

    /**
     * Get current registered controller name of access denied 
     * 
     * @return string
     */
    public function getAccessDeniedController() {
        return $this->accessDeniedController;
    }

    /**
     * Check a user object whether can access class object be passed
     * 
     * @param \Toknot\User\ClassUserControl $clsObj
     * @param \Toknot\User\CurrentUser $user
     */
    public function checkAccess(ClassUserControl $clsObj, UserControl $user) {
        switch ($clsObj->getClassType()) {
            case ClassUserControl::CLASS_READ:
                $this->accessControlStatus = $clsObj->checkRead($user);
                break;
            case ClassUserControl::CLASS_WRITE:
                $this->accessControlStatus = $clsObj->checkWrite($user);
                break;
            case ClassUserControl::CLASS_UPDATE:
                $this->accessControlStatus = $clsObj->checkChange($user);
                break;
            default :
                $this->accessControlStatus = true;
                break;
        }
    }

    /**
     * Get a user object by uid, recommended ser serialize() the user object instead
     * 
     * @param integer $id
     * @return \Toknot\User\CurrentUser
     */
    public function setCurrentUser($id) {
        return CurrentUser::getInstanceByUid($id);
    }

}
