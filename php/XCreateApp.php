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
        mkdir("{$app_path}/var/error_log");
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
        $this->toknot_path =  str_replace($same,'',$toknot_path);
    }
    public function get_default_config_code($app_path) {
        return <<<EOF
;User Application Configuration
;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;
;[Base]
;the section define Application base directory info
;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;

;your Application php directory name, inside __X_APP_ROOT__
;php_dir_name = php

;model is common opreate method of database data;
;php_model_dir_name = model

;UI directory name, inside __X_APP_ROOT__
;your js and html template file stored it
;ui_dir_name = ui

;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;
;[App]
;Application data directory
;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;

;whether check data directory
;app.check_data_dir = true        

;temp data directory name, inside __X_APP_DATA_DIR__
;app.data_cache = cache 

;data cache stored file, inside app.data_cache,use php serialize storage
;app.cache_file = data_model_cache_file.dat

;error log message output file to locations directory name
;app.error_log_dir = error_log  

;Database file locations directory name
;app.db_data = db               


;daemon run directory, inside __X_APP_DATA_DIR__
;app.run_dir =           

;set yur application use timezone
;app.timezone = Asia/Chongqing  

;if use ajax,need a key be access request data of ajax
;app.ajax_key = data            

;the request is ajax and the flag is describe
;app.ajax_flag = is_ajax        

;your application output data use encoding
;   always frameworker opreate data use UTF-8
;   if not UTF-8, respones or request data will be encode to UTF-8
;app.encoding = utf8            

;exception log message segment string
;app.exception_seg_line = *****************************************************************


;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;
;Below Set About Access URL format
;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;

;url and link express file suffix, that look like real file
;app.url_file_suffix = php   

;if have multiple level domian and used, open it will can sub-domain-name route to view directory of 
; application, the level number max value equal access domian in url, the number determine start use level
;app.subsite_start_level = 1 

;open domian route mode, if set 0 is close ,otherwise greater than 0 that is router depth
;   the number is router max level
;app.subsite_mode = 0 

;URL format
;   0 is default mode, similar http://localhost/directory/.../CLASS_NAME/METHOD_NAME.php
;       need webserver always exec entrance file, the file suffix defined app.url_file_suffix
;    1 QUERY_STRING mode, similar http://localhost/index.php?/directory/.../CLASS_NAME/METHOD_NAME
;   2 PATH_INFO mode, similar http://localhost/index.php/directory/.../CLASS_NAME/METHOD_NAME 
;       need webserver support PATH_INFO
;   3 ENTIR_PATH mode，similar URL为http://localhost/directory/CLASS_NAME/METHOD_NAME
;       need webserver support rewite to default mode
;   4 MANUAL_DEFINE_LIST mode  the mode is 0 mode that use list defined
;app.uri_mode = 0

;max size of upload file
;app.upfile_size = 512000    

;Allowed upload file mime-type
;app.upfile_mime = image/jpeg;image/png;image/gif;image/bmp   


;Application runtime_config, inside conf directory locate __X_APP_DATA_DIR__
;this option allow php script modify on runtime, it not config.ini
;app.runtime_config = config_runtime.php 

;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;
;[web]
;webserver of frameworker configuration
;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;

;if true webserver run daemon, otherwise set false on develop
;web.daemon = false      

;listen ip
;web.ip = 127.0.0.1      

;listen port
;web.port = 8080         

;Web document root path,the option only determine static file, if not set,will 
;   use __X_APP_ROOT__ defined path
;web.document_root =     

;set worker process what can run min number
;web.min_worker_num = 1  

;set worker process what can run max number
;web.max_worker_num = 20 

;a worker process allow max number by connect
;web.worker_max_connect = 10

;webserver run pid file name, inside app.run_dir
;web.pid_file = xweb.pid   

;SOCKET
;web_socket_file = xweb_sock    

;set static file send header that Cache-Control time of browser
;   units: d is day, h is hours, m is minutes, s is seconds
;web.cache_control_time = 1d     

;browser request body max length, units: m is MiB, k is KiB
;web.request_body_length = 1m    

;upload file temp stored path
;web.upfile_tmp_dir =            

;default page file name
;web.index = index.php:index.html:index.htm      

;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;
;[cron]              
;Application cron
;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;

;class name
;cron.call_class =         

;cron exec file, obsolete path
;cron.class_file =         

;exec interval
; units: d is days, h is hours, m is minutes, s is seconds
;        D is day of every month, W is day of every month, 0 is Sunday, T is specified time such as 19:32T
;cron.time_interval =      

; exec times, if less than 1 or not set whill loop
;cron.times =              

;custom webserver, undone
;server_custom_server_process = process  

;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;
;[inotify]
;notify synchronously file service configuration
;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;

;watch list config file ,inside __X_APP_DATA_DIR__/conf
;inotify.watch_list_conf = sync.conf  

;synchronously log
;inotify.log_file = sync   


;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;
;[session]
;session configuration
;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;

;whether use php session extension, include extension of php provides
;session.use_php_session = true

;session.use_toknot_sess = false;

;set session name
;session.session_name = XSID   

;session file of session data stored directory name inside __X_APP_DATA_DIR__
;   include if use session extension with php and set save_handler is files 
;session.save_path = session

;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;
;Database configuration
;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;

;[MySQL]
;mysql.a.host = localhost
;mysql.a.dbname = test
;mysql.a.user = root
;mysql.a.password = 
;mysql.a.pconnect = false
;mysql.a.port = 3306;
;mysql.b.host = 
;mysql.b.dbname =
;mysql.b.user

;[Firebird]
;firebird.type = firebird_embedded

;firebird.data_dirname = firebird       

;[TxtDatabase]

;txtdb.data_dirname = txtdb
;txtdb.a.name = test_table;
;txtdb.b.name = test2_table;

;[TxtKVDatabase]
kvdb.data_dirname = kvdb

;[TextFileData]
text.data_dirname = text

;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;
;[TPL]
;template configuration
;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;

;when open HTML cache to file, HTML file save path, inside __X_APP_DATA_DIR__/app.data_cache
tpl.html_cache_dirname = html_cache

;when open view data cache, the serialize data save path, inside __X_APP_DATA_DIR__/app.data_cache
;tpl.data_cache_dirname = view_data_cache

;static file output path, this is obsolete path, and in web document root
; CSS, JS compression and save path
tpl.static_dir_name =  {$app_path}/static 

;JS,CSS file access path by URL of HTTP request, such as request http://domian/static/x.js
;   the option set /static
tpl.http_access_static_path = /static  

;set domian of static file
tpl.http_access_static_domain =     

;file suffix when output HTML data
tpl.html_suffix = .htm       

;file suffix when output JSON data
;tpl.json_suffix = .json      

;tpl.xml_suffix = .xml        

;UI file parse data to file locate directory
;tpl.compile_tpl_dir_name = compile_tpl  

;origin javascript file locate directory, inside __X_APP_DATA_DIR__/ui/
;tpl.js_file_dir = js            

;tpl.css_file_dir = css          

;whether output rand static file
;tpl_rand_output_static_file_name = false;     

;whether open output file compression
;tpl.compression = false;        

;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;
;[tpl_common_tpldata]
;Application common display variables
;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;

;such as below

;site name
;tpl.common_tpldata.site_name = web_php          
;tpl.common_tpldata.charset = utf-8
;tpl.common_tpldata.copyright = Copyright © 2012 toknot All rights reserved. 
;
EOF;
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
include_once(dirname(__FILE__)."/{$this->toknot_path}/__init__.php");
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
