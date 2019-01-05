<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2018 chopin xiao (xiao@toknot.com)
 */

namespace Toknot\Lib\Model\Auth;

use SessionHandlerInterface;
use Toknot\Lib\IO\Request;
use Toknot\Boot\ArrayObject;
use Toknot\Boot\Kernel;

class Session extends ArrayObject implements SessionHandlerInterface {

    protected $manual = false;
    protected $definedName = 'TSID';
    protected $id = '';
    protected $opHander = null;
    protected $lifetimeKey = '_lifetime';
    protected $kernel = null;

    public function __construct($opHandler, $name = null) {
        $this->kernel = Kernel::instance();
        session_set_save_handler($this, true);
        $this->definedName = $name === null ? $this->definedName : $name;
        $this->opHander = $opHandler;
        $this->name($this->definedName);
        $this->start();
    }

    public function start() {
        $status = session_status();
        if ($status === PHP_SESSION_ACTIVE) {
            $this->id = session_id();
            return;
        } elseif ($status === PHP_SESSION_NONE) {
            $this->manual = !session_start();
            if ($this->manual) {
                $id = $this->generateId();
                $this->id($id);
            } else {
                $this->id = session_id();
            }
        } elseif ($status === PHP_SESSION_DISABLED) {
            $this->manual = true;
        }
        parent::__construct($_SESSION);
    }

    public function get($key) {
        return $_SESSION[$key];
    }

    public function set($key, $value) {
        $_SESSION[$key] = $value;
    }

    public function offsetSet($key, $value) {
        $_SESSION[$key] = $value;
        parent::offsetSet($key, $value);
    }

    public function offsetExists($key) {
        return array_key_exists($key, $_SESSION);
    }

    public function offsetUnset($key) {
        unset($_SESSION[$key]);
        parent::offsetUnset($key);
    }

    public function offsetGet($key) {
        return $_SESSION[$key];
    }

    public function name($name = null) {
        if ($name === null) {
            return $this->getName();
        }
        $this->definedName = $name;
        return session_name($name);
    }

    public function id($id = null) {
        if ($id === null) {
            return $this->getId();
        }
        $this->id = $id;
        return session_id($id);
    }

    public function close() {
        if ($this->manual) {
            $this->manualSetCookie();
        }
        return true;
    }

    public function destroy($sessionId) {
        return $this->opHander->destory($sessionId);
    }

    public function gc($maxlifetime) {
        return $this->opHander->gc($maxlifetime);
    }

    public function open($savePath, $name) {
        try {
            return $this->opHander->open($savePath, $name);
        } catch (\Exception $e) {
            $this->kernel->echoException($e);
            return false;
        }
    }

    public function read($sessionId) {
        try {
            return $this->opHander->read($sessionId);
        } catch (\Exception $e) {
            $this->kernel->echoException($e);
            return false;
        }
    }

    public function write($sessionId, $sessionData) {
        try {
            return $this->opHander->write($sessionId, $sessionData);
        } catch (\Exception $e) {
            $this->kernel->echoException($e);
            return false;
        }
    }

    protected function getName() {
        if ($this->manual) {
            return $this->definedName;
        } else {
            return session_name();
        }
    }

    protected function getId() {
        if ($this->manual) {
            return Request::cookie()->value($this->definedName);
        } else {
            return session_id();
        }
    }

    protected function manualSetCookie() {
        setcookie($this->definedName, $this->id);
    }

    protected function generateId() {
        return sha1(Request::requestHash());
    }

    public function __destruct() {
        $this->close();
    }

}
