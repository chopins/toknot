<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2017 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Share\Session;

use Toknot\Boot\Kernel;
use Toknot\Boot\Object;
use Symfony\Component\HttpFoundation\Session\Session as SSession;
use Toknot\Share\Session\DBSessionHandler;
use Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage;

/**
 * Description of SessionDBStrogle
 *
 * @author chopin
 */
class Session extends Object {

    /**
     *
     * @var \Symfony\Component\HttpFoundation\Session\Session
     */
    protected $session;

    public function __construct() {
        $option = Kernel::single()->cfg->app->session;
        $hander = new DBSessionHandler($option->table);

        $stroage = new NativeSessionStorage($option->toArray(), $hander);
        $this->session = new SSession($stroage);
    }

    public function start() {
        $this->session->start();
    }

    /**
     * 
     * @return \Symfony\Component\HttpFoundation\Session\Session
     */
    public function getSession() {
        return $this->session;
    }

}
