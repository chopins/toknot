<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Admin;

use Toknot\Control\FMAI;
use Toknot\User\ClassAccessControl;
use Toknot\Exception\FileIOException;
use Toknot\Config\ConfigLoader;
use Toknot\User\Nobody;
use Toknot\User\UserClass;
use Toknot\Di\Version;
use Toknot\User\UserAccessControl;
use Toknot\Control\ControllerInterface AS CI;

/**
 * Admin module base class for user's admin application
 */
abstract class AdminBase extends ClassAccessControl implements CI\ControllerInterface{

    /**
     * the controller permission, 8bit number like uninx
     *
     * @var integer 
     * @access protected
     */
    protected $permissions = 0770;

    /**
     * {@see Toknot\Control\FMAI} instance
     *
     * @var Toknot\Control\FMAI
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
     * @var Toknot\Di\ArrayObject
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
     * {@see Toknot\User\Session} instance
     *
     * @var Toknot\User\Session
     */
    protected $SESSION = null;
    private static $adminConstruct = false;
    protected $currentUser = null;

    public function __construct(FMAI $FMAI) {
        if (self::$adminConstruct) {
            return;
        }
        self::$adminConstruct = true;
        self::$FMAI = $FMAI;
        $this->loadAdminConfig();
        $this->initDatabase();        

        $this->SESSION = $FMAI->startSession(self::$CFG->Admin->adminSessionName);

        $user = $this->checkUserLogin();
        $FMAI->setCurrentUser($user);
        $this->currentUser = $user;
        
        if($FMAI->getAccessStatus($this) === false) {
            if($FMAI->isNobodyUser()) {
                $FMAI->redirectController('\User\Login');
            }
            $FMAI->throwNoPermission($this);
        }
        $FMAI->newTemplateView(self::$CFG->View);
        
    }

    /**
     * set view value
     */
    public function commonTplVarSet() {
        self::$FMAI->D->title = 'ToKnot Admin';
        self::$FMAI->D->toknotVersion = Version::VERSION . '-' . Version::STATUS;
        self::$FMAI->D->currentUser = $this->currentUser;
    }

    /**
     * init database connect
     */
    public function initDatabase() {
        $this->AR = self::$FMAI->getActiveRecord();
        $dbSectionName = self::$CFG->Admin->databaseOptionSectionName;
        $this->AR->config(self::$CFG->$dbSectionName);
        $this->dbConnect = $this->AR->connect();
        UserClass::$DBConnect = $this->dbConnect;
    }

    /**
     * load admin application config
     * 
     * @throws FileIOException
     */
    public function loadAdminConfig() {
        if (!file_exists(self::$FMAI->appRoot . '/Config/config.ini')) {
            throw new FileIOException('must create ' . self::$FMAI->appRoot . '/Config/config.ini');
        }
        ConfigLoader::$cacheFile = self::$FMAI->appRoot . '/Data/config';
        self::$CFG = self::$FMAI->loadConfigure(self::$FMAI->appRoot . '/Config/config.ini');
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
     * @return \Toknot\User\Nobody
     */
    public function checkUserLogin() {
        if (isset($_SESSION['adminUser']) && isset($_SESSION['Flag'])) {
            $user = unserialize($_SESSION['adminUser']);
            if ($user->checkUserFlag($_SESSION['Flag'])) {
                return $user;
            }
        } elseif (null !== self::$FMAI->getCOOKIE('uid') && null !== $_COOKIE['Flag'] && null !== self::$FMAI->getCOOKIE('TokenKey')) {
            $user = UserClass::checkLogin(self::$FMAI->getCOOKIE('uid'), self::$FMAI->getCOOKIE('Flag'), self::$FMAI->getCOOKIE('TokenKey'));
            if ($user) {
                return $user;
            }
        }
        return new Nobody;
    }

    /**
     * Set user login
     * 
     * @param \Toknot\User\UserAccessControl $user
     */
    protected function setAdminLogin(UserAccessControl $user) {
        $_SESSION['Flag'] = $user->generateUserFlag();
        $_SESSION['adminUser'] = serialize($user);
        if ($user->loginExpire > 0) {
            setcookie('uid', $user->getUid(), $user->loginExpire);
            setcookie('Flag', $_SESSION['Flag'], $user->loginExpire);
            setcookie('TokenKey', $user->generateLoginKey(), $user->loginExpire);
        } else {
            setcookie('Flag', $_SESSION['Flag']);
        }
    }

}

?>
