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

class XCreateApp {
    public $toknot_path = null;
    public function __construct($app_path) {
        $app_path = realpath($app_path);
        if(!is_dir($app_path)) {
            if(file_exists($app_path)) die("$app_path is exists and is not directory");
            mkdir($app_path);
        }
        mkdir("{$app_path}/model");
        mkdir("{$app_path}/php");
        mkdir("{$app_path}/ui");
        mkdir("{$app_path}/ui/js");
        mkdir("{$app_path}/ui/css");
        mkdir("{$app_path}/ui/html");
        mkdir("{$app_path}/ui/json");
        mkdir("{$app_path}/ui/xml");
        mkdir("{$app_path}/var");
        mkdir("{$app_path}/var/cache");
        mkdir("{$app_path}/var/compile_tpl");
        mkdir("{$app_path}/var/conf");
        mkdir("{$app_path}/var/db");
        mkdir("{$app_path}/var/log");
        mkdir("{$app_path}/var/run");
        mkdir("{$app_path}/var/session");
        $this->get_toknot_reletive_path($app_path);
        file_put_contents("{$app_path}/run.php",$this->get_run_php_code());
        file_put_contents("{$app_path}/var/conf/config.ini",$this->get_default_config_code($app_path));
        echo "Application initialization success\r\n";
        echo "application create at {$app_path}\r\n";
        echo "config.ini of application inside {$app_path}/var/conf/\r\n";
        echo "set application static path is {$app_path}/static, if update, edit tpl.static_dir_name option at config.ini\r\n";
        echo "more message see {$app_path}/run.php and {$app_path}/var/conf/config.ini\r\n";
        echo "Goodluck!\r\n";
        exit(0);
    }
    public function get_toknot_reletive_path($app_path) {
        $toknot_path = dirname(__FILE__);
        $tlen = strlen($toknot_path);
        $alen = strlen($app_path);
        $len = min($tlen, $alen);
        $same = '';
        for($i=0; $i<$len; $i++) {
            if($toknot_path[$i] == $app_path[$i]) {
                $same .= $app_path[$i];
            } else {
                break;
            }
        }
        if(substr($same,-1) != DIRECTORY_SEPARATOR) {
            $same = dirname($same);
        }
        $ds = 'dirname(__FILE__)');
        $i = 1;
        while(true) {
            $path = dirname($app_path);
            if($path == $same) {
                break;
            }
            $ds = "dirname({$ds})";
            $i++;
        }
        if($i > 3) {
            $this->toknot_path = "$toknot_path".DIRECTORY_SEPARATOR."__init__.php";
        } else {
            $this->toknot_path = $ds.DIRECTORY_SEPARATOR.str_replace($same,'',$toknot_path).DIRECTORY_SEPARATOR."__init__.php"
        }
    }
    public function get_default_config_code($app_path) {
        $default_ini = dirname(__FILE__).DIRECTORY_SEPARATOR.'toknot.def.ini';
        $ini_content = file($default_ini);
        $return_content = '';
        foreach($ini_content as $line_no=>$line) {
            if($line_no <= 5) continue;
            if($line_no == 243) {
                $line = "tpl.static_dir_name = {$app_path}/static";
            } elseif(substr($line,1) != ';') {
                $line = ";$line";
            }
            $return_content .= $line;
        }
        return $return_content;
    }
    public function get_run_php_code() {
        $code = <<<EOF
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
 * @since      File available since Release \$id\$
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
define('__X_DAEMON_LOOP_FILE__',false);

//include __init__.php of toknot frameworker
include_once({$this->toknot_path});
EOF;
        return $code;
    }
}
if(empty($argv[1])) {
    echo "Error:Need set Application path\r\n";
    echo "such as:\r\n\tphp XCreateApp.php /home/yourname/application_name\r\n";
    return;
}
return new XCreateApp($argv[1]);
