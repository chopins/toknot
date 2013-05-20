<?php
/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Control;
use Toknot\Control\FMAI;

/**
 * Router interface, allow appliection set self router 
 */
interface RouterInterface {
    
    /**
     * router rule method
     */
    public function routerRule();
    
    /**
     * invoke application object or method
     * 
     * @access public
     * @param FMAI $appContext  AppContext instance
     * @see FMAI
     */
    public function invoke(FMAI $appContext);
    
    /**
     * Add application top namespace
     * 
     * @param string $appspace
     */
    public function routerSpace($appspace);
    
    /**
     * set application directory
     * 
     * @param string $path
     */
    public function routerPath($path);

    /**
     * set router runtime all args or configure
     */
    public function runtimeArgs();
    
    /**
     * set default invoke class for router
     * 
     * @param string $defaultInvoke
     */
    public function defaultInvoke($defaultInvoke);
}

?>
