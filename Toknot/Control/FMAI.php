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
use Toknot\Di\DataCacheControl;
use Toknot\Di\ArrayObject;
use Toknot\Di\Log;
use Toknot\Di\FileObject;
use Toknot\Config\ConfigLoader;
use Toknot\Db\ActiveRecord;
use Toknot\View\ViewCache;
use Toknot\View\Renderer;
use Toknot\User\ClassAccessControl;
use Toknot\User\UserAccessControl;
use Toknot\User\Nobody;
use Toknot\User\Session;
use Toknot\User\Root;
use Toknot\User\Exception\NoPermissionExecption;
use Toknot\Control\Exception\ForbiddenException;
use Toknot\Control\Router;
use Toknot\Di\TKFunction as TK;

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
     * @access readonly
     */
    private $D = null;

    /**
     * Current HTTP request method
     *
     * @var string
     * @access readonly
     */
    private $requestMethod = 'GET';

    /**
     * In path mode, URI path without router controller path
     *
     * @var array
     * @access private
     */
    private $uriOutRouterPath = array();

    /**
     * Current user whether can access the check class
     *
     * @var boolean 
     * @access private
     */
    private $accessControlStatus = true;

    /**
     * when user access be denied invoke controller
     *
     * @var string
     * @access private
     */
    private $forbiddenController = null;

    /**
     * before invoke controller method and call the handler
     *
     * @var callable
     * @access private
     */
    private $invokeBeforeHandler = null;

    /**
     * after invoke controller method and call the handler
     *
     * @var callable
     * @access private
     */
    private $invokeAfterHandler = null;

    /**
     * current access controller
     *
     * @var Object 
     * @access readonly
     */
    private $controller = null;

    /**
     * the Application root directory path
     *
     * @var string
     * @access readonly
     */
    private $appRoot = '';

    /**
     * the application root namespace
     *
     * @var string
     * @access readonly
     */
    private $appNamespace = '';

    /**
     * Current access user of object
     *
     * @var Toknot\User\Root|Toknot\User\UserClass|Toknot\User\Nobody
     * @access readonly
     */
    private $currentUser = null;

    /**
     * if user no permission be invoked controller
     *
     * @var Toknot\Control\ControllerInterface\ControllerInterface
     * @access private
     */
    private $noPermissionController = null;

    /**
     * The value equal PHP of get_magic_quotes_gpc()
     *
     * @var int
     * @static
     * @access public 
     */
    public static $magicQuotesGpc = 0;

    /**
     * Whether enable HTML cache
     * @var boolean
     */
    public $enableCache = false;
    private $exitStatus = false;

    /**
     * FMAI singleton
     * 
     * @param string $appNamespace  Namespace of user app
     * @param string $appRoot       directory root path of user app
     * @return Toknot\Control\FMAI
     */
    public static function singleton($appNamespace, $appRoot) {
        return parent::__singleton($appNamespace, $appRoot);
    }

    /**
     * construct Framework Module Access Interfaces object instance, the instance be passed
     * when invoke Application Controller and construct
     * The method will load framework default configure file
     * 
     * @access protected
     * @param string $appNamespace  Namespace of user app
     * @param string $appRoot       directory root path of user app
     */
    protected function __construct($appNamespace, $appRoot) {
        self::$magicQuotesGpc = get_magic_quotes_gpc();
        StandardAutoloader::importToknotClass('Config\ConfigLoader');
        ConfigLoader::singleton();

        if (file_exists($appRoot . '/Config/config.ini')) {
            $this->loadConfigure($appRoot . '/Config/config.ini', $appRoot . '/Data/config');
        }

        $CFG = ConfigLoader::CFG();
        date_default_timezone_set($CFG->App->timeZone);
        $this->appRoot = $appRoot;
        $this->appNamespace = $appNamespace;
        $this->currentUser = new Nobody;
        $this->D = new ArrayObject;

        $this->registerForbiddenController($CFG->App->forbiddenController);
        $this->registerNoPermissonController($CFG->App->noPermissionController);

        DataCacheControl::$appRoot = $appRoot;
        Log::$enableSaveLog = $CFG->Log->enableLog;
        Log::$savePath = FileObject::getRealPath($appRoot, $CFG->Log->logSavePath);
    }

    /**
     * user namespace import class
     * 
     * @param type $className  the name contain full namespace
     * @param type $aliases
     */
    public static function import($className, $aliases) {
        StandardAutoloader::import($className, $aliases);
    }

    /**
     * invoke class that be imported
     * 
     * @param string $key   the key is aliases or class name without namespace
     * @return object   instance of class that be impport
     */
    public static function call($key) {
        $name = StandardAutoloader::getImprotList($key);
        return new $name;
    }

    /**
     * Router set URI string that without router map part
     * 
     * @param string $uri
     */
    public function setURIOutRouterPath($uri, $method) {
        $this->uriOutRouterPath = $uri;
        $this->requestMethod = $method;
    }

    /**
     * Get URI without router map part
     * 
     * @return string
     */
    public function getURIOutRouterPath() {
        return $this->uriOutRouterPath;
    }

    /**
     * do not invoke method of controller
     * 
     * @param string $method
     */
    public function kill($method = null) {
        if ($method == $this->requestMethod) {
            $this->exitStatus = true;
        } elseif ($method === null) {
            $this->exitStatus = true;
        }
    }

    /**
     * the method be invoked before which method of controller was invoked by router 
     * 
     * @param \Toknot\User\ClassAccessControl $controller
     * @return boolean  if false, do not invoked method of controller
     */
    public function invokeBefore(&$controller) {
        $this->controller = $controller;
        if ($controller instanceof ClassAccessControl && $this->noPermissionController) {
            $noPerms = Router::controllerNameTrans($this->noPermissionController);
            UserAccessControl::updatePermissonController($this, $noPerms, $this->requestMethod);
        }
        if ($this->requestMethod == 'GET' && $this->enableCache) {
            ViewCache::outPutCache();
            if (ViewCache::$cacheEffective == ViewCache::CACHE_USE_SUCC) {
                return false;
            }
        }

        if ($this->invokeBeforeHandler !== null) {
            if (is_array($this->invokeAfterHandler)) {
                $obj = $this->invokeAfterHandler[0];
                $method = $this->invokeAfterHandler[1];
                $obj->$method($this);
            } else {
                $func = $this->invokeAfterHandler;
                $func($this);
            }
        }
        if ($this->exitStatus) {
            return false;
        }
        return true;
    }

    /**
     * the method be invoked after which method of controller was invoked by router
     * 
     * @return null
     */
    public function invokeAfter() {
        if ($this->invokeAfterHandler === null)
            return;
        if (is_array($this->invokeAfterHandler)) {
            $obj = $this->invokeAfterHandler[0];
            $method = $this->invokeAfterHandler[1];
            $obj->$method($this);
        } else {
            $func = $this->invokeAfterHandler;
            $func($this);
        }
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
        ConfigLoader::importCfg($ini);
        Log::$enableSaveLog = ConfigLoader::CFG()->Log->enableLog;
        Log::$savePath = FileObject::getRealPath($this->appRoot, ConfigLoader::CFG()->Log->logSavePath);
        date_default_timezone_set(ConfigLoader::CFG()->App->timeZone);
        return ConfigLoader::CFG();
    }

    /**
     * enable HTML cache opreate, the cache will use {@see Toknot\View\Renderer}
     * class provided of cache opreate {@see Toknot\View\Renderer::dispaly} 
     * when twice request interval time less one threshold could output the first 
     * save html file, so {@see Toknot\View\ViewCache::registerDisplayHandle} 
     * register what is {@see Toknot\View\Renderer::dispaly}
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
        StandardAutoloader::importToknotModule('Db', 'DbCRUD');
        return ActiveRecord::singleton();
    }

    /**
     * Instance of {@see Toknot\View\Renderer}
     * 
     * @return Toknot\View\Renderer
     */
    public function newTemplateView(& $CFG) {
        $this->D = new ArrayObject;
        StandardAutoloader::importToknotClass('View\Renderer');
        Renderer::$cachePath = FileObject::getRealPath($this->appRoot, $CFG->templateCompileFileSavePath);
        Renderer::$fileExtension = $CFG->templateFileExtensionName;
        Renderer::$scanPath = FileObject::getRealPath($this->appRoot, $CFG->templateFileScanPath);
        Renderer::$htmlCachePath = FileObject::getRealPath($this->appRoot, $CFG->htmlStaticCachePath);
        Renderer::$outCacheThreshold = $CFG->defaultPrintCacheThreshold;
        Renderer::$dataCachePath = $CFG->dataCachePath;
        return Renderer::singleton();
    }

    /**
     * Set template variable
     * 
     * @param string $name
     * @param mixed $value
     */
    public function setViewVar($name, $value) {
        $this->D->$name = $value;
    }

    /**
     * Readonly property support
     * 
     * @param string $name
     * @return mixed
     */
    public function &__get($name) {
        $readOnlyList = array('D', 'controller', 'appRoot',
            'appNamespace', 'requestMethod');
        if (in_array($name, $readOnlyList)) {
            return $this->$name;
        }
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
     * @param boolean $filter Whether addslashes for value, default is true
     * @return string
     */
    public function getParam($index, $filter = true) {
        if (count($this->uriOutRouterPath) <= $index) {
            return null;
        }
        if ($filter) {
            return addslashes($this->uriOutRouterPath[$index]);
        } else {
            return $this->uriOutRouterPath[$index];
        }
    }

    /**
     * get value of $_GET and use addslashes
     * 
     * @param string $name
     * @return null|string
     */
    public function getGET($name) {
        if (empty($_GET[$name])) {
            return null;
        } else if (self::$magicQuotesGpc) {
            return $_GET[$name];
        } else {
            return addslashes($_GET[$name]);
        }
    }

    /**
     * get value of $_POST and use addslashes
     * 
     * @param string $name
     * @return null|string
     */
    public function getPOST($name) {
        if (empty($_POST[$name])) {
            return null;
        } else if (self::$magicQuotesGpc) {
            return $_POST[$name];
        } else {
            return addslashes($_POST[$name]);
        }
    }

    /**
     * get value of $_POST and use addslashes
     * 
     * @param string $name
     * @return null|string
     */
    public function getCOOKIE($name) {
        if (empty($_COOKIE[$name])) {
            return null;
        } else if (self::$magicQuotesGpc) {
            return $_COOKIE[$name];
        } else {
            return addslashes($_COOKIE[$name]);
        }
    }

    /**
     * Get current user access status and default the controller is current accessed
     * 
     * @param \Toknot\User\ClassAccessControl $clsObj check current user whether access $clsObj 
     * @return boolean if allow access return true otherise false
     */
    public function getAccessStatus($clsObj) {
        if ($clsObj !== null) {
            $this->checkAccess($clsObj);
        } elseif ($this->controller instanceof ClassAccessControl) {
            $this->checkAccess($this->controller);
        }
        return $this->accessControlStatus;
    }

    /**
     * Register a controller when the user access denied be invoked GET method
     * 
     * @param string $controllerName
     */
    public function registerForbiddenController($controllerName) {
        if ($controllerName) {
            if (!Router::checkController($controllerName, $this->requestMethod)) {
                throw new Exception\ControllerInvalidException($controllerName);
            }
            $this->forbiddenController = $controllerName;
        }
    }

    public function registerNoPermissonController($controllerName) {
        if ($controllerName) {
            if (!Router::checkController($controllerName, $this->requestMethod)) {
                throw new Exception\ControllerInvalidException($controllerName);
            }
            $this->noPermissionController = $controllerName;
        }
    }

    /**
     * Register a callable that it be call when before invoke controller method
     * 
     * @param callable $callable function or method of object
     */
    public function registerInvokeBeforeHandler(&$callable) {
        $this->invokeBeforeHandler = $callable;
    }

    /**
     * Register a callable that it be call when after invoke cotroller method
     * 
     * @param callable $callable
     */
    public function registerInvokeAfterHandler(&$callable) {
        $this->invokeAfterHandler = $callable;
    }

    /**
     * Redirect to Denided contriller
     * 
     * @param \Toknot\User\ClassAccessControl $class
     * @return boolean
     */
    public function throwForbidden() {
        ForbiddenException::$displayController = $this->getForbiddenController();
        ForbiddenException::$FMAI = $this;
        ForbiddenException::$method = $this->requestMethod;
        throw new ForbiddenException('Access Denied');
    }

    /**
     * Redirect to a controller, must contain namespace
     * 
     * @param string $class The class name without Controller of level namespace
     * @param string $queryString redirect url params sting
     * @access public
     */
    public function redirectController($class, $queryString = '') {
        $url = strtr($class, '\\', '/');
        if(Router::getSelfInstance()->getRouterMode() === Router::ROUTER_GET_QUERY) {
            $url = "?c={$url}&{$queryString}";
        } elseif (!empty($queryString)) {
            $queryString = "?$queryString";
        }
        TK\header("Location:$url{$queryString}");
    }

    /**
     * Get current registered controller name of access denied 
     * 
     * @return string
     */
    public function getForbiddenController() {
        return Router::controllerNameTrans($this->forbiddenController);
    }

    /**
     * Check a user object whether can access class object be passed
     * 
     * @param \Toknot\User\ClassAccessControl $clsObj $clsObj check current user whether access $clsObj 
     */
    public function checkAccess(ClassAccessControl $clsObj) {
        $this->accessControlStatus = $clsObj->checkClassAccess();
        switch ($clsObj->getOperateType()) {
            case ClassAccessControl::CLASS_READ:
                $this->accessControlStatus = $clsObj->checkRead($this->currentUser);
                break;
            case ClassAccessControl::CLASS_WRITE:
                $this->accessControlStatus = $clsObj->checkWrite($this->currentUser);
                break;
            case ClassAccessControl::CLASS_UPDATE:
                $this->accessControlStatus = $clsObj->checkChange($this->currentUser);
                break;
            default :
                $this->accessControlStatus = true;
                break;
        }
    }

    public function throwNoPermission($message = null) {
        throw new NoPermissionExecption("No Permission Access {$message}");
    }

    /**
     * get sub action name
     * 
     * @return string
     */
    public function getSubAction() {
        if (!($subActionName = $this->getParam(0, false))) {
            $subActionName = 'index';
        }
        return $subActionName;
    }

    /**
     * invoke Sub Action for custom method of Controller
     * the method will check User Access permissions
     * 
     * @param \Toknot\User\ClassAccessControl $clsObj
     * @return null
     */
    public function invokeSubAction(ClassAccessControl &$clsObj) {
        $subActionName = $this->getSubAction();
        if (method_exists($clsObj, $subActionName)) {
            $clsObj->updateMethodPerms($subActionName);
            if ($this->getAccessStatus($clsObj)) {
                return $clsObj->$subActionName();
            }
            $this->throwNoPermission("$clsObj::$subActionName()");
        } else {
            $invokeClass = null;
            Router::singleton()->invokeNotFoundController($invokeClass);
            return Router::singleton()->instanceController($invokeClass, $this, $this->requestMethod);
        }
    }

    /**
     * Set current user object
     * 
     * @param Tokont\User\UserClass|Toknot\User\Root $user
     */
    public function setCurrentUser($user = null) {
        if ($user instanceof UserAccessControl || $user instanceof Root) {
            $this->currentUser = $user;
        }
    }

    public function isNobodyUser() {
        return $this->currentUser instanceof Nobody;
    }

    /**
     * 
     * @return type
     */
    public function getCurrentUser() {
        return $this->currentUser;
    }

    /**
     * Start toknot session object
     * 
     * @param type $name
     * @return \Toknot\User\Session
     */
    public function &startSession($name = null) {
        $name = $name ? $name : ConfigLoader::CFG()->Session->sessionName;
        $session = Session::singleton();
        $session->name($name);
        $session->start();
        return $session;
    }

    /**
     * Get Application are registed root namespace
     * 
     * @return string
     */
    public function getAppNamespace() {
        return $this->appNamespace;
    }

    /**
     * get php script exec time
     * 
     * @return float
     */
    public static function getCurrentExecTime() {
        return microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'];
    }

}
