<?php

/**
 * Toknot
 *
 * XInotifySync
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
/**
 * XInotifySync 
 * 
 * @package 
 * @version $id$
 * @author Chopins xiao <chopins.xiao@gmail.com> 
 */
class XInotifySync {
    /**
     * inotify_instance 
     * 
     * @var mixed
     * @access public
     */
    public $inotify_instance = null;
    public $watch_descriptor = array();
    public $pending_event = 0;
    public $watch_list_conf_wd = null;

    /**
     * inotify_sock 
     * file watch process sock
     * 
     * @var mixed
     * @access public
     */
    public $inotify_sock = null;

    /**
     * sync_sock 
     * file send process sock
     * 
     * @var mixed
     * @access public
     */
    public $sync_sock = null;
    /**
     * max_sync_process_num 
     * max send file process
     * 
     * @var float
     * @access public
     */
    public $max_sync_process_num = 5;
    public $log_file = 'sync.log';
    public $ssh_ins = null;
    public $watch_list_conf = null;
    public $tmp_echnage = 'inotify_change.dat';
    public function __construct($daemon = 1, $watch_list_conf) {
        if(extension_loaded('inotify') == false) {
            dl('inotify.so');
        }
        if(extension_loaded('proctitle') ==false) {
            dl('proctitle.so');
        }
        if(extension_loaded('posix') == false) {
            dl('posix.so');
        }
        if($daemon) {
            daemon();
        }
        $ips = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM,STREAM_IPPROTO_IP);
        //fork inotify process
        setproctitle('php:XInotifySync Main process');
        $pid = pcntl_fork();
        if($pid == -1) throw new XException('fork inotify process failure');
        if($pid == 0) {
            setproctitle('php:XInotifySync Watch Master process');
            $this->inotify_sock = $ips[0];
            fclose($ips[1]);
            $this->create_inotify_instance();
            $this->watch_list_conf = $watch_list_conf;
            $this->watch_list_conf_wd = inotify_add_watch($this->inotify_instance,$watch_list_conf,IN_MODIFY);
            $this->add_form_file($this->watch_list_conf);
            $this->watch_loop();
            exit(0);
        }

        //fork sync process
        $pid = pcntl_fork();
        if($pid == -1) throw new XException('fork sync process failure');
        if($pid == 0) {
            $this->sync_sock = $ips[1];
            setproctitle('php:XInotifySync Sync Master process');
            fclose($ips[0]);
            $this->sync_master_process_loop();
            exit(0);
        }
        pcntl_wait($status);
    }
    public function err($msg) {
        $this->msg($msg);
        exit(1);
    }
    public function msg($msg) {
        echo "$msg\r\n";
    }
    public function logs($str) {
        $msg = time().':'.$str."\n";
        file_put_contents($this->log_file,$str,FILE_APPEND);
    }
    public function create_inotify_instance() {
        $this->inotify_instance = inotify_init();
    }
    public function watch($path,$ip,$port, $tpath) {
        $path = trim($path);
        $path = rtrim($path,'/');
        $wd = inotify_add_watch($this->inotify_instance,$path,IN_IGNORED|IN_ISDIR|
                            IN_CLOSE_WRITE|IN_CREATE|IN_MOVE|IN_DELETE);
        $this->watch_descriptor[$wd]['wd'] = $wd;
        $this->watch_descriptor[$wd]['path'] = $path;
        $this->watch_descriptor[$wd]['target_ip'] = $ip;
        $this->watch_descriptor[$wd]['target_port'] = $port;
        $this->watch_descriptor[$wd]['target_path'] = $tpath;
        if(is_dir($path)) {
            $this->add_sub_dir($wd);
        }
        return $wd;
    }
    public function rm($wd) {
        inotify_rm_watch($this->inotify_instance, $wd);
        if(is_dir($this->watch_descriptor[$wd]['path'])) {
            $this->rm_sub_dir($this->watch_descriptor[$wd]['path']);
        }
        unset($this->watch_descriptor[$wd]);
    }
    public function rm_dir_wd($path) {
        foreach($this->watch_descriptor as $wd => $info) {
            if($path == $info['path']) {
                unset($this->watch_descriptor[$wd]);
                //$this->rm($wd);
            }
        }
    }
    public function rm_all_watch() {
        foreach($this->watch_descriptor as $wd => $path) {
            $this->rm($wd);
        }
    }
    public function queue() {
        $this->pending_event = inotify_queue_len($this->inotify_instance);
    }
    public function get() {
        return inotify_read($this->inotify_instance);
    }
    public function add_form_array($file_list) {
        if(!is_array($file_list)) return;
        foreach($file_list as $file) {
            $this->watch($file['path'],$file['ip'],$file['port'],$file['tpath']);
        }
    }
    /**
     * sync_master_process_loop 
     * file sync master process
     * 
     * @access public
     * @return void
     */
    public function sync_master_process_loop() {
        stream_set_blocking($this->sync_sock, 1);
        while(1) {
            if(is_resource($this->sync_sock) == false) {
                $this->err('pip error');
                return;
            }
            $read = array($this->sync_sock);
            $write = null;
            $except = null;
            $chg_num = stream_select($read,$write,$except,0);
            if($chg_num > 0) {
                $str = fread($this->sync_sock,10000);
                $this->msg('request');
                $this->sync_file($str);
            }
        }
        pcntl_wait($status);
    }
    /**
     * sync_file 
     * file opreate process
     * 
     * @param mixed $str 
     * @access public
     * @return void
     */
    public function sync_file($str) {
        $oppid = pcntl_fork();
        if($oppid > 0) {
            return $oppid;
        } elseif($oppid == -1){
            $this->logs('fork sync opreate process error');
            return;
        }
        //$this->ssh_ins = new XSSH2('192.168.1.251','22');
        //$this->ssh_ins->connect();
        //$this->ssh_ins->create_sftp();
        list($this->watch_descriptor,$change_list) = unserialize($str);
        print_r($change_list);
        die;
        $file_num = count($change_list['C']) + count($change_list['U']);
        $this->exec_sync_rm($change_list['D']);
        $this->exec_sync_mv($change_list['M']);
        if($file_num >= $this->max_sync_process_num) {
            $max_num = $this->max_sync_process_num;
        } else {
            $max_num = $file_num;
        }
        $pnum = 1;
        $file_idx = 0;
        $current_sync_queen = array();
        while(true) {
            if($file_idx>= $file_num) break;
            $pid = $this->exec_sync_send_file($change_list[$file_idx]);
            $pnum++;
            $current_sync_queen[$pid] = $file_idx;
            if($pnum >= $max_num) {
                while(count($current_sync_queen) > 0) {
                    $pid = pcntl_wait($status);
                    unset($current_sync_queen[$pid]);
                }
            }
        }
    }
    public function exec_sync_send_file($file) {
        $pid = pcntl_fork();
        if($pid > 0) return $pid;
        if($pid == -1) {
            $this->logs('fork file send process error');
            return;
        }
        setproctitle('php:XInotifySync send file process');
        $this->ssh_ins->sendfile($file,$file, 744);
    }
    public function exec_sync_rm($delete) {
        foreach($delete as $file) {
            $this->ssh_ins->rm($file);
        }
    }
    public function exec_sync_mv($move) {
        foreach($move as $file) {
            $this->ssh_ins->mv($file);
        }
    }
    /**
     * notify_file_list 
     * send the change list info to sysnc process
     * 
     * @param mixed $watch_info 
     * @param mixed $change 
     * @access public
     * @return void
     */
    public function notify_file_list($watch_info, $change) {
        $change_str = serialize(array($watch_info,$change));
        $read = null;
        $write = array($this->inotify_sock);
        $except = null;
        $chg_num = 0;
        $chg_num = stream_select($read,$write,$except,0);
        if($chg_num > 0) {
            if(in_array($this->inotify_sock,$write)) {
                fwrite($this->inotify_sock,$change_str, strlen($change_str));
                $write = array();
            }
        }
    }
    public function add_form_file($file) {
        if(!is_file($file)) return;
        $fh = fopen($file,'r');
        while(!feof($fh)) {
            $conf_line = fgets($fh);
            list($ini,) = explode('#',$conf_line,2);
            $ini = trim($ini);
            if(empty($ini)) continue;
            list($path, $ip, $port, $tpath) = explode(':',$ini);
            $wd = $this->watch($path,$ip, $port,$tpath);
        }
    }
    public function add_sub_dir($wd) {
        $watch_descriptor = $this->watch_descriptor[$wd];
        $dh = opendir($watch_descriptor['path']);
        if($dh === false) return;
        while(false !== ($name = readdir($dh))) {
            if($name == '.' || $name == '..') continue;
            $path_dir = "{$watch_descriptor['path']}/{$name}";
            $tpath = "{$watch_descriptor['target_path']}/{$name}";
            if(is_dir($path_dir)) {
                $nwd = $this->watch($path_dir, $watch_descriptor['target_ip'],
                             $watch_descriptor['target_port'], $tpath);
                $this->add_sub_dir($nwd);
            }
        }

    }
    public function reload_watch_list($ev_info) {
        $this->rm_all_watch();
        if($ev_info['mask'] & IN_DELETE_SELF ||
                $ev_info['mask'] & IN_MOVE_SELF) {
            return;
        }
        $this->add_form_file($this->watch_list_conf);
    }
    public function watch_loop() {
        while(true) {
            stream_set_blocking($this->inotify_instance,1);
            $events = $this->get();
            $present_timestamp = time();
            $this->msg('New events');
            $change = array();
            $change['C'] = array();
            $change['MF'] = array();
            $change['MT'] = array();
            $change['D'] = array();
            $change['U'] = array();
            foreach($events as $ev => $ev_info) {
                if(!isset($this->watch_descriptor[$ev_info['wd']])) {
                    continue;
                }
                $watch_info = $this->watch_descriptor[$ev_info['wd']];
                $os_path = "{$watch_info['path']}/{$ev_info['name']}";
                $os_tpath = "{$watch_info['target_path']}/{$ev_info['name']}";
                if($ev_info['wd'] == $this->watch_list_conf_wd) {
                    $this->reload_watch_list($ev_info);
                    continue;
                }
                switch($ev_info['mask']) {
                    case IN_CREATE|IN_ISDIR: //创建文件夹
                        $wd = $this->watch($os_path, $watch_info['target_ip'],
                                            $watch_info['target_port'],
                                            $watch_info['target_path']);
                        $change['C'][] = $os_path;
                        $this->msg('create dir');
                    break;
                    case IN_CLOSE_WRITE:  //修改
                        $change['U'][] = $os_path;
                        $this->msg('edit');
                    break;
                    case IN_MOVED_TO: //移动
                        $change['MT'][$ev_info['cookie']] = $os_path;
                        $this->msg('INTO'. $os_path);
                    break;
                    case IN_MOVED_FROM:
                        pcntl_alarm(1);
                        $change['MF'][$ev_info['cookie']] = $os_path;
                        $this->msg('OUT' . $os_path);
                    break;
                    case IN_DELETE:
                        $change['D'][] = $ev_info['name'];
                        $this->msg('file del');
                    case IN_DELETE|IN_ISDIR:  //删除
                        $change['D'][] = $ev_info['name'];
                        $this->rm_dir_wd($os_path);
                        $this->msg('del dir');
                    break;
                    case IN_DELETE_SELF:  //监视文件夹删除
                        $thsi->rm($ev_info['wd']);
                    break;
                    case IN_MOVE_SELF: //监视文件夹移动
                    break;
                    default:
                    break;
                }
            }
            $this->notify_file_list($this->watch_descriptor, $change);
        }
    }
    public function __destruct() {
        if(is_resource($this->inotify_instance)) {
            fclose($this->inotify_instance);
        }
    }
}
