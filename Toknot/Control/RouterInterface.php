<?php
/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Control;

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
     */
    public function invoke();
    
    /**
     * add application namespace
     * 
     * @param string $appspace
     */
    public function routerSpace($appspace);
    
    /**
     * set router runtime all args or configure
     */
    public function runtimeArgs();
}

?>
