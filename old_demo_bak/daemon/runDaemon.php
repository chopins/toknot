#!/opt/php/bin/php
<?php
/*
   用户需要定义常量:
    __X_IN_FRAME__     设置为true,
    __X_SHOW_ERROR__   是否显示错误trace信息
    __X_APP_ROOT__     设置用户应用程序所在目录
   然后在WEB目录中的index.php文件中包含本文件,该文件应当是唯一的脚本执行文件
   在nginx 可以如下配置，指定所有PHP都将SCRIPT_FILENAME指定为该文件
        fastcgi_param  SCRIPT_FILENAME    WEB目录下的/demo/index.php;
*/
define('__X_IN_FRAME__', true);
define('__X_SHOW_ERROR__',true);
define('__X_APP_ROOT__', dirname(__FILE__));
define('__X_NO_WEB_SERVER__',true);
define('__X_DAEMON_LOOP_FILE__','/sync.php');
//运行框架程序
include_once('/home/chopin/Code/toknot/php/__init__.php');
