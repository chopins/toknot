<?php

/**
 * Toknot
 * initialization and load frameworker
 *
 * PHP version 5.3
 * 
 * @package XDataStruct
 * @author chopins xiao <chopins.xiao@gmail.com>
 * @copyright  2012 The Authors
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @link       http://blog.toknot.com
 * @since      File available since Release $id$
 */

/*
   Usage:
   应用程序可以定义以下常量:
    __X_IN_FRAME__     该值只能为true,
    __X_SHOW_ERROR__   是否显示错误异常和trace信息
    __X_APP_ROOT__     设置用户应用程序所在目录,默认为web访问根目录
                       true 将在页面中输出，但是不将错误信息记录到日志中
                       false 将不在页面中显示，但是会将错日志写入日志文件中
                       null  将屏蔽所有错误信息的显示，并且不记录到日志文件中
    __X_EXCEPTION_LEVEL__ 设置异常等级, 
                          0 为所有信息，
                          1 将不抛出notice信息，
                          2 将不抛出Warning和notice信息
    __X_APP_DATA_DIR_NAME__ 数据目录名__X_APP_ROOT__目录下面

    __X_APP_DATA_DIR__ 数据存储路径,本值如果设置，__X_APP_DATA_DIR_NAME__将无效

    __X_APP_USER_CONF_FILE_NAME__  用户应用所使用的配置文件名,位于__X_APP_DATA_DIR__目录下面的conf目录下面

    __X_NO_WEB_SERVER__   是否在永远禁用内置webserver 即使在CLI状态下
                          CLI模式下，如果设定为true, 那么当在执行命令时给出 -d 参数，脚本仍然会已守护进程模式运行
                          并会自动调用用户的Loop类, Loop 程序文件名字需要使用 __X_DAEMON_LOOP_FILE__ 来定义

    __X_DAEMON_LOOP_FILE__  守护进程模式下运行的PHP文件名，位于 __X_APP_ROOT__目录下$_CFG->php_dir_name目录下
                          
   然后在WEB目录中的index.php文件中包含本文件,该文件应当是唯一的脚本执行文件
   在nginx 可以如下配置，指定所有PHP都将SCRIPT_FILENAME指定为该文件
        fastcgi_param  SCRIPT_FILENAME    WEB目录下的/demo/index.php;
   该index.php文件定义类似下列代码:
    [CODE]
        define('__X_IN_FRAME__', true);
        define('__X_SHOW_ERROR__',true);
        define('__X_APP_ROOT__', dirname(__FILE__).'/mysite');
        include_once(dirname(dirname(__FILE__)) . '/fw-2.2/__init__.php');
    [/CODE]

[关于代码命名规则]
    1.框架类： 首单词全小写，后续单词首字母大写
    2.框架内部使用方法，全小写，单词间用下划线连接 _
    3.框架全局变量全部位于 $_ENV中， 并且全大写
    4.框架常量,全大写
*/

/******用户定义常量检查开始********************/
defined('__X_IN_FRAME__') ||  define('__X_IN_FRAME__', true);
defined('__X_SHOW_ERROR__') || define('__X_SHOW_ERROR__',true); 
defined('__X_EXCEPTION_LEVEL__') || define('__X_EXCEPTION_LEVEL__',2);
defined('__X_APP_DATA_DIR_NAME__') || define('__X_APP_DATA_DIR_NAME__','var');
defined('__X_APP_USER_CONF_FILE_NAME__') || define('__X_APP_USER_CONF_FILE_NAME__','config.ini');
defined('__X_APP_DATA_DIR__') || define('__X_APP_DATA_DIR__',__X_APP_ROOT__.'/'.__X_APP_DATA_DIR_NAME__);
defined('__X_NO_WEB_SERVER__') || define('__X_NO_WEB_SERVER__', false);
defined('__X_FIND_SLOW__') || define('__X_FIND_SLOW__', true);
/******用户定义常量结束********************/
define('__X_RUN_START_TIME__',microtime(true));
define('__X_FRAMEWORK_ROOT__', __DIR__); //不要修改本常量
clearstatcache();
if(PHP_SAPI ==  'cli' && !isset($_SERVER['argv'])) {
    $_SERVER['argc'] = $argc;
    $_SERVER['argv'] = $argv;
}
include_once(__X_FRAMEWORK_ROOT__.'/XFunction.php');
if(__X_FIND_SLOW__) {
    register_tick_function('find_php_slow_pointer');
    declare(ticks=1);
}
spl_autoload_register('XAutoload');
set_error_handler('error2debug');
register_shutdown_function('XExitAlert');
load_php(__X_FRAMEWORK_ROOT__.'/XDataStruct.php');
try {
    $_X_APP_RUNING = XScheduler::singleton();
} catch(XException $e) {
    echo $e->getXDebugTraceAsString();
}
exit;
