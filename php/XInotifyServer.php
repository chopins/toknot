<?php
class XInotifyServer {
    public $inotify_instance = null;
    public $watch_descriptor = array();
    public $pending_event = 0;
    public $watch_list = array();
    public $cfg = null;
    public function __construct() {
        $this->inotify_instance = inotify_init();
        stream_set_blocking($this->inotify_instance,1);
    }
    public function load_cfg($_CFG = null) {
        if($_CFG == null) {
            global $_CFG;
        }
        $this->cfg = $_CFG->inotify;
    }
    public function watch($path) {
        $path = trim($path);
        $wd = inotify_add_watch($this->inotify_instance,$path,
                IN_CREATE|IN_MODIFY|IN_MOVED_TO|IN_CREATE);
        $this->watch_descriptor[$wd] = $path;
        $this->watch_list[] = $path;
    }
    public function rm($wd) {
        inotify_rm_watch($this->inotify_instance, $wd);
        unset($this->watch_descriptor[$wd]);
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
            $this->watch($file);
        }
    }
    public function sync_process() {
    }
    public function sendfile() {
    }
    public function add_form_file($file) {
        if(!is_file($file)) return;
        $fh = SplFileObject($file,'r');
        while(!$fh->eof()) {
            $this->watch($fh->fgets());
        }
    }
    public function loop() {
        while(true) {
            $this->get();
        }
    }
    public function __destruct() {
        fclose($this->inotify_instance);
    }
}
