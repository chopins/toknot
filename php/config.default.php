<?php
/**
 * Web.php
 *
 * _CFG class
 *
 * PHP version 5.3
 * 
 * @category web.php
 * @package web.php
 * @author chopins xiao <chopins.xiao@gmail.com>
 * @copyright  2012 The Authors
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @link       http://blog.toknot.com
 * @since      File available since Release 2.3
 */

/******************************************************************************
 *************** 不要修改本配置文件********************************************
 *************** 请新建自定义配置文件******************************************  
 ******************************************************************************
 */


/**
 * 请不要修改本配置文件
 * 用户自定义配置文件位于 应用程序的 data 目录, 名字为 config.php, 所有配置项目必须与本默认配置文件一样
 * 新配置文件不要初始化定义$_CFG
 */
$_CFG = new stdClass; //配置数据类

/***框架定义的应用程序基本目录解构****/
$_CFG->php_dir_name = 'php';         //存放PHP程序路径名,位于应用目录下
$_CFG->php_model_dir_name = 'model'; //存放公共模块组件目录，建议数据库操作，通用模块存放
$_CFG->ui_dir_name = 'ui';           //存放UI文件夹,位于__X_APP_ROOT__定义目录下_
$_CFG->data_dir_name = 'var';        //存放数据文件夹,位于__X_APP_ROOT__定义目录下_

/***数据文件夹相关目录配置***/
$_CFG->check_data_dir = true;        //每次都检查data目录
$_CFG->data_cache = 'cache';         //临时数据文件夹
$_CFG->error_log_dir = 'error_log';  //错误日志保存位置，
$_CFG->db_data = 'db';               //数据库相关缓存,文本数据库保存位置
$_CFG->run_dir = '';                 //相关服务运行目录，默认位于$_CFG->data_dir_name目录下

$_CFG->timezone = 'Asia/Chongqing';  //时区配置
$_CFG->ajax_key = 'data';            //提交JSON数据时URL中保存数据的键名
$_CFG->ajax_flag = 'is_ajax';        //AJAX提交JSON数据时给出的标识
$_CFG->encoding = 'utf8';            //输出内容实际编码,$_CFG->tpl->common_tpldata->charset仅仅是显示名字
                                     //框架对数据是按UTF-8来保存
                                     //如果这里是非UTF-8编码，提交的数据会被从当前设置编码转换成UTF-8
                                     //PHP程序运行时以UTF-8来运行
$_CFG->exception_seg_line = '===========================================================================';

/***以下URL涉及到URL地址相关***/
$_CFG->url_file_suffix = 'php'; //文件后缀，按PHP解析的文件后缀

$_CFG->subsite_start_level = 1; //从第几级开始解析,不能大于$_CFG->subsite_mode, 如果实际域名没有那么多层级将不解析
$_CFG->subsite_mode = 0; //开启域名解析目录模式,及其深度 0为关闭, 
                         // 1 为解析到一级子域名,一般本地测试使用这种模式, 比如设置 user.news 这种域名
                         // 2 为解析到二级子域名
                         // 3 为解析到三级子域名，以此类推
                         // 注意第一级子域名指顶级域名下面的子域名
                         //  例如 test.com 中的test 为一级,
                         // www.test.com 中的www 为第二级
                         //开启后，将会将按域名顺序解析到PHP存放路径下的目录
$_CFG->uri_mode = 0; 
        // 0 为普通模式,URL为http://localhost/directory/.../CLASS_NAME/METHOD_NAME.php, 需要将所有的PHP执行绑定到index.php
        // 1 为QUERY_STRING模式 URL为http://localhost/index.php?a=directory/.../CLASS_NAME/METHOD_NAME&p=XX 无需服务器支持
        // 2 PATH_INFO模式 URL为http://localhost/index.php/director/CLASS_NAME/METHOD_NAME 需要服务器开启PATH_INFO
        // 3 路径模式，URL为http://localhost/directory/CLASS_NAME/METHOD_NAME,需要服务器rewite支持解析到0模式
        // 4 自定义请求URI列表
        // 只影响模板输出时链接格式

$_CFG->upfile_size = 512000;//文件上传最大尺寸
$_CFG->upfile_mime = array('image/jpeg','image/png','image/gif','image/bmp');//允许上传的文件类型


//应用程序的运行时配置文件,位于data目录,
//与应用自定义配置文件不同,运行时配置配置文件所有配置项允许程序运行后修改，并且所有项目自定义
$_CFG->runtime_config = 'config.runtime.php'; 

/*****框架web server配置******/
$_CFG->web = new stdClass;
$_CFG->web->daemon = false; //以守护进程模式运行WEB服务器
$_CFG->web->ip = '127.0.0.1';//本地监听IP地址
$_CFG->web->port = '8080';//本地监听端口
$_CFG->web->document_root = '';//WEB ROOT, 注意如果未设置本目录，web root目录将是app程序所在目录
$_CFG->web->min_worker_num = 1; //最小2个进程
$_CFG->web->max_worker_num = 20;
$_CFG->web->worker_max_connect = 10;
$_CFG->web->pid_file = 'xweb.pid';//PID文件,位于$_CFG->run_dir目录
//$_CFG->web->socket_file = 'xweb.sock';//SOCKET文件
$_CFG->web->cache_control_time = '1d'; //浏览器缓存控制时间, 单位d 天，h小时,m分钟，s秒
$_CFG->web->request_body_length = '1m';//浏览器请求最大body长度,单位 m（MB),k(KB)
$_CFG->web->upfile_tmp_dir = '';//默认位于系统临时目录
$_CFG->web->index = 'index.php index.html index.htm';//默认页
$_CFG->web->cron = array();//计划任务配置
$_CFG->web->cron[0]['call_class'] = '';//类名
$_CFG->web->cron[0]['class_file'] = '';//类文件名，绝对路径,如果文件不存在将被忽略
$_CFG->web->cron[0]['time_interval'] = '';//多长时间执行一次, 单位区分大小写，d 天，h小时,m分钟，s秒
                                                  // D 每月第几天, W 每周第几天,0表示星期天, T 指定时间(精确到分)(19:23T)
$_CFG->web->cron[0]['times'] = // 执行次数,小于 1或不设置将一直执行

//$_CFG->server->custom_server_process = array();
//$_CFG->server->custom_server_process = 'process';//自定义服务器

/***** 框架文件同步服务*********************************/
$_CFG->inotify = new stdClass;
$_CFG->inotify->watch_list_in_file = '';//将需要监视的文件或目录路径写入文件中，每行一个
$_CFG->inotify->watch_list = array(); //将监视清单保存在数组中,每元素一个
$_CFG->inotify->port = 8045; //监听端口
$_CFG->inotify->db_file = 'inotify.db'; //位于$_CFG->db_data目录下面
$_CFG->inotify->slave = array(); //接收同步端清单,以下配置将根据算法生成一个会话ID，
                            //每一个slave端配置情况例如：array('password'=>'','ip'=>'','user'=>'','olg'=>'md5')
/**** 框架SVNClient服务*******************************/
$_CFG->svn = new stdClass;
$_CFG->svn->protocol = 'unix';
$_CFG->svn->socket = 'xsvn.sock';//unix socket文件将位于$_CFG->run_dir;

/**** 框架文本数据操作************************************/
$_CFG->kvdb_dir = 'kvdb'; //位于$_CFG->db_data目录下
$_CFG->txtdb_dir = 'txtdb';

/*****session相关配置******/
$_CFG->session = new stdClass;
$_CFG->session->session_name = 'XSID';//session 在cookie中的名字
$_CFG->session->save_path = 'session';

/*****数据库相关配置*******/
$_CFG->db  = new stdClass;
$_CFG->db->host = 'localhost';
$_CFG->db->name = 'jac_ipad_data';
$_CFG->db->user = 'root';
$_CFG->db->password = '';
$_CFG->db->select_api = false;

/***模板相关配置***/
$_CFG->tpl = new stdClass; 
$_CFG->tpl->static_dir_name = ''; //静态文件输出地址,本目录应当可访问并且可写，绝对路径
$_CFG->tpl->http_access_static_path = '/static'; //静态文件HTTP访问路径,本目录应当可访问并且可写，绝对路径
$_CFG->tpl->http_access_static_domain = ''; //为空表示当前URL，否则请填写实际访问domian
$_CFG->tpl->html_suffix = '.htm'; //在定义输出HTML数据的UI文件的后缀名
$_CFG->tpl->json_suffix = '.json'; //在定义输出JSON数据的UI文件的后缀名
$_CFG->tpl->xml_suffix = '.xml';//在定义输出XML数据的UI文件的后缀名
$_CFG->tpl->compile_tpl_dir_name = 'compile_tpl'; //HTML格式UI文件解析后保存文件夹名，位于$_CFG->data_dir_name下
$_CFG->tpl->js_file_dir = 'js'; //存放未压缩JS文件目录，位于$_CFG->ui_dir_name目录下面
$_CFG->tpl->css_file_dir = 'css'; //存放未压缩CSS文件目录，位于$_CFG->ui_dir_name目录下面
//$_CFG->tpl->rand_output_static_file_name = false;
$_CFG->tpl->compression = false;


/***模板中需要显示的通用数据***/
$_CFG->tpl->common_tpldata = new stdClass;
$_CFG->tpl->common_tpldata->site_name = 'web.php';//站点名字
$_CFG->tpl->common_tpldata->charset = 'utf-8';
$_CFG->tpl->common_tpldata->copyright = 'copyright @ 2012 web.php Author All rights reserved.';//版权信息
