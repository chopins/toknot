<?php
class runDaemon extends X{
    protected $inotify_instance = null;
    protected $log_file = null;
    protected $run_record = null;
    protected $pre_exec_id = 0;
    protected $opRecord = null;
    protected $execute_res = null;
    public function __construct() {
        dl_extension('inotify','inotify_init');
        dl_extension('pcntl','pcntl_fork');
        daemon();
        $this->opRecord = $this->LM('opreateRecord');
        $this->log_file = $this->opRecord->get_db_file();
        $this->init_pre_exec_id();
        $this->loop();
    }
    public function init_pre_exec_time() {
        $id = $this->opRecord->last_exec_id();
        if($id > 0) {
            $this->pre_exec_id = $id;
        } else {
            $this->pre_exec_id = $this->opRecord->last_id();
        }
    }
    public function watch_record_file() {
        $this->inotify_instance = inotify_init();
        inotify_add_watch($this->inotify_instance, 
                          $this->log_file, IN_CLOSE_WRITE);
        stream_set_blocking($this->inotify_instance,1);
    }
    public function execute_act() {
        $act_list = $this->opRecord->pull_modified($this->pre_exec_time);
        $this->pre_exec_id = $this->opRecord->last_pull_id();
        $this->opRecord->set_last_exec_id($this->pre_exec_id);
        if(count($act_list) > 0) {
            foreach($act_list as $key=> $action) {
                $this->CV($action['cls'])->$action['func']();
            }
        }
    }
    public function loop() {
        while(true) {
            $change = inotify_read($this->inotify_instance);
            if(count($change) > 0) {
                $this->execute_act();
            }
        }
    }
    public function __destruct() {
        if(is_resource($this->inotify_instance)) {
            fclose($this->inotify_instance);
        }
    }
}

return new runDaemon();
