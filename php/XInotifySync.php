<?php

/**
 * Toknot
 *
 * XInotifySync
 *
 * PHP version 5.3
 * 
 * @package XInotifySync
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
    public $max_transporter = 5;
    public $log_file_dir = null;
    public $ssh_ins = null;
    public $run_dir = '/tmp';
    public $watch_list_conf = null;
    public $tmp_echnage = 'inotify_change.dat';
    public function __construct($watch_list_conf, $run_dir,$log_file_dir) {
        if(extension_loaded('inotify') == false) {
            dl('inotify.so');
        }
        if(extension_loaded('proctitle') ==false) {
            dl('proctitle.so');
        }
        if(extension_loaded('posix') == false) {
            dl('posix.so');
        }
        $this->log_file_dir = $log_file_dir;
        $this->run_dir = $run_dir;
        $ips = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM,STREAM_IPPROTO_IP);
        //fork inotify process
        setproctitle('php:XInotifySync Main process');
        $pid = pcntl_fork();
        if($pid == -1) throw new XException('fork inotify process failure');
        if($pid == 0) {
            setproctitle('php:XInotifySync Watcher');
            $this->logs('Watcher Starter');
            $this->inotify_sock = $ips[0];
            fclose($ips[1]);
            $this->create_inotify_instance();
            $this->watch_list_conf = $watch_list_conf;
            $this->watch_list_conf_wd = inotify_add_watch($this->inotify_instance,$watch_list_conf,IN_MODIFY);
            $this->add_form_file($this->watch_list_conf);
            $this->watch_loop();
            $this->logs('Watcher Exit');
            exit(0);
        }
        //fork sync process
        $pid = pcntl_fork();
        if($pid == -1) throw new XException('fork sync process failure');
        if($pid == 0) {
            $this->logs('Dispatcher start');
            $this->sync_sock = $ips[1];
            setproctitle('php:XInotifySync Dispatcher');
            fclose($ips[0]);
            $this->sync_master_process_loop();
            $this->logs('Dispatcher Exit');
            exit(0);
        }
        $i = 0;
        while(true) {
           pcntl_wait($status);
        }
    }
    public function err($msg) {
        $this->msg($msg);
        exit(1);
    }
    public function msg($msg) {
        $time = microtime(true);
        $pid = posix_getpid();
        echo "$time:PID:$pid:$msg\r\n";
    }
    public function logs($msg) {
        $time = microtime(true);
        $pid = posix_getpid();
        $str = "$time:PID:$pid:$msg\r\n";
        $date = date('Ymd');
        file_put_contents("{$this->log_file_dir}/log_{$date}",$str,FILE_APPEND);
    }
    public function create_inotify_instance() {
        $this->inotify_instance = inotify_init();
    }
    public function watch($path,$ip,$port,$username, $password,$tpath) {
        $path = trim($path);
        $path = rtrim($path,'/');
        $wd = inotify_add_watch($this->inotify_instance,$path,IN_IGNORED|IN_ISDIR|IN_CLOSE_WRITE|IN_CREATE|IN_MOVE|IN_DELETE);
        $this->watch_descriptor[$wd]['wd'] = $wd;
        $this->watch_descriptor[$wd]['local_path'] = $path;
        $this->watch_descriptor[$wd]['target_ip'] = $ip;
        $this->watch_descriptor[$wd]['target_port'] = $port;
        $this->watch_descriptor[$wd]['target_path'] = $tpath;
        $this->watch_descriptor[$wd]['username'] = $username;
        $this->watch_descriptor[$wd]['password'] = $password;
        if(is_dir($path)) {
            $this->add_sub_dir($wd);
        }
        return $wd;
    }
    public function rm($wd) {
        inotify_rm_watch($this->inotify_instance, $wd);
        if(is_dir($this->watch_descriptor[$wd]['local_path'])) {
            $this->rm_sub_dir($this->watch_descriptor[$wd]['local_path']);
        }
        unset($this->watch_descriptor[$wd]);
    }
    public function rm_dir_wd($path) {
        foreach($this->watch_descriptor as $wd => $info) {
            if($path == $info['local_path']) {
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
        foreach($file_list as $server ) {
            $wd = $this->watch($server['local_path'],
                               $server['target_ip'], 
                               $server['target_port'],
                               $server['username'],
                               $server['password'],
                               $server['target_path']);
        }
    }
    public function file_transport_process_exit($signo) {
        $this->msg('file transport complete');
        pcntl_wait($status);
    }
    /**
     * sync_master_process_loop 
     * file sync master process
     * 
     * @access public
     * @return void
     */
    public function sync_master_process_loop() {
        while(1) {
            if(is_resource($this->sync_sock) == false) {
                $this->err('pip error');
                return;
            }
            pcntl_signal_dispatch();
            $read = array($this->sync_sock);
            $write = null;
            $except = null;
            $chg_num = stream_select($read,$write,$except,200000);
            if($chg_num > 0) {
                usleep(100000);
                $str = fread($this->sync_sock,10000);
                $message_group = explode("\r\n",$str);
                $ph = opendir($this->run_dir);
                $trans_num = 0;
                while(false === ($f = readdir($ph))) {
                    if($f == '.'|| $f == '..') continue;
                    $trans_num++;
                }
                if($trans_num <= $this->max_transporter) {
                    $file_info = array('U'=> array(),
                        'D'=>array(),
                        'MF'=> array(),
                        'MT'=> array());
                }
                foreach($message_group as $mstr) {
                    $unit = unserialize($mstr);
                    if(is_array($unit)) {
                        $this->merge($file_info,$unit);
                    }
                }
                if($trans_num <= $this->max_transporter) {
                    $tmp_pid = $this->transporter_file($file_info);
                    pcntl_waitpid($tmp_pid, $status);
                }
            }
        }
    }
    public function merge(&$file_info, $unit) {
        foreach($unit as $t => $value) {
            if($t == 'MT' || $t == 'MF') {
                foreach($value as $i => $f) {
                    $file_info[$t][$i] = $f;
                }
            } else {
                foreach($value as $i => $f) {
                    $file_info[$t][] = $f;
                }
            }
        }
    }
    public function transporter_pid() {
        $pid = posix_getpid();
        file_put_contents($this->run_dir.'/'.$pid,$pid);
    }
    public function rm_transporter_pid() {
        $pid = posix_getpid();
        unlink($this->run_dir.'/'.$pid);
    }
    /**
     * sync_file 
     * file opreate process
     * 
     * @param mixed $str 
     * @access public
     * @return void
     */
    public function transporter_file($change_list) {
        $fock_pid = pcntl_fork();
        if($fock_pid == -1) throw new XException('fork #1 Error');
        if($fock_pid >0) return $fock_pid;
        $fock_pid = pcntl_fork();
        if($fock_pid == -1) throw new XException('fork #2 ERROR');
        if($fock_pid>0) exit(0);
        chdir('/');
        umask('0');
        posix_setsid();
 //       fclose(STDIN);
 //       fclose(STDOUT);
  //      fclose(STDERR);
        $this->transporter_pid();
        fclose($this->sync_sock);
        setproctitle('php:XInotifySync Transporter');
        $this->logs('new Transporter Start');
        $sendfile_list = $change_list['U'];
        $file_num = count($sendfile_list);
        $move_del = array_diff_key($change_list['MF'],$change_list['MT']);
        $move_create = array_diff_key($change_list['MT'], $change_list['MF']);
        if(count($move_create) > 0) {
            $sendfile_list += $move_create;
        }
        $move = array_intersect_key($change_list['MF'],$change_list['MT']);
        $move_to = array_intersect_key($change_list['MT'], $change_list['MF']);
        if(count($move) > 0) {
            $pid = $this->exec_sync_mv($move, $move_to);
            pcntl_waitpid($pid, $status);
        }
        if(count($change_list['D']) > 0) {
            if(count($move_del) > 0) {
                $change_list['D'] += $move_del;
            }
            $pid = $this->exec_sync_rm($change_list['D']);
            pcntl_waitpid($pid, $status);
        }
        if($file_num >= $this->max_sync_process_num) {
            $max_num = $this->max_sync_process_num;
        } else {
            $max_num = $file_num;
        }
        $pnum = 1;
        $current_sync_queen = array();
        while(true) {
            if(count($sendfile_list) == 0) break;
            $file = array_shift($sendfile_list);
            $pid = $this->exec_sync_send_file($file);
            $pnum++;
            $current_sync_queen[$pid] = $pnum;
            if($pnum >= $max_num) {
                while(true) {
                    if(count($current_sync_queen) == 0) break;
                    $pid = pcntl_wait($status);
                    unset($current_sync_queen[$pid]);
                }
            }
        }
        $this->logs('transporter exit');
        $this->rm_transporter_pid();
        exit(0);
    }
    public function exec_sync_send_file($watch_info) {
        $pid = pcntl_fork();
        if($pid > 0) return $pid;
        if($pid == -1) {
            $this->logs('fork file send process error');
            return;
        }
        setproctitle('php:XInotifySync Execer');
        $this->logs("send file {$watch_info['local_path']} to {$watch_info['target_path']}");
        $ssh_ins = new XSSH2($watch_info['target_ip'],$watch_info['target_port'],
                            $watch_info['username'], $watch_info['password']);
        $ssh_ins->connect();
        $ssh_ins->create_sftp();
        if(is_dir($watch_info['local_path'])) {
            $ssh_ins->mkdir($watch_info['target_path'],0644);
        } else {
            $ssh_ins->sendfile($watch_info['local_path'],$watch_info['target_path'],0644);
        }
        exit(0);
    }
    public function exec_sync_mv($move_form, $move_to) {
        $pid = pcntl_fork();
        if($pid > 0) return $pid;
        if($pid == -1) {
            $this->logs('fork move file process error');
            return;
        }
        setproctitle('php:XInotifySync Mover');
        $ssh_conn_list = array();
        foreach($move_form as $cookie => $file) {
            if(!in_array($file['target_ip'],$ssh_conn_list)) {
                $ssh_ins = new XSSH2($file['target_ip'],$file['target_port'],
                                $file['username'],$file['password']);
                $ssh_conn_list[$file['target_ip']] = $ssh_ins;
                $ssh_ins->connect();
                $ssh_ins->create_sftp();
            } else {
                $ssh_ins = $ssh_conn_list[$file['target_ip']];
            }
            $this->logs("mv {$file['target_path']} to {$move_to[$cookie]['target_path']}");
            $ssh_ins->mv($file['target_path'], $move_to[$cookie]['target_path']);
        }
        foreach($ssh_conn_list as $ssh_ins) {
            $ssh_ins->disconnect();
        }
        exit(0);
    }
    public function exec_sync_rm($delete) {
        $pid = pcntl_fork();
        if($pid >0) return $pid;
        if($pid == -1) {
            $this->logs('fork del file process error');
            return;
        }
        setproctitle('php:XInotifySync Deleter');
        $ssh_conn_list = array();
        foreach($delete as $file) {
            if(!in_array($file['target_ip'],$ssh_conn_list)) {
                $ssh_ins = new XSSH2($file['target_ip'],$file['target_port'],
                                $file['username'],$file['password']);
                $ssh_ins->connect();
                $ssh_ins->create_sftp();
            } else {
                $ssh_ins = $ssh_conn_list[$file['target_ip']];
            }
            $this->logs("rm file {$file['target_path']}");
            $ssh_ins->rm($file['target_path']);
        }
        foreach($ssh_conn_list as $ssh_ins) {
            $ssh_ins->disconnect();
        }
        exit(0);
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
    public function notify_file_list($change) {
        $change_str = serialize($change) . "\r\n";
        $read = null;
        $write = array($this->inotify_sock);
        $except = null;
        $chg_num = 0;
        $chg_num = stream_select($read,$write,$except,200000);
        if($chg_num > 0) {
            if(in_array($this->inotify_sock,$write)) {
                $len = fwrite($this->inotify_sock,$change_str, strlen($change_str));
                $write = array();
            }
        }
    }
    public function add_form_file($file) {
        $ini = XConfig::parse_ini($file);
        $this->add_form_array($ini);
    }
    public function add_sub_dir($wd) {
        $watch_descriptor = $this->watch_descriptor[$wd];
        $dh = opendir($watch_descriptor['local_path']);
        if($dh === false) return;
        while(false !== ($name = readdir($dh))) {
            if($name == '.' || $name == '..') continue;
            $path_dir = "{$watch_descriptor['local_path']}/{$name}";
            $tpath = "{$watch_descriptor['target_path']}/{$name}";
            if(is_dir($path_dir)) {
                $nwd = $this->watch($path_dir, $watch_descriptor['target_ip'],
                             $watch_descriptor['target_port'], 
                            $watch_descriptor['username'],
                            $watch_descriptor['password'],
                             $tpath);
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
        $proto_array = array();
            $change['D'] = array();
            $change['MT'] = array();
            $change['MF'] = array();
            $change['U'] = array();

        while(true) {
            $move_status = 0;
            stream_set_blocking($this->inotify_instance,1);
            $events = $this->get();
            $present_timestamp = time();
            $this->logs('New events');
            $change = $proto_array; 
            foreach($events as $ev => $ev_info) {
                if(!isset($this->watch_descriptor[$ev_info['wd']])) {
                    continue;
                }
                $watch_info = $this->watch_descriptor[$ev_info['wd']];
                $os_path = "{$watch_info['local_path']}/{$ev_info['name']}";
                $os_tpath = "{$watch_info['target_path']}/{$ev_info['name']}";
                $wd = $ev_info['wd'];
                if($ev_info['wd'] == $this->watch_list_conf_wd) {
                    $this->reload_watch_list($ev_info);
                    continue;
                }
                switch($ev_info['mask']) {
                    case IN_CREATE|IN_ISDIR: //创建文件夹
                        $wd = $this->watch($os_path, $watch_info['target_ip'],
                                            $watch_info['target_port'],
                                            $watch_info['username'],
                                            $watch_info['password'],
                                            $os_tpath);
                        $chain = 'U';
                        $nid = $wd;
                    break;
                    case IN_CREATE:
                        $chain = null;
                    break;
                    case IN_CLOSE_WRITE:  //修改
                        $chain = 'U';
                        $nid = $wd;
                        $this->msg('write');
                    break;
                    case IN_MOVED_TO:
                        $chain = 'U';
                        $nid = $wd;
                    break;
                    case IN_MOVED_TO|IN_ISDIR: //移进
                        $wd = $this->watch($os_path, $watch_info['target_ip'],
                                            $watch_info['target_port'],
                                            $watch_info['username'],
                                            $watch_info['password'],
                                            $os_tpath);
                        $nid = $ev_info['cookie'];
                        $chain = 'MT';
                    break;
                    case IN_MOVED_FROM|IN_ISDIR: //移除文件夹
                        $this->rm_dir_wd($os_path);
                        $nid = $ev_info['cookie'];
                        $chain = 'MF';
                    break;
                    case IN_MOVED_FROM: //移除文件
                        $nid = $wd;
                        $chain = 'D';
                    break;
                    case IN_DELETE:
                        $nid = $wd;
                        $chain = 'D';
                    break;
                    case IN_DELETE|IN_ISDIR:  //删除
                        $nid = $wd;
                        $chain = 'D';
                        $this->rm_dir_wd($os_path);
                    break;
                    case IN_DELETE_SELF:  //监视文件夹删除
                        $thsi->rm($ev_info['wd']);
                    break;
                    case IN_MOVE_SELF: //监视文件夹移动
                    break;
                    default:
                    break;
                }
                if($chain == null) continue;
                $array = array();
                $array['local_path'] = $os_path;
                $array['target_path'] = $os_tpath;
                $array['target_ip'] = $watch_info['target_ip'];
                $array['target_port'] = $watch_info['target_port'];
                $array['username'] = $watch_info['username'];
                $array['password'] = $watch_info['password'];
                $change[$chain][$nid] = $array;
            }
            if($change != $proto_array) {
                $this->notify_file_list($change);
            }
        }
    }
    public function __destruct() {
        if(is_resource($this->inotify_instance)) {
            fclose($this->inotify_instance);
        }
    }
}
