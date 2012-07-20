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
exists_frame();
/**
 * XInotifySync 
 * 
 * @package 
 * @version $id$
 * @author Chopins xiao <chopins.xiao@gmail.com> 
 */
class XInotifySync {
    public $inotify_instance = null;
    public $watch_descriptor = array();
    public $pending_event = 0;
    public $watch_list_conf_wd = null;
    public $cfg = null;
    public $inotify_sock = null;
    public $sync_sock = null;
    public $max_sync_process_num = 5;
    public function __construct() {
        daemon();
        $this->load_cfg();
        $ips = stream_socket_pair(STREAM_RF_UNIX, STREAM_SOCK_STREAM,STREAM_IPPROTO_IP);
        //fork inotify process
        $pid = pcntl_fork();
        if($pid == -1) throw new XException('fork inotify process failure');
        if($pid == 0) {
            $this->inotify_sock = $ips[0];
            fclose($ips[1]);
            $this->create_inotify_instance();
            $watch_list_conf_file = $this->cfg->watch_list_conf;
            $this->watch_list_conf_wd = $this->watch($watch_list_conf_file);
            $this->watch_loop();
            exit(0);
        }
        //fork sync process
        $pid = pcntl_fork();
        if($pid == -1) throw new XException('fork sync process failure');
        if($pid == 0) {
            $this->sync_sock = $ips[1];
            fclose($ips[0]);
            $this->sync_master_process_loop();
            exit(0);
        }
        pcntl_wait($status);
    }
    public function load_cfg($_CFG = null) {
        if($_CFG == null) {
            global $_CFG;
        }
        $this->cfg = $_CFG->inotify;
    }
    public function create_inotify_instance() {
        $this->inotify_instance = inotify_init();
    }
    public function watch($path,$ip,$port, $tpath) {
        $path = trim($path);
        $wd = inotify_add_watch($this->inotify_instance,$path,
                            IN_DELETE_SELF|IN_MOVE_SELF|IN_MODIFY|IN_CREATE|IN_MOVE|IN_DELETE);
        $this->watch_descriptor[$wd]['wd'] = $wd;
        $this->watch_descriptor[$wd]['path'] = $path;
        $this->watch_descriptor[$wd]['target_ip'] = $ip;
        $this->watch_descriptor[$wd]['target_port'] = $port;
        $this->watch_descriptor[$wd]['target_path'] = $tpath;
        return $wd;
    }
    public function rm($wd) {
        inotify_rm_watch($this->inotify_instance, $wd);
        unset($this->watch_descriptor[$wd]);
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
    public function sync_master_process_loop() {
        stream_set_blocking($this->sync_sock, 1);
        while(1) {
            $acp = stream_socket_accept($this->sync_sock, 0);
            $str = stream_socket_recvfrom($acp,2048);
            $this->sync_file($str);
            fclose($acp);
        }
    }
    public function sync_file($str) {
        list($this->watch_descriptor,$change_list) = unserialize($str);
        $file_num = count($change_list['C']) + count($change_list['U']);
        $this->exec_sync_cmd($change_list['D'],$change_list['M']);
        if($file_num >= $this->max_sync_process_num) {
            $max_num = $this->max_sync_process_num;
        } else {
            $max_num = $file_num;
        }
        for($i=0;$i<$file_num;$i++) {
            $this->exec_sync_send_file($change_list[$i];
        }
    }
    public function exec_sync_cmd($delete, $move) {
    }
    public function notify_file($watch_info, $change) {
        $change_str = serialize(array($watch_info,$change));
        stream_socket_sendto($this->inotify_sock,$change_str);
    }
    public function add_form_file($file) {
        if(!is_file($file)) return;
        $fh = SplFileObject($file,'r');
        while(!$fh->eof()) {
            $conf_line = $fh->fgets();
            list($ini,) = explode('#',$conf_line,2);
            $ini = trim($ini);
            if(empty($ini)) continue;
            list($path, $ip, $port, $tpath) = explode(':',$ini);
            $this->watch($path,$ip, $port,$tpath);
        }
    }
    public function reload_watch_list($ev_info) {
        $this->rm_all_watch();
        if($ev_info['mask'] & IN_DELETE_SELF ||
                $ev_info['mask'] & IN_MOVE_SELF) {
            return;
        }
        $this->add_form_file($this->cfg->watch_list_conf);
    }
    public function loop() {
        while(true) {
            stream_set_blocking($this->inotify_instance,1);
            $events = $this->get();
            $change = array();
            $change['C'] = array();
            $change['M'] = array();
            $change['D'] = array();
            $change['U'] = array();
            foreach($events as $ev => $ev_info) {
                switch(true) {
                    case ($ev_info['wd'] & $this->watch_list_conf_wd):
                        $this->reload_watch_list($ev_info);
                    break;
                    case $ev_info['mask'] & IN_CREATE: //创建
                        $change['C'][] = $ev_info['name'];
                    break;
                    case $ev_info['mask'] & IN_MODIFY:  //修改
                        $change['U'][] = $ev_info;
                    break;
                    case $ev_info['mask'] & IN_MOVE: //移动
                        $change['M'][] = $ev_info['name'];
                    break;
                    case $ev_info['mask'] & IN_DELETE:  //删除
                        $change['D'][] = $ev_info['name'];
                    break;
                    case $ev_info['mask'] & IN_DELETE_SELF:  //监视文件夹删除
                    break;
                    case $ev_info['mask'] & IN_MOVE_SELF: //监视文件夹移动
                    break;
                    default:
                    break;
                }
            }
            $this->sendfile($this->watch_descriptor, $change);
        }
    }
    public function __destruct() {
        fclose($this->inotify_instance);
    }
}
