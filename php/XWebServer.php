<?php
/**
 * Toknot
 *
 * XWebServer class
 *
 * PHP version 5.3
 * 
 * @category php
 * @package Server
 * @author chopins xiao <chopins.xiao@gmail.com>
 * @copyright  2012 The Authors
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @link       http://blog.toknot.com
 * @since      File available since Release 0.4
 */
exists_frame();

/**
 * XWebServer 
 * One multipart process webserver base on libevent,pcntl,shmop,POSIX
 * 
 * @uses XHTTPResponse
 * @final
 * @package Server
 * @version $id$
 * @copyright 2012 The Author
 * @author Chopins xiao <chopins.xiao@gmail.com> 
 * @license http://opensource.org/licenses/bsd-license.php New BSD License
 */
final class XWebServer extends XHTTPResponse {
    /**
     * port 
     * 
     * @var int
     * @access private
     */
    private $port = 8080;
    /**
     * protocol 
     * 
     * @var string
     * @access private
     */
    private $protocol = 'tcp';
    /**
     * ip 
     * 
     * @var string
     * @access private
     */
    private $ip = '0.0.0.0';
    /**
     * server 
     * 
     * @var resources
     * @access private
     */
    private $server = null;
    /**
     * master_worker_sock 
     * 
     * @var resource
     * @access private
     */
    private $master_worker_sock = null;
    /**
     * errno 
     * 
     * @var int
     * @access private
     */
    private $errno = 0;
    /**
     * errstr 
     * 
     * @var string
     * @access private
     */
    private $errstr = null;
    /**
     * scheduler 
     * 
     * @var Object
     * @access private
     */
    private $scheduler = null;
    /**
     * header_buff 
     * 
     * @var string
     * @access private
     */
    private $header_buff = '';
    /**
     * request_body 
     * 
     * @var string
     * @access private
     */
    private $request_body = '';
    /**
     * timeout 
     * 
     * @var float
     * @access private
     */
    private $timeout = 30;
    /**
     * connect_pool 
     * 
     * @var array
     * @access private
     */
    private $connect_pool = array();
    private $connect_idx = 0;
    private $cache_control_time = 30;
    private $cfg = null;
    private $min_worker_num = 2;
    private $max_worker_num = 20;
    private $worker_max_connect = 10;
    private $document_root = '';
    private $php_file_ext = 'php';
    private $base_shmop_key = 0;
    private $worker_pool = array();
    private $accept_lock = 0;
    private $log_handle = null;
    private $open_log = true;
    private $main_pid_file = '';
    private $run_daemon =  false;
    private $run_dir = '';
    private $master_process = false;
    private $master_loopbreak = false;
    private $cron_process = array();
    private $connect_abort_status = false;

    private $worker_id = 0;
    private $master_pid = 0;

    const WP_WAIT = 1;
    const WP_SLEEP = 2;
    const WP_BUSY = 3;
    const WP_LISTEN = 4;
    const WP_WORKER = 5;
    const WP_EXIT = 6;
    /**
     * __construct 
     * 
     * @param mixed $scheduler 
     * @access public
     * @return void
     */
    public function __construct($scheduler) {
        $this->scheduler = $scheduler;
        $this->check_system();
        $this->check_extension();
        $this->load_cfg();
        $this->run_mode();
        $this->master_pid = posix_getpid();
        $this->run_web();
    }
    private function run_cron_process() {
        $pid = pcntl_fork();
        if($pid == -1) throw new XException('fork accept worker process error');
        if($pid >0) {
            $this->cron_process['shmid'] = $shmop_id;
            $this->cron_process['pid'] = $pid;
            return;
        }
        $this->setproctitle('XServer:cron');
        $this->cron_worker_loop();
        return;
    }
    private function cron_worker_loop() {
        $cron_log = array();
        while(true) {
            sleep(1);
            $this->scheduler->load_cfg();
            $cron_list = $GLOBALS['_CFG']->web->cron;
            if(empty($cron_list)) continue;
            foreach($cron_list as $cron) {
                if(empty($cron['call_class']) || empty($cron['class_file'])
                    || empty($cron['time_interval']) || !file_exists($cron['class_file'])) {
                    continue;
                }
                if(!empty($cron['times']) && $cron_log[$cron['call_class']] >= $cron['times']) {
                    continue;
                }
                $timeinterval = conv_human_time($cron['time_interval']);
                if($timeinterval === false) {
                    $suffix = substr($cron['time_interval'],-1,1);
                    $exec_time = substr($cron['time_interval'],0, strlen($cron['time_interval'])-1);
                    switch($suffix) {
                        case 'T':
                            $current_time = date('H:i');
                        break;
                        case 'W':
                            $current_time = date('w');
                        break;
                        case 'D':
                            $current_time = date('j');
                        break;
                        default:
                        continue;
                    }
                    if($current_time != $exec_time) continue;
                } else if(time() - $timeinterval < $cron_log[$cron['call_class']]['exec_time']) {
                    continue;
                }
                if(isset($cron_log[$cron['class_class']])) {
                    $cron_log[$cron['call_class']]['exec_time'] = time();
                    $cron_log[$cron['call_class']]['times'] ++;
                } else {
                    $cron_log[$cron['call_class']]['exec_time'] = time();
                    $cron_log[$cron['call_class']]['times'] = 1;
                }
                $pid = pcntl_fork();
                if($pid > 0) continue;
                if($pid == -1) exit();
                include($cron['class_file']);
                $ref = new ReflectionClass($cron['class_class']);
                $ref->newInstance();
                exit;
            }
        }
    }
    private function load_cfg($_CFG = null) {
        if($_CFG == null) $_CFG = $GLOBALS['_CFG'];
        $this->run_dir = empty($_CFG->run_dir) ?
            __X_APP_DATA_DIR__.'/run': $_CFG->run_dir;

        if(!is_dir($this->run_dir) || !is_writable($this->run_dir)) {
            throw new XException('webserver run dir not exists or unwriteable');
        }

        $this->main_pid_file = empty($_CFG->web->pid_file) ? 
                "{$this->run_dir}/xweb.pid" : "{$this->run_dir}/{$_CFG->web->pid_file}";
        $this->index = explode(' ',$_CFG->web->index);
        if(empty($_CFG->web->document_root) || !is_readable($_CFG->web->document_root)) {
            $this->document_root = __X_APP_ROOT__;
        } else  {
            $this->document_root = $_CFG->web->document_root;
        }
        isset($_CFG->web->worker_max_connect) and ($this->max_connect = $_CFG->web->worker_max_connect);
        isset($_CFG->url_file_suffix) and ($this->php_file_ext = $_CFG->url_file_suffix);
        isset($_CFG->web->port) and ($this->port = $_CFG->web->port);
        isset($_CFG->web->min_worker_num) and
            ($this->min_worker_num = $_CFG->web->min_worker_num);
        isset($_CFG->web->max_worker_num) and
            ($this->max_worker_num = $_CFG->web->max_worker_num);
        $this->upfile_tmp_dir = isset($_CFG->web->upfile_tmp_dir) ? $_CFG->web->upfile_tmp_dir :
                                    __X_APP_DATA_DIR__."/{$_CFG->data_cache}";
        isset($_CFG->web->cache_control_time) and 
            ($this->cache_control_time = conv_human_time($_CFG->web->cache_control_time));
        isset($_CFG->web->request_body_length) and 
            ($this->request_body_length = conv_human_byte($_CFG->web->request_body_length));
        isset($_CFG->web->daemon) and $this->run_daemon = $_CFG->web->daemon;
    }
    private function run_mode() {
        $argv = '';
        if($_SERVER['argc'] >= 2) {
            $argv = $_SERVER['argv'];
            if($this->support_argv($argv,$this->main_pid_file)) return;
        }
        if(array_search('-d',$argv) !== false || $this->run_daemon) {
            daemon();
        }
        $this->setproctitle('XServer:master '.__FILE__);
        $this->init_access_log($this->run_dir);
        file_put_contents($this->main_pid_file, posix_getpid());
    }
    private function support_argv($argv,$pidfile) {
        $pid = 0;
        if(file_exists($pidfile)) {
            $pid = file_get_contents($pidfile);
        }
        foreach($argv as $av) {
            switch($av) {
                case 'quit':
                case '-q':
                    if($pid ==0) exit('pid file not found');
                    posix_kill($pid, SIGTERM);
                return true;
                case '-r':
                case 'restart':
                    if($pid ==0) exit('pid file not found');
                    posix_kill($pid , SIGUSR1);
                return true;
                case 'reload':
                    if($pid == 0) exit('pid file not found');
                    posix_kill($pid , SIGUSR2);        
                case '-h':
                case 'help':
                    echo "Option: -q | quit     stop the web server\n";
                    echo "        -r | restart  restart the web server\n";
                    echo "        -h | help     display the message\n";
                    echo "        -d            run webserver daemon\n";
                return true;
                default:
                return false;
            }
        }
    }
    public function process_signal($signo) {
        if($this->master_process == false) {
            exit;
        }
        switch($signo) {
            case SIGINT:
            case SIGHUP:
            case SIGTERM:
                $this->worker_exit();
                exit;
            return;
            case SIGUSR1:
                $this->worker_exit();
                $php_exc = getenv('_');
                if($php_exc == $_SERVER['argv'][0]) {
                    $php_exc = PHP_BINDIR.'/php';
                }
                popen("$php_exc {$_SERVER['argv'][0]} -d ");
                exit;
            return;
            case SIGUSR2:
                $this->scheduler->load_cfg();
                $this->load_cfg();
                $this->worker_exit();
                $this->fork_worker_process(true);
            return;
            case SIGCHLD:
                $pid = pcntl_waitpid(-1,$status);
                foreach($this->worker_pool as $key => $worker) {
                    if($worker['pid'] == $pid) {
                        shmop_delete($worker['shmid']);
                        unset($this->worker_pool[$key]);
                        break;
                    }
                }
                $this->run_new_worker_process($key);
            return;
        }
    }
    public function __destruct() {
        unset($this);
    }
    private function get_shmop_key() {
        $int_hash = 5831;
        $str_key = md5(__FILE__.__METHOD__.posix_getpid());
        for($i=0;$i<32;$i++) {
            $int_hash = ((($int_hash <<5) + $int_hash) + ord($str_key[$i])) & 0x7fffffff;
        }
        return $int_hash;
    }
    private function check_system() {
        if(strtoupper(substr(PHP_OS,0,3) === 'WIN')) {
            throw new XException('XServer class not support windows');
        }
        if(PHP_SAPI != 'cli') {
            throw new XException('XServer class only runing in php cli mode');
        }
    }
    private function check_extension() {
        dl_extension('pcntl', 'pcntl_fork');
        dl_extension('proctitle','setproctitle');
        dl_extension('posix','posix_getpid');
        dl_extension('libevent','event_base_new');
    }
    private function setproctitle($name) {
        setproctitle($name);
    }
    private function get_loacl_socket() {
        return "{$this->protocol}://{$this->ip}:{$this->port}";
    }
    private function run_web() {
        $local_socket = $this->get_loacl_socket();
        $this->server = stream_socket_server($local_socket,$this->errno, $this->errstr);
        if(!$this->server) throw new XException($this->errstr);
        stream_set_blocking($this->server,0);
        $this->fork_worker_process();
    }
    private function run_web_worker() {
        $base_evt = event_base_new();
        $evt = event_new();
        event_set($evt, $this->server, EV_READ | EV_WRITE|EV_TIMEOUT | EV_PERSIST, 
                array($this,'web_accept'), array($evt,$base_evt,$shmop_id));
        event_base_set($evt,$base_evt);
        event_add($evt);

        return $base_evt;
    }
    private function worker_event_exit($signo, $flag, $arg) {
        event_del($arg[1]);
        event_base_loopexit($arg[0]);
        exit;
    }
    private function worker_exit() {
        foreach($this->worker_pool as $key =>$worker) {
            posix_kill($worker['pid'], SIGTERM);
            shmop_write($worker['shmid'],self::WP_EXIT,0);
            pcntl_waitpid($worker['pid'],$status);
            shmop_delete($worker['shmid']);
            unset($this->worker_pool[$key]);
        }
        $this->__destruct();
        exit;
    }
    private function fork_worker_process($restart_worker = false) {
        for($i=1;$i<=$this->min_worker_num;$i++) {
            $mws = stream_socket_pair(STREAM_PF_UNIX,STREAM_SOCK_STREAM,STREAM_IPPROTO_IP);
            $pid = pcntl_fork();
            if($pid == -1) throw new XException('fork accept worker process error');
            if($pid >0) {
                $this->worker_pool[$pid] = $mws[1];
                fclose($mws[0]);
                continue;
            }
            fclose($mws[1]);
            $this->setproctitle('XServer:worker pool');
            $this->master_worker_sock = $mws[0];
            $this->web_worker_loop($mws[0]);
            return;
        }
    }
    private function run_new_worker_process($base_evt= null, $sig_evt = null) {
        $mws = stream_socket_pair(STREAM_PF_UNIX,STREAM_SOCK_STREAM,STREAM_IPPROTO_IP);
        $pid = pcntl_fork();
        if($pid == -1) return -1;
        if($pid >0) {
            $this->worker_pool[$pid] = $mws[1];
            fclose($mws[0]);
            return;
        }
        gc_collect_cycles();
        $this->setproctitle('XServer:worker pool');
        fclose($mws[1]);
        $this->web_worker_loop($mws[0]);
    }

    /**
     * master_watch_event 
     * 
     * @param mixed $sock 
     * @param mixed $flag 
     * @param mixed $arg 
     * @access private
     * @return void
     */
    private function master_watch_event($sock, $flag, $arg) {
        if(!empty($arg[0])) event_base_loopexit($arg[0]);
        if(!empty($arg[1])) event_del($arg[1]);
        return;
    }
    private function master_event_loopbreak($sock, $flag, $arg) {
        $this->master_watch_event($sock,$flag,$arg);
        $this->master_loopbreak = true;
        $this->worker_exit();
    }
    private function add_sig_event($base_evt, $signo, $call_func) {
        $sig_evt = event_new();
        event_set($sig_evt, $signo , EV_SIGNAL, array($this,$call_func),array($base_evt,$sig_evt));
        event_base_set($sig_evt, $base_evt);
        event_add($sig_evt);
        return $sig_evt;
    }
    private function master_loop() {
        $busy_all = false;
        $last_listen = 0;
        $base_evt = event_base_new();
        $evt = event_new();
        event_set($evt, $this->server, EV_READ | EV_WRITE|EV_TIMEOUT | EV_PERSIST, 
                array($this,'master_watch_event'), $base_evt);
        event_base_set($evt,$base_evt);
        event_add($evt);
        
        pcntl_signal_dispatch();
        event_base_loop($base_evt, EVLOOP_ONCE);
        pcntl_waitpid(-1,$status);
        return;
        while(true) {
            if($this->master_loopbreak) break;
            $listen = $wait = $first_sleep = 0;
            foreach($this->worker_pool as $idx => $worker) {
                $worker_stat = shmop_read($worker['shmid'],0,1);
                switch($worker_stat) {
                    case self::WP_LISTEN:
                        $listen = $worker;
                        $last_listen = $worker;
                    break;
                    case self::WP_SLEEP:
                        $first_sleep = $worker;
                    break;

                    case self::WP_WAIT:
                        $wait++;
                    break;
                    case self::WP_WORKER:
                    if($busy_all && $first_sleep == 0 && $worker != $last_listen) {
                        $first_sleep = $worker;
                        $last_listen = $worker;
                    }
                    break;
                }
            }
            if($wait == count($this->worker_pool)) continue;
            if($listen !==0) {
                continue;
            }
            if($listen === 0 && $first_sleep !== 0) {
                shmop_write($first_sleep['shmid'],self::WP_LISTEN,0);
                continue;
            }
            $idx++;
            if($idx >= $this->max_worker_num) {
                $busy_all = true;
                continue;
            }
            $this->run_new_worker_process($idx, $base_evt,$sig_evt_list);
            do {
                pcntl_signal_dispatch();
                $worker_stat = shmop_read($this->worker_pool[$idx]['shmid'],0,1);
                if($worker_stat == self::WP_SLEEP) {
                    shmop_write($this->worker_pool[$idx]['shmid'],self::WP_LISTEN,0);
                    break;
                }
                usleep(1000);
            } while(0);
        }
        pcntl_waitpid(-1,$status);
    }
    public function connect_abort($sock, $flag, $arg) {
        if(is_array($arg)) {
            event_del($arg[1]);
        } else {
            event_del($arg);
        }
        $this->connect_abort_status = true;
    }
    public function get_worker_id() {
        return $this->worker_id;
    }
    private function web_worker_loop($mws) {
        $this->master_process = false;
        pcntl_signal(SIGTERM,SIG_DFL);
        pcntl_signal(SIGINT, SIG_DFL);
        pcntl_signal(SIGHUP, SIG_DFL);
        pcntl_signal(SIGPIPE, array($this,'connect_abort'));
        //pcntl_sigprocmask(SIG_UNBLOCK,array(SIGTERM,SIGINT,SIGHUP),$old);
        fwrite($mws,self::WP_WAIT);
        $base_evt = $this->run_web_worker();
        $master_worker_base_evt = event_base_new();
        $master_worker_evt = event_new();
        event_set($master_worker_evt, $mws, EV_READ | EV_WRITE|EV_TIMEOUT | EV_PERSIST, 
                array($this,'web_accept'), $master_worker_base_evt);
        event_base_set($master_worker_evt,$master_worker_base_evt);
        event_add($master_worker_evt);
        event_base_loop($base_evt);
    }
    private function web_accept($sock, $flag, $arg) {
        $this->clear_evn();
        if($flag == EV_TIMEOUT) return;
        if($this->connect_abort_status) {
            $this->connect_abort_status = false;
            return;
        }
        $connect = stream_socket_accept($this->server, $this->timeout, $client_info);
        if($connect == false) return;
        $this->connect_pool[$this->connect_idx] = $connect;
        $_SERVER['REQUEST_TIME'] = time();
        $_SERVER['SERVER_PORT'] = $this->port;
        list($_SERVER['REMOTE_ADDR'], $_SERVER['REMOTE_PORT']) = explode(':',$client_info);
        stream_set_blocking($connect,0);
        if($this->connect_idx >= PHP_INT_MAX) {
            $this->connect_idx = 0;
        }
        $this->web_read($connect);
        unset($this->connect_pool[$this->connect_idx]);
        $this->connect_idx++;
    }
    private function clear_evn() {
        $this->request_body = '';
        $this->header_buff = '';
        $_ENV['__X_EXCEPTION_THROW__'] = false;
        $_ENV['__X_OUT_BROWSER__'] = false;
        $_ENV['__X_EXCEPTION_THROW_DISABEL__'] = false;
        $_ENV['__X_FATAL_EXCEPTION__'] = false;
        $this->request_static_file = false;
        $this->request_static_file_type = false;
        $this->request_static_file_state = false;
        $this->user_headers = array();
        $_SERVER['SERVER_ADDR'] = '';
        $_SERVER['HTTP_HOST'] = '';
        $_SERVER['REQUEST_URI'] = '';
        $_SERVER['REQUEST_METHOD'] = '';
        $_SERVER['REQUEST_TIME'] = '';
        $_SERVER['HTTP_USER_AGENT'] = null;
        unset($_SERVER['HTTP_REFERER'],$_SERVER['HTTP_CONNECTION'],
              $_SERVER['HTTP_ACCEPT'],$_SERVER['HTTP_ACCEPT_CHARSET'],$_SERVER['HTTP_ACCEPT_ENCODING'],
              $_SERVER['HTTP_ACCEPT_LANGUAGE']);
        if(!empty($this->content_type)) 
            unset($this->content_type,$this->boundary);
        if(isset($_SERVER['QUERY_STRING'])) unset($_SERVER['QUERY_STRING']);
        $_POST = array();
        $_FILES = array();
        $_REQUEST = array();
        $_GET = array();
    }
    public function exit_alert($e) {
        $this->return_server_status(500);
        $output_html = "<h1>{$this->response_status}</h1>";
        $_ENV['__X_OUT_BROWSER__'] = true;
        $output_html .= $e->getXDebugTraceAsString();
        $response_header = $this->get_response_header();
        $response_header .= $this->set_length(strlen($output_html));
        $response_header .= "\r\n";
        $out = $response_header . $output_html;
        $stat = fwrite($_ENV['__X_REQUEST_CONNECT__'],$out);
        fclose($_ENV['__X_REQUEST_CONNECT__']);
        unset($_ENV['__X_REQUEST_CONNECT__']);
    }
    private function web_write($connect) {
        if($this->request_static_file_state === false) {
            $output_html = $errstr = '';
            if($this->response_status_code == 200) {
                $_ENV['__X_REQUEST_CONNECT__'] = $connect;
                $_ENV['__X_SERVER_INSTANCE__'] = $this;
                $_ENV['__X_RUN_APP_COMPLETE__'] = false;
                $_ENV['__X_OUT_BROWSER__'] = true;
                $this->scheduler->load_app();
                if(!empty($this->scheduler->app_instance->headers)) {
                    $this->user_headers = $this->scheduler->app_instance->headers;
                }
                $_ENV['__X_RUN_APP_COMPLETE__'] = true;
                $_ENV['__X_OUT_BROWSER__'] = false;
                if($_ENV['__X_FATAL_EXCEPTION__'] == true) {
                    $output_html = '';
                    $this->return_server_status('500');
                    $output_html = $this->scheduler->get_html();
                } else {
                    $this->cookie_header = $this->get_setcookie_header();
                    $output_html = $this->scheduler->get_html();
                }
            } else {
                $output_html = "<h1>{$this->response_status}</h1>";
            }
            $output_html .= $errstr;
            $response_header = $this->get_response_header();
            $response_header .= $this->cookie_header;
            $response_header .= $this->set_length(strlen($output_html));
            $response_header .= "\r\n";
            $out = $response_header . $output_html;
            $stat = @stream_socket_sendto($connect,$out);
            fclose($connect);
        } else {
            $f = false;
            if($this->request_static_file_state === true) {
                $f = fopen($this->request_static_file,'rb');
                if($f === false) $this->return_server_status(403);
            }
            $response_header = $this->get_response_header();
            if($f) {
                $response_header .= $this->set_length(filesize($this->request_static_file));
            }
            $response_header .= "\r\n";
            $read = $except = null;
            $write =  array($connect);
            $_ENV['__X_EXCEPTION_THROW_DISABEL__'] = true;
            if(stream_select($read, $write, $except, 0) > 0) {
                fwrite($connect, $response_header);
                if($f) {
                    while(!feof($f)) {
                    if($this->connect_abort_status) break;
                    $tmp_buff = fread($f,1024);
                    if(stream_select($read, $write,$except, 0) === false) {
                        break;
                    }
                    $re = @stream_socket_sendto($connect,$tmp_buff);
                    if($re == -1) {
                        $write_complete = false;
                        break;
                    }
                    }
                    fclose($f);
                }
            }

            $_ENV['__X_EXCEPTION_THROW_DISABEL__'] = false;
            fclose($connect);
        }
        if($this->open_log) {
            $this->access_log(array('status'=>$this->response_status_code,                'date'=>date('Y-m-d H:i:s',$_SERVER['REQUEST_TIME']),
                    'user_ip'=>$_SERVER['REMOTE_ADDR'],
                    'uri'=>$_SERVER['REQUEST_URI'],
                    'host'=>$_SERVER['HTTP_HOST'],
                    'user_agent'=>$_SERVER['HTTP_USER_AGENT'],
                    'method'=>$_SERVER['REQUEST_METHOD']
                ));
            }

        unset($connect);
        if(isset($this->upfile_tmp_list) && is_array($this->upfile_tmp_list)) {
            foreach($this->upfile_tmp_list as $filename) {
                file_exists($filename) && unlink($filename);
            }
            $this->upfile_tmp_list = array();
        }
        $this->clear_evn();
    }
    private function web_read($connect) {
        $_SERVER['SERVER_ADDR'] = gethostbyname(gethostname());
        $this->return_server_status('200');
        $this->get_request_header($connect);
        if($this->response_status_code == 200 && $_SERVER['REQUEST_METHOD'] == 'POST') {
            $this->get_request_body($connect);
        }
        $_REQUEST = array_merge($_GET,$_POST,$_COOKIE);
        $this->web_write($connect);
    }
    private function init_access_log($run_dir) {
        $log_dir = "{$run_dir}/access_log";
        if(!file_exists($log_dir)) {
            mkdir($log_dir);
        } elseif(!is_dir($log_dir)) {
            throw new XException("unable create access log dir, {$log_dir} exists");
        }
        $log_file = "{$log_dir}/access_log_".date('Ymd');
        $this->log_handle = fopen($log_file,'ab');
        if($this->log_handle === false) throw new XException("create webserver access log 
                file error, cannot open file {$log_file}");
    }

    /**
     * log format :status date - Ip Method uri  USER-AGENT
     */
    private function access_log($access_log_array) {
        if(false === is_resource($this->log_handle)) throw new XException('can not open webserver
                access log file of resource handler');
        $log = "{$access_log_array['status']} - {$access_log_array['date']} - "
               ."{$access_log_array['user_ip']} - {$access_log_array['method']} - "
               ." {$access_log_array['host']}"
               ."{$access_log_array['uri']} -- {$access_log_array['user_agent']}\r\n";
        @fwrite($this->log_handle,$log);
    }
 }
