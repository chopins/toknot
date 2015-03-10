<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Lib\Admin;

use Toknot\Lib\User\ClassAccessControl;
use Toknot\Lib\User\Nobody;
use Toknot\Lib\User\UserClass;
use Toknot\Boot\Version;
use Toknot\Lib\User\UserAccessControl;
use Toknot\Config\ConfigLoader;
use Toknot\Db\ActiveRecord;

/**
 * Admin module base class for user's admin application
 */
abstract class AdminBase extends ClassAccessControl{

    /**
     * the controller permission, 8bit number like uninx
     *
     * @var integer 
     * @access protected
     */
    protected $permissions = 0770;

    /**
     * {@see Toknot\Boot\FMAI} instance
     *
     * @var Toknot\Boot\FMAI
     * @access protected
     * @static
     */
    protected static $FMAI = null;

    /**
     * {@see Toknot\Db\ActiveRecord} instance
     *
     * @var Toknot\Db\ActiveRecord
     * @access protected
     */
    protected $AR = null;

    /**
     * Object of the configure data
     *
     * @var Toknot\Boot\ArrayObject
     * @access protected
     * @static
     */
    protected static $CFG = null;

    /**
     * The database connect instance of {@see Toknot\Db\DatabaseObject}, 
     * if use multi-database, the property will is array store connect instance
     *
     * @var mixed
     */
    protected $dbConnect = null;

    /**
     * {@see Toknot\Lib\User\Session} instance
     *
     * @var Toknot\Lib\User\Session
     */
    protected $SESSION = null;
    protected $currentUser = null;

    public function __init() {
        //self::$FMAI = $FMAI;
        self::$CFG = ConfigLoader::CFG();
        $this->initDatabase();        

        $this->SESSION = $this->startSession(self::$CFG->Admin->adminSessionName);
        $user = $this->checkUserLogin();
        $this->setCurrentUser($user);
        $this->currentUser = $user;
        
        if($this->getAccessStatus($this) === false) {
            if($this->isNobodyUser()) {
                $this->redirectController('\User\Login');
            }
            $this->throwNoPermission($this);
        }
        $this->newTemplateView(self::$CFG->View);
        $this->commonTplVarSet();
    }

    /**
     * set view value
     */
    public function commonTplVarSet() {
        $this->D->title = 'ToKnot Admin';
        $this->D->toknotVersion = Version::VERSION . '-' . Version::STATUS;
        $this->D->currentUser = $this->currentUser;
    }

    /**
     * init database connect
     */
    public function initDatabase() {
        $this->AR = ActiveRecord::singleton();
        $dbSectionName = self::$CFG->Admin->databaseOptionSectionName;
        $this->AR->config(self::$CFG->$dbSectionName);
        $this->dbConnect = $this->AR->connect();
        UserClass::$DBConnect = $this->dbConnect;
    }

    /**
     * if CLI run, redirect to GET
     */
    public function CLI() {
        $this->GET();
    }
   
    /**
     * Check current visiter whether logined
     * 
     * @return \Toknot\Lib\User\Nobody
     */
    public function checkUserLogin() {
        if (isset($_SESSION['adminUser']) && isset($_SESSION['Flag'])) {
            $user = unserialize($_SESSION['adminUser']);
            if ($user->checkUserFlag($_SESSION['Flag'])) {
                return $user;
            }
        } elseif (null !== $this->getCOOKIE('uid') && null !== $_COOKIE['Flag'] && null !== $this->getCOOKIE('TokenKey')) {
            $user = UserClass::checkLogin($this->getCOOKIE('uid'), $this->getCOOKIE('Flag'), $this->getCOOKIE('TokenKey'));
            if ($user) {
                return $user;
            }
        }
        return new Nobody;
    }

    /**
     * Set user login
     * 
     * @param \Toknot\Lib\User\UserAccessControl $user
     */
    protected function setAdminLogin(UserAccessControl $user) {
        $_SESSION['Flag'] = $user->generateUserFlag();
        $_SESSION['adminUser'] = serialize($user);
        if ($user->loginExpire > 0) {
            setcookie('uid', $user->getUid(), $user->loginExpire,'/');
            setcookie('Flag', $_SESSION['Flag'], $user->loginExpire,'/');
            setcookie('TokenKey', $user->generateLoginKey(), $user->loginExpire,'/');
        } else {
            setcookie('Flag', $_SESSION['Flag'],0,'/');
        }
    }

}
