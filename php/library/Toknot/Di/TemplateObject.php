<?php
/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Di;

use Toknot\Di\Object;
use Toknot\View\Renderer;

class TemplateObject extends Object {
    /**
     * name 
     * the template file name
     * 
     * @var mixed
     * @access public
     */
    public $name = null;

    /**
     * type 
     * the template filetype
     * 
     * @var mixed
     * @access public
     */
    public $type = 'htm';

    /**
     * data_cache 
     * only cache view data
     * 
     * @var mixed
     * @access public
     */
    public $dataCache = false;

    /**
     * cache_time 
     * the cache data or file expires seconds if open cache, and default 300 seconds
     * 
     * @var float
     * @access public
     */
    public $cacheTime = 300;

    /**
     * static_cache 
     * save view-class output html to file if be set true
     * 
     * @var mixed
     * @access public
     */
    public $staticCache = false;

    /**
     * TPL_INI 
     * configuration for tpl
     * 
     * @var mixed
     * @access public
     */
    public $TPL_INI;
    private $cache_dir;
    public $be_cache = false;
    public function __construct($TPL_INI, $cache_dir) {
        $this->TPL_INI = $TPL_INI;
        $this->cache_dir = $cache_dir;
    }
    public function check_cache() {
        $ins = XTemplate::singleton($this->TPL_INI);
        $ins->set_cache_dir($this->cache_dir);
        $this->be_cache = $ins->get_cache($this);
    }
}
