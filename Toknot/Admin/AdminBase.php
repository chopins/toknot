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

class AdminBase extends ClassAccessControl {

	protected $permissions = 0770;
	protected static $FMAI = null;
	protected $AR = null;
	protected static $CFG = null;
	protected $dbConnect = null;
	private static $adminConstruct = false;
	protected $SESSION = null;

	public function __construct(FMAI $FMAI) {
		if (self::$adminConstruct) {
			return;
		}
		self::$adminConstruct = true;
		self::$FMAI = $FMAI;
		$this->loadAdminConfig();
		$this->initDatabase();
		$this->SESSION = $FMAI->startSession(self::$CFG->Admin->adminSessionName);

		$FMAI->registerAccessDeniedController('Toknot\Admin\Login');

		$user = $this->checkUserLogin();
		$FMAI->checkAccess($this, $user);

		$this->commonTplVarSet();
		$FMAI->newTemplateView(self::$CFG->View);
		if ($FMAI->redirectAccessDeniedController($this)) {
			exit();
		}
	}

	public function commonTplVarSet() {
		self::$FMAI->D->title = 'ToKnot Admin';
		self::$FMAI->D->toknotVersion = Version::VERSION . '-' . Version::STATUS;
	}

	public function initDatabase() {
		$this->AR = self::$FMAI->getActiveRecord();
		$dbSectionName = self::$CFG->Admin->databaseOptionSectionName;
		if (self::$CFG->Admin->multiDatabase) {
			$i = 0;
			while (true) {
				$section = self::$CFG->Admin->databaseOptionSectionName . $i;
				if (!isset(self::$CFG->$section)) {
					break;
				}
				$this->AR->config(self::$CFG->$section);
				$this->dbConnect[$i] = $this->AR->connect();
				$i++;
			}
			if (empty(self::$CFG->Admin->userTableDatabaseId)) {
				UserClass::$DBConnect = $this->dbConnect[0];
			}
		} else {
			$this->AR->config(self::$CFG->$dbSectionName);
			$this->dbConnect = $this->AR->connect();
			UserClass::$DBConnect = $this->dbConnect;
		}
	}

	public function loadAdminConfig() {
		if (!file_exists(self::$FMAI->appRoot . '/Config/config.ini')) {
			throw new FileIOException('must create ' . self::$FMAI->appRoot . '/Config/config.ini');
		}
		ConfigLoader::$cacheFile = self::$FMAI->appRoot . '/Data/config';
		self::$CFG = self::$FMAI->loadConfigure(self::$FMAI->appRoot . '/Config/config.ini');
	}

	public function CLI() {
		$this->GET();
	}

	public function checkUserLogin() {
		if (isset($_SESSION['adminUser']) && isset($_SESSION['Flag'])) {
			$user = unserialize($_SESSION['adminUser']);
			if ($user->checkUserFlag($_SESSION['Flag'])) {
				return $user;
			}
		} elseif (isset($_COOKIE['uid']) && isset($_COOKIE['Flag']) && isset($_COOKIE['TokenKey'])) {
			$user = UserClass::checkLogin($_COOKIE['uid'], $_COOKIE['Flag'], $_COOKIE['TokenKey']);
			if ($user) {
				return $user;
			}
		}
		return new Nobody;
	}

	protected function setAdminLogin(UserAccessControl $user) {
		$_SESSION['Flag'] = $user->generateUserFlag();
		$_SESSION['adminUser'] = serialize($user);
		if ($user->loginExpire > 0) {
			setcookie('uid', $user->getUid(), $user->loginExpire);
			setcookie('Flag', $_SESSION['Flag'], $user->loginExpire);
			setcookie('TokenKey', $user->generateLoginKey(), $user->loginExpire);
		} else {
			setcookie('Flag',$_SESSION['Flag']);
		}
	}

}

?>
