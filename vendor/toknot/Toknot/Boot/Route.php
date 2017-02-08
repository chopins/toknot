<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
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
     * @param string $php       The file is route map config
     * @param string $params    load map file need params   
     * @return array            array of the route map
     */
    abstract public function load($php, $params);

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
