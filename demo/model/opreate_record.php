<?php
class opreateRecordModel extends XDataModel {
    public $log_file = '';
    public function auto_conf() {
        $opreate_table = $this->use_conf('txtdb','b');
        $this->connect_database($opreate_table);
    }
    public function check_modified() {
        return filemtime($this->log_file);
    }
    public function append_record($action) {
        $time = time();
        $this->DB->opreate_log_table->add($time,$action);
    }
    public function pull_modified($time) {
        return $this->DB->opreate_log_table->key_greater_than($time, XTxtDB::FIND_END);
    }
}
