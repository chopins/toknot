<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2015 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Renderer;

use Toknot\Boot\Object;

class ViewCache extends Object {

	/**
	 * Cache use result
	 *
	 * @var integer
	 * @access public
	 * @static
	 */
    public static $cacheEffective = 0;

	/**
	 * need cache file tag name, the variable be set by renderer class
	 *
	 * @var string
	 * @access private
	 * @static
	 */
    private static $cacheFile = '';

	/**
	 * The class be rendered page
	 *
	 * @var Object
	 * @access private
	 * @static
	 */
    private static $renderer = null;

	/**
	 * The method of renderer class be print out page
	 *
	 * @var callable
	 * @access private
	 * @static
	 */
    private static $displayMethod = 'display';

	/**
	 * Whether eanbele cache, it is set option by application
	 *
	 * @var boolean
	 * @access public
	 * @static
	 */
    public static $enableCache = false;

	const CACHE_USE_SUCC = 200;

	/**
	 * register dispaly method of the renderer class
	 * 
	 * @param string $method
	 * @access public
	 * @static
	 */
	public static function registerDisplayHandle($method) {
        self::$displayMethod = $method;
    }

	/**
	 * dispaly page, the common be auto invoke in {@see Toknot\Share\FMAI}
	 * 
	 * @access public
	 * @static
	 */
    public static function outPutCache() {
        if(empty(self::$cacheFile)) return;
		$displayMethod = self::$displayMethod;
		self::$cacheEffective = self::$renderer->$displayMethod(self::$cacheFile);
    }

	/**
	 * Set renderer object
	 * 
	 * @param object $object
	 */
    public static function setRenderer(& $object) {
        self::$renderer = $object;
    }

	/**
	 * set cache file tag
	 * 
	 * @param string $file
	 */
    public static function setCacheFile($file) {
        self::$cacheFile = $file;
    }
    
}