<?php
/**
 * Toknot
 * initialization and load frameworker
 *
 * PHP version 5.3
 * 
 * @author chopins xiao <chopins.xiao@gmail.com>
 * @copyright  2012 The Authors
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @link       http://blog.toknot.com
 * @since      File available since Release $id$
 */

/*
   Can define constants
    __X_IN_FRAME__          only set true,
    __X_SHOW_ERROR__        if set true will show erro message
    __X_APP_ROOT__          your Application directory

    __X_EXCEPTION_LEVEL__   set error level show format via exception
                                0 all error
                                1 notice message will not display that use exception
                                2 Warning and notice will not display  that use exception
    __X_APP_DATA_DIR_NAME__ Application data directory name, inside __X_APP_ROOT__ defined path

    __X_APP_DATA_DIR__      Application data obsolete path,if defined constants __X_APP_DATA_DIR_NAME__ will not use

    __X_APP_USER_CONF_FILE_NAME__  Application configuration file name,
                                    inside directory name is conf of sub directory of __X_APP_DATA_DIR__ defined path

    __X_NO_WEB_SERVER__   disable webserver of frameworker, even though run by CLI mode
                          if run CLI mode，and it defined true, when exec and given -d option，
                          script will run in daemon mode, and call user's Loop-Script, 
                          Loop-Script file defined by __X_DAEMON_LOOP_FILE__

    __X_DAEMON_LOOP_FILE__  daemon Loop-Script file, the file is relative path that inside php_dir_name that is defined
                            at Application configuration file, and it only is directory name, it inside __X_APP_ROOT__

    __X_FIND_SLOW__         enable SLOW-RUN-TRACK, if one statement run time greater than 1s, 
                            will stored in tpl-variable that name is __X_RUN_TIME__
                          
*/

define('__X_IN_FRAME__', true);
define('__X_SHOW_ERROR__',true);
define('__X_APP_ROOT__', dirname(__FILE__));
define('__X_EXCEPTION_LEVEL__',2);
define('__X_APP_DATA_DIR_NAME__','var');
define('__X_APP_USER_CONF_FILE_NAME__','config.ini');
define('__X_APP_DATA_DIR__',__X_APP_ROOT__.'/'.__X_APP_DATA_DIR_NAME__);
define('__X_NO_WEB_SERVER__', false);
define('__X_FIND_SLOW__', false);
define('__X_DAEMON_LOOP_FILE__','/daemon.php');

//include __init__.php of toknot frameworker
include_once(dirname(dirname(__FILE__)).'/php/__init__.php');
