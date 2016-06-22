<?php return array (
  'Log' => 
  array (
    'enableLog' => '',
    'logSavePath' => 'var/log',
  ),
  'Database' => 
  array (
    'dsn' => '',
    'username' => '',
    'password' => '',
    'dirverOptions' => 
    array (
      0 => '',
    ),
    'tablePrefix' => '',
    'databaseStructInfoCache' => 'var/database/databaseTaleListCache',
    'databaseStructInfoCacheExpire' => '3600',
    'databaseTableStructCache' => 'var/database/tableStructCache',
    'databaseTableStructCacheExpire' => '3600',
  ),
  'View' => 
  array (
    'templateFileScanPath' => 'View',
    'templateCompileFileSavePath' => 'var/view/compile',
    'templateFileExtensionName' => 'html',
    'htmlStaticCachePath' => 'var/view/HTML',
    'dataCachePath' => 'var/view/Data',
    'defaultPrintCacheThreshold' => '2',
  ),
  'User' => 
  array (
    'allowRootLogin' => '',
    'rootPassword' => '',
    'userPasswordEncriyptionAlgorithms' => 'sha512',
    'userPasswordEncriyptionSalt' => 'salt',
    'userTableName' => 'user',
    'userIdColumnName' => 'uid',
    'userNameColumnName' => 'username',
    'userGroupIdColumnName' => 'gid',
    'userPasswordColumnName' => 'password',
  ),
  'Session' => 
  array (
    'sessionName' => '_TKSID',
    'fileStoreSession' => '1',
    'fileStorePath' => 'var/session',
    'maxLifeTime' => '3600',
  ),
  'Admin' => 
  array (
    'adminSessionName' => '_TKASID',
    'databaseOptionSectionName' => 'Database',
    'adminUseIniNavigationConfig' => '1',
    'adminNavigationListTable' => 'tk_navigation_menu',
    'adminNavigationListIniFile' => 'navigation.ini',
  ),
  'App' => 
  array (
    'rootNamespace' => '\\TaskHub',
    'timeZone' => 'UTC',
    'notFoundController' => '',
    'methodNotAllowedController' => '',
    'defaultInvokeController' => '\\Index',
    'forbiddenController' => '',
    'noPermissionController' => '',
    'routerMode' => 'ROUTER_PATH',
    'routerDepth' => '0',
    'beforeInvokeController' => '',
    'afterInvokeController' => '',
  ),
);