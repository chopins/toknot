<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Control;

use Toknot\Di\Object;
use Toknot\View\Renderer;
use Toknot\Config\ConfigLoader;
use Toknot\Db\ActiveRecord;
use Toknot\View\XML;
use Toknot\View\ViewCache;

/**
 * Framework Module Access Interfaces
 */
final class FMAI extends Object {

    /**
     * The propertie is set variable of \Toknot\View\Renderer use template, because 
     * {@see \Toknot\View\Renderer::$varList} use ArrayObject, so available to Application 
     * one Array of set way instead ArrayObject
     *
     * @var array 
     */
    public $D = array();
    protected $uriOutRouterPath = null;

    public static function singleton() {
        return parent::__singleton();
    }

    /**
     * construct Framework Module Access Interfaces object instance, the instance be passed
     * when invoke Application Controller and construct
     * The method will load framework default configure file
     * 
     */
    protected function __construct() {
        ConfigLoader::singleton();
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

    /**
     * Load configure file
     * 
     * @param string $ini
     * @return ArrayObject
     */
    public function loadConfigure($ini) {
        return ConfigLoader::loadCFG($ini);
    }

    /**
     * enable HTML cache opreate, the cache will use Toknot\View\Renderer class provided of cache opreate
     * {@see Toknot\View\Renderer::dispaly} when twice request interval time less one threshold could output
     * the first save html file, so {@see ViewCache::registerDisplayHandle} register what is 
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
     *      //set template file of directory
     *      $view->scanPath = __DIR__ . '/View';
     * 
     *      //set transfrom to php file save of directory
     *      $view->cachePath = __DIR__ . '/Data/View';
     * 
     *      //set template file extension
     *      $view->fileExtension = 'html';
     *      
     *      //enable Toknot\View\Renderer write html data to disk
     *      $view->enableHTMLCache = true;
     * 
     *      //set Toknot\View\Renderer write html file of save path
     *      $view->htmlCachePath = __DIR__ . '/Data/HTML';
     * 
     *      //set update html file time of seconds
     *      $view->outCacheThreshold = 5;
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
    public function enableHTMLCache() {

        ViewCache::$enableCache = true;
        $view = $this->newTemplateView();
        ViewCache::setRenderer($view);
        ViewCache::registerDisplayHandle('outPutHTMLCache');
    }

    /**
     * set cache file be use when View class output cache, if use {@see Toknot\View\Renderer}
     * accomplish View layer page renderer class and will here is set template file name like use 
     * {@see FMAI::display()}
     * 
     * @param string $file
     */
    public function setCacheFile($file) {
        ViewCache::setCacheFile($file);
    }

    /**
     * Instance of \Toknot\Db\ActiveRecord
     * 
     * @return \Toknot\Db\ActiveRecourd
     */
    public function getActiveRecord() {
        return ActiveRecord::singleton();
    }

    /**
     * Instance of \Toknot\View\Renderer
     * 
     * @return \Toknot\View\Renderer
     */
    public function newTemplateView() {
        return Renderer::singleton();
    }

    public function newXMLView() {
        return XML::singleton();
    }

    public function newJSONView() {
        
    }

    public function newPictureView() {
        
    }

    /**
     * Use {@see \Toknot\View\Renderer} output HTML 
     * 
     * @param string $tplName  The template file name which without extension name 
     *                          and without {@see Renderer::scanPath} set path
     */
    public function display($tplName) {
        $view = Renderer::singleton();
        $view->importVars($this->D);
        $view->display($tplName);
    }

    public function getParam() {
        
    }

}