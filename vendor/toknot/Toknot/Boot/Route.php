<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2017 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Boot;

abstract class Route extends Object {

    /**
     * find current request match route
     * 
     * @return  return match request of middleware 
     */
    abstract public function match();

    /**
     * Get Request instance of current http request
     * @return \Toknot\Share\Request
     */
    abstract public function getRequest();

    /**
     * Load route map of config
     *  
     * @return array            array of the route map
     */
    abstract public function load();

    /**
     * Generate url of a route
     * 
     * @param string $route     The route name
     * @param array $params     The route params
     * @return string           url of route
     */
    abstract public function url($route, $params);

    /**
     * Get route support namespace of all middeware
     * 
     * @param array $nsCfg      The config of namespace
     * @return array            return array of namespace of middeware, key is middeware type
     */
    abstract public function middlewareNamespace($nsCfg);
}
